<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiamondClaimLog;
use App\Models\DiamondWallet;
use App\Models\GmAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SupplyReport extends Command
{
    protected $signature = 'supply:report
                            {--date= : Date in YYYY-MM-DD format (default: today)}';

    protected $description = 'Show portal KC / KC Block supply report';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : now()->toDateString();

        $this->info("Supply Report — {$date}");
        $this->newLine();

        // ─── 1. Outstanding supply ─────────────────────────────────────────
        $totalBalance = (int) DiamondWallet::sum('balance');
        $totalLifetime = (int) DiamondWallet::sum('lifetime_mined');
        $totalBlocks = (int) DiamondWallet::sum('diamond_blocks');
        $walletCount = DiamondWallet::where('balance', '>', 0)->count();
        $blockCount = DiamondWallet::where('diamond_blocks', '>', 0)->count();

        $this->line('<fg=cyan>Portal KC outstanding:</> <fg=yellow>' . number_format($totalBalance) . '</>');
        $this->line('<fg=cyan>Lifetime mined (all users):</> <fg=yellow>' . number_format($totalLifetime) . '</>');
        $this->line('<fg=cyan>KC Block outstanding:</> <fg=yellow>' . number_format($totalBlocks) . '</>');
        $this->line('<fg=cyan>Users with KC balance:</> ' . $walletCount);
        $this->line('<fg=cyan>Users with KC Block:</> ' . $blockCount);
        $this->newLine();

        // ─── KC-equivalent outstanding ─────────────────────────────────────
        // Each KC Block represents compressed KC (typically blocks are worth
        // the same as KC since they're just compressed for storage).
        // No reverse conversion path currently exists in portal.
        $kcEquivalent = $totalBalance + $totalBlocks;
        $this->line('<fg=cyan>KC-equivalent total (balance + blocks):</> <fg=yellow>' . number_format($kcEquivalent) . '</>');
        $this->newLine();

        // ─── 2. Daily supply stats ─────────────────────────────────────────
        $todayClaimed = (int) DiamondClaimLog::whereDate('created_at', $date)
            ->sum('amount_claimed');
        $todayClaimers = DiamondClaimLog::whereDate('created_at', $date)
            ->distinct('user_id')
            ->count('user_id');
        $avgRate = (int) DiamondClaimLog::whereDate('created_at', $date)
            ->avg('rate_snapshot');

        $this->info("Today's Supply ({$date})");
        $this->table(['Metric', 'Value'], [
            ['Total KC claimed',      number_format($todayClaimed)],
            ['Unique claimers',        $todayClaimers],
            ['Avg rate (KC/h)',       number_format($avgRate)],
            ['Avg per claimer',       $todayClaimers > 0 ? number_format($todayClaimed / $todayClaimers) : '0'],
            ['KC from mining jobs',   number_format($this->supplyFromGmActions($date, 'charge_currency'))],
        ]);
        $this->newLine();

        // ─── 3. Top KC holders ─────────────────────────────────────────────
        $this->info('Top 10 KC Holders');
        $this->table(
            ['#', 'Username', 'Balance', 'Lifetime', 'Blocks', 'Mined Today'],
            DiamondWallet::select('user_id', 'balance', 'lifetime_mined', 'diamond_blocks')
                ->where('balance', '>', 0)
                ->orderByDesc('balance')
                ->limit(10)
                ->get()
                ->map(function (DiamondWallet $w) use ($date) {
                    static $idx = 0; $idx++;
                    $user = User::find($w->user_id);
                    $minedToday = (int) DiamondClaimLog::where('user_id', $w->user_id)
                        ->whereDate('created_at', $date)
                        ->sum('amount_claimed');

                    return [
                        $idx,
                        $user ? $user->username : "ID:{$w->user_id}",
                        number_format((int) $w->balance),
                        number_format((int) $w->lifetime_mined),
                        number_format((int) $w->diamond_blocks),
                        number_format($minedToday),
                    ];
                })
                ->toArray()
        );
        $this->newLine();

        // ─── 4. Top KC Block holders ───────────────────────────────────────
        $this->info('Top 10 KC Block Holders (compressed KC)');
        $blockHolders = DiamondWallet::select('user_id', 'diamond_blocks', 'balance')
            ->where('diamond_blocks', '>', 0)
            ->orderByDesc('diamond_blocks')
            ->limit(10)
            ->get();

        if ($blockHolders->isEmpty()) {
            $this->line('  No KC Block holders found.');
        } else {
            $this->table(
                ['#', 'Username', 'KC Blocks', 'KC Balance', 'Total'],
                $blockHolders->map(function (DiamondWallet $w) {
                    static $idx = 0; $idx++;
                    $user = User::find($w->user_id);

                    return [
                        $idx,
                        $user ? $user->username : "ID:{$w->user_id}",
                        number_format((int) $w->diamond_blocks),
                        number_format((int) $w->balance),
                        number_format((int) $w->diamond_blocks + (int) $w->balance),
                    ];
                })
                ->toArray()
            );
        }
        $this->newLine();

        // ─── 5. Reverse conversion path? ───────────────────────────────────
        $this->info('Conversion Paths');
        $this->line('  <fg=green>Mine → Claim → Portal KC (balance)</>  ✓ active');
        $this->line('  <fg=yellow>Portal KC → Game KC (charge_currency)</>  ✓ via DiamondMiningJob');
        $this->line('  <fg=red>Game KC → KC Block (CraftCrystal)</>         ✗ sealed (no active path)');
        $this->line('  <fg=red>KC Block → KC uncompress</>                   ✗ sealed (no active path)');
        $this->line('  <fg=red>KC Block → S1 / transfer</>                   ✗ no path');

        return self::SUCCESS;
    }

    /**
     * Sum KC credited to game servers from gm_actions.
     */
    private function supplyFromGmActions(string $date, string $actionType): int
    {
        return (int) GmAction::where('action_type', $actionType)
            ->whereDate('created_at', $date)
            ->where('status', 'executed')
            ->sum(\Illuminate\Support\Facades\DB::raw("CAST(JSON_EXTRACT(payload, '$.amount') AS UNSIGNED)"));
    }
}
