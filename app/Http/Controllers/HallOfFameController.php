<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HallOfFameLegend;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HallOfFameController extends Controller
{
    private const CACHE_TTL = 120;

    private const FULL_CACHE_TTL = 130; // Slightly longer to ensure myRank can always read it

    private const DISPLAY_LIMIT = 50;

    private const JOB_NAMES = [
        1 => 'Chiến Binh',
        2 => 'Ma Đấu Sĩ',
        3 => 'Tiên Nữ',
    ];

    private const JOB_ICONS = [
        1 => '⚔️',
        2 => '🔮',
        3 => '🧝‍♀️',
    ];

    public function index(): View
    {
        $legends = HallOfFameLegend::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('server_key');

        return view('hall-of-fame', compact('legends'));
    }

    public function rankings(): JsonResponse
    {
        $data = Cache::get('hall_of_fame:rankings');

        if (! $data) {
            $data = $this->generateRankings();

            $oldData = Cache::get('hall_of_fame:rankings_old');
            if ($oldData && is_array($oldData)) {
                $gameKeys = ['power', 'level', 'gold', 'yuanbao', 'warrior', 'mage', 'taoist', 'playtime'];
                $webKeys = ['miners', 'shoppers', 'spinners', 'checkin'];

                foreach ($gameKeys as $key) {
                    if (isset($data[$key]) && is_array($data[$key])) {
                        $this->applyTrends($data[$key], $oldData[$key] ?? [], 'account');
                    }
                }
                foreach ($webKeys as $key) {
                    if (isset($data[$key]) && is_array($data[$key])) {
                        $this->applyTrends($data[$key], $oldData[$key] ?? [], 'username');
                    }
                }
            }

            Cache::put('hall_of_fame:rankings', $data, self::CACHE_TTL);
            Cache::put('hall_of_fame:rankings_old', $data, self::CACHE_TTL * 15);
        }

        return response()->json($data);
    }

    /**
     * @return array{servers: list<array<string, mixed>>, rankings: array<string, mixed>, legends: list<array<string, mixed>>}
     */
    public function sdkPayload(): array
    {
        try {
            $rankings = $this->generateRankings(false);

            return [
                'servers' => $rankings['servers'] ?? [],
                'rankings' => $rankings,
                'legends' => $this->fetchLegends(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'servers' => [],
                'rankings' => [],
                'legends' => [],
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchLegends(): array
    {
        return HallOfFameLegend::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (HallOfFameLegend $legend): array => [
                'id' => $legend->id,
                'server_name' => $legend->server_name,
                'server_key' => $legend->server_key,
                'server_status' => $legend->server_status,
                'category' => $legend->category,
                'category_label' => $legend->category_label,
                'player_name' => $legend->player_name,
                'score_value' => $legend->score_value,
                'score_label' => $legend->score_label,
                'rewards' => $legend->rewards,
                'sort_order' => $legend->sort_order,
            ])
            ->values()
            ->all();
    }

    private function generateRankings(bool $storeFullRankings = true): array
    {
        $allActors = $this->fetchAllActors();
        $servers = Server::query()
            ->where('status', '!=', Server::STATUS_MAINTENANCE)
            ->whereNotNull('opened_at')
            ->get();

        // ── Full sorted lists (all players) stored separately for myRank lookup ──
        $fullGameRankings = [
            'power' => $this->sortAll($allActors, 'totalpower'),
            'level' => $this->sortAll($allActors, fn ($a) => $a->level * 100 + $a->zhuansheng_lv),
            'gold' => $this->sortAll($allActors, 'gold'),
            'yuanbao' => $this->sortAll($allActors, 'yuanbao'),
            'warrior' => $this->sortAll($allActors->where('job', 1), 'warrior_power'),
            'mage' => $this->sortAll($allActors->where('job', 2), 'mage_power'),
            'taoist' => $this->sortAll($allActors->where('job', 3), 'taoistpriest_power'),
            // Fix 3: Use cumulative EXP as the true "cultivation" metric (reflects exp potion usage)
            'playtime' => $this->sortAll($allActors, 'exp'),
        ];

        if ($storeFullRankings) {
            Cache::put('hall_of_fame:full_rankings', $fullGameRankings, self::FULL_CACHE_TTL);
        }

        return [
            // ── In-game Rankings (top 50 for display) ──
            'power' => array_slice($fullGameRankings['power'], 0, self::DISPLAY_LIMIT),
            'level' => array_slice($fullGameRankings['level'], 0, self::DISPLAY_LIMIT),
            'gold' => array_slice($fullGameRankings['gold'], 0, self::DISPLAY_LIMIT),
            'yuanbao' => array_slice($fullGameRankings['yuanbao'], 0, self::DISPLAY_LIMIT),

            // ── Class Rankings ──
            'warrior' => array_slice($fullGameRankings['warrior'], 0, self::DISPLAY_LIMIT),
            'mage' => array_slice($fullGameRankings['mage'], 0, self::DISPLAY_LIMIT),
            'taoist' => array_slice($fullGameRankings['taoist'], 0, self::DISPLAY_LIMIT),

            // ── Tu Luyện (Cumulative EXP) ──
            'playtime' => array_slice($fullGameRankings['playtime'], 0, self::DISPLAY_LIMIT),

            // ── Web Rankings ──
            'miners' => $this->getTopMiners(),
            'shoppers' => $this->getTopShoppers(),
            'spinners' => $this->getTopSpinners(),
            'checkin' => $this->getTopCheckin(),

            // ── Server Stats ──
            'server_stats' => $this->buildServerStats($allActors),

            // ── Meta ──
            'servers' => $servers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'total_players' => $allActors->count(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Sort all actors descending by a key and return formatted array (no limit).
     *
     * @param  Collection<int, object>  $actors
     * @return list<array<string, mixed>>
     */
    private function sortAll(Collection $actors, string|\Closure $sortKey): array
    {
        return $actors->sortByDesc($sortKey)->values()
            ->map(fn ($a) => $this->formatActor($a))
            ->all();
    }

    private function applyTrends(array &$newList, array $oldList, string $idField): void
    {
        $oldRanks = [];
        foreach ($oldList as $idx => $entry) {
            if (isset($entry[$idField])) {
                $oldRanks[$entry[$idField]] = $idx;
            }
        }

        foreach ($newList as $idx => &$entry) {
            $id = $entry[$idField] ?? null;
            if ($id && isset($oldRanks[$id])) {
                $oldRank = $oldRanks[$id];
                if ($idx < $oldRank) {
                    $entry['_trend'] = 1;
                    $entry['_trend_diff'] = $oldRank - $idx;
                } elseif ($idx > $oldRank) {
                    $entry['_trend'] = -1;
                    $entry['_trend_diff'] = $idx - $oldRank;
                } else {
                    $entry['_trend'] = 0;
                    $entry['_trend_diff'] = 0;
                }
            } else {
                $entry['_trend'] = 1;
                $entry['_trend_diff'] = 'NEW';
            }
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function fetchAllActors(): Collection
    {
        $servers = Server::query()
            ->where('status', '!=', Server::STATUS_MAINTENANCE)
            ->get();

        $allActors = collect();

        foreach ($servers as $server) {
            $connection = $server->db_connection_name;
            if (! $connection) {
                continue;
            }

            try {
                $actors = DB::connection($connection)
                    ->table('actors')
                    ->select([
                        'actorname', 'accountname', 'job', 'sex', 'level',
                        'totalpower', 'gold', 'yuanbao', 'vip_level', 'recharge',
                        'zhuansheng_lv', 'monthcard', 'total_wing_power', 'totalonline',
                        'warrior_power', 'mage_power', 'taoistpriest_power',
                        'star_value', 'essence',
                        // Fix 3: cumulative EXP — reflects true cultivation effort incl. exp potions
                        'exp',
                    ])
                    ->where('level', '>', 1)
                    ->get()
                    ->map(function ($actor) use ($server) {
                        $actor->server_name = $server->name;
                        $actor->server_id = $server->id;
                        $actor->job_name = self::JOB_NAMES[$actor->job] ?? 'Unknown';
                        $actor->job_icon = self::JOB_ICONS[$actor->job] ?? '❓';

                        return $actor;
                    });

                $allActors = $allActors->merge($actors);
            } catch (\Throwable) {
                continue;
            }
        }

        return $allActors;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getTopMiners(): array
    {
        return DB::table('diamond_wallets')
            ->join('users', 'users.id', '=', 'diamond_wallets.user_id')
            ->select('users.username', 'diamond_wallets.lifetime_mined', 'diamond_wallets.ascension_level', 'diamond_wallets.balance')
            ->where('diamond_wallets.lifetime_mined', '>', 0)
            ->orderByDesc('diamond_wallets.lifetime_mined')
            ->limit(self::DISPLAY_LIMIT)
            ->get()
            ->values()
            ->map(fn ($m) => (array) $m)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getTopShoppers(): array
    {
        return DB::table('wpoint_transactions')
            ->join('users', 'users.id', '=', 'wpoint_transactions.user_id')
            ->where('wpoint_transactions.amount', '<', 0)
            ->select('users.username')
            ->selectRaw('COUNT(wpoint_transactions.id) as order_count')
            ->selectRaw('ABS(SUM(wpoint_transactions.amount)) as total_spent')
            ->groupBy('wpoint_transactions.user_id', 'users.username')
            ->orderByDesc('total_spent')
            ->limit(self::DISPLAY_LIMIT)
            ->get()
            ->values()
            ->map(fn ($s) => (array) $s)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getTopSpinners(): array
    {
        return DB::table('spin_logs')
            ->join('users', 'users.id', '=', 'spin_logs.user_id')
            ->select('users.username')
            ->selectRaw('COUNT(spin_logs.id) as spin_count')
            ->selectRaw('SUM(spin_logs.wcoin_cost) as total_wcoin_spent')
            ->groupBy('spin_logs.user_id', 'users.username')
            ->orderByDesc('spin_count')
            ->limit(self::DISPLAY_LIMIT)
            ->get()
            ->values()
            ->map(fn ($s) => (array) $s)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getTopCheckin(): array
    {
        return DB::table('checkin_logs')
            ->join('users', 'users.id', '=', 'checkin_logs.user_id')
            ->select('users.username')
            ->selectRaw('COUNT(checkin_logs.id) as checkin_count')
            ->selectRaw('MAX(checkin_logs.day_index) as max_streak')
            ->groupBy('checkin_logs.user_id', 'users.username')
            ->orderByDesc('checkin_count')
            ->limit(self::DISPLAY_LIMIT)
            ->get()
            ->values()
            ->map(fn ($c) => (array) $c)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatActor(object $actor): array
    {
        return [
            'name' => $actor->actorname,
            'job' => $actor->job,
            'job_name' => $actor->job_name,
            'job_icon' => $actor->job_icon,
            'level' => $actor->level,
            'power' => $actor->totalpower,
            'vip_level' => $actor->vip_level,
            'recharge' => $actor->recharge,
            'gold' => $actor->gold,
            'yuanbao' => $actor->yuanbao,
            'zhuansheng' => $actor->zhuansheng_lv,
            'monthcard' => $actor->monthcard,
            'wing_power' => $actor->total_wing_power,
            'online_minutes' => $actor->totalonline,
            'exp' => $actor->exp,
            'warrior_power' => $actor->warrior_power,
            'mage_power' => $actor->mage_power,
            'taoist_power' => $actor->taoistpriest_power,
            'star_value' => $actor->star_value,
            'essence' => $actor->essence,
            'server' => $actor->server_name,
            'server_id' => $actor->server_id,
            'account' => $actor->accountname,
        ];
    }

    /**
     * @param  Collection<int, object>  $actors
     * @return array<string, mixed>
     */
    private function buildServerStats(Collection $actors): array
    {
        $warriors = $actors->where('job', 1);
        $mages = $actors->where('job', 2);
        $taoists = $actors->where('job', 3);
        $total = $actors->count() ?: 1;

        return [
            'total_power' => $actors->sum('totalpower'),
            'total_gold' => $actors->sum('gold'),
            'total_yuanbao' => $actors->sum('yuanbao'),
            'total_recharge' => $actors->sum('recharge'),
            'avg_level' => round($actors->avg('level') ?? 0),
            'max_level' => $actors->max('level') ?? 0,
            'total_online_hours' => round(($actors->sum('totalonline') ?? 0) / 60),
            'class_distribution' => [
                'warrior' => ['count' => $warriors->count(), 'pct' => round($warriors->count() / $total * 100)],
                'mage' => ['count' => $mages->count(), 'pct' => round($mages->count() / $total * 100)],
                'taoist' => ['count' => $taoists->count(), 'pct' => round($taoists->count() / $total * 100)],
            ],
            'vip_count' => $actors->where('vip_level', '>', 0)->count(),
            'monthcard_count' => $actors->where('monthcard', '>', 0)->count(),
            'zhuansheng_count' => $actors->where('zhuansheng_lv', '>', 0)->count(),
        ];
    }

    /**
     * Find the current user's rank + 2 players above/below in the specified tab.
     * Uses the full sorted list (not just top 20) so players outside top 20 can see their rank.
     */
    public function myRank(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ranks' => []]);
        }

        // Fix 1: Use full_rankings for game tabs (all players, not just top 50)
        $fullData = Cache::get('hall_of_fame:full_rankings');
        $displayData = Cache::get('hall_of_fame:rankings');

        if (! $displayData) {
            return response()->json(['ranks' => []]);
        }

        $username = (string) $user->username;
        $results = [];

        $gameKeys = ['power', 'level', 'gold', 'yuanbao', 'warrior', 'mage', 'taoist', 'playtime'];
        $webKeys = ['miners', 'shoppers', 'spinners', 'checkin'];

        foreach ($gameKeys as $key) {
            // Prefer full list for accurate rank; fall back to display list
            $list = $fullData[$key] ?? $displayData[$key] ?? [];
            $idx = $this->findPlayerIndex((array) $list, $username, 'account');
            if ($idx !== null) {
                $results[$key] = $this->extractNearby((array) $list, $idx);
            }
        }

        foreach ($webKeys as $key) {
            $list = $displayData[$key] ?? [];
            $idx = $this->findPlayerIndex((array) $list, $username, 'username');
            if ($idx !== null) {
                $results[$key] = $this->extractNearby((array) $list, $idx);
            }
        }

        return response()->json(['ranks' => $results]);
    }

    /**
     * @param  list<array<string, mixed>>  $list
     */
    private function findPlayerIndex(array $list, string $username, string $field): ?int
    {
        foreach ($list as $idx => $entry) {
            if (($entry[$field] ?? null) === $username) {
                return (int) $idx;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $list
     * @return array{rank: int, total: int, nearby: list<array<string, mixed>>}
     */
    private function extractNearby(array $list, int $myIdx): array
    {
        $start = max(0, $myIdx - 2);
        $end = min(count($list) - 1, $myIdx + 2);
        $nearby = [];

        for ($i = $start; $i <= $end; $i++) {
            $entry = $list[$i];
            $entry['_rank'] = $i + 1;
            $entry['_is_me'] = ($i === $myIdx);
            $nearby[] = $entry;
        }

        return [
            'rank' => $myIdx + 1,
            'total' => count($list),
            'nearby' => $nearby,
        ];
    }
}
