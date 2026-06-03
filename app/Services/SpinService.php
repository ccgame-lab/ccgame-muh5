<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ExecuteGmCommand;
use App\Models\GmAction;
use App\Models\Server;
use App\Models\SpinLog;
use App\Models\User;
use App\Models\WPointTransaction;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpinService
{
    public function __construct(
        protected WPointService $wpointService,
    ) {}

    /**
     * Execute a spin with daily limits, diminishing cost, and chain protection.
     *
     * Safety layers:
     *   1. Daily spin limit (hard cap)
     *   2. Diminishing cost after threshold (soft anti-grind)
     *   3. Extra turn chain limit (prevents lucky streaks)
     *   4. WPoint daily cap (absolute ceiling on WPoint from spin rewards)
     *
     * @return array{success: bool, prize_index: int, prize_type: string, prize_value: int, label: string, wpoint_balance: int, extra_spin: bool, free_spin: bool, spins_today: int, spins_remaining: int, milestone_bonus: ?int}
     */
    public function spin(User $user, ?int $serverId = null): array
    {
        $baseCost = (int) config('economy.spin_cost', 10);
        $prizes = config('economy.spin_prizes', []);
        $dailyLimit = (int) config('economy.spin_daily_limit', 20);

        if (empty($prizes)) {
            throw new Exception('Cấu hình giải thưởng chưa sẵn sàng.');
        }

        $today = now()->toDateString();
        $spinsToday = SpinLog::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();

        if ($spinsToday >= $dailyLimit) {
            throw new Exception("Bạn đã hết lượt quay hôm nay ({$dailyLimit}/{$dailyLimit}).");
        }

        $freeKey = "spin_free_{$user->id}";
        $isFree = Cache::pull($freeKey) === true;

        return DB::transaction(function () use ($user, $serverId, $baseCost, $prizes, $isFree, $spinsToday, $dailyLimit, $today): array {
            $actualCost = $isFree ? 0 : $this->calculateDiminishingCost($baseCost, $spinsToday);

            if ($isFree) {
                $wallet = $user->webWallet;
                $balance = $wallet ? (int) $wallet->balance : 0;
            } else {
                $balance = $this->wpointService->debit($user, $actualCost, 'spin', [
                    'spin_number' => $spinsToday + 1,
                ]);
            }

            $prizeIndex = $this->weightedRoll($prizes);
            $prize = $prizes[$prizeIndex];
            $type = (string) $prize['type'];
            $value = (int) $prize['value'];
            $extraSpin = false;

            SpinLog::create([
                'user_id' => $user->id,
                'prize_index' => $prizeIndex,
                'prize_type' => $type,
                'prize_value' => $value,
                'wcoin_cost' => $actualCost,
            ]);

            switch ($type) {
                case 'wcoin':
                    $value = $this->clampWCoinReward($user->id, $value, $today);
                    if ($value > 0) {
                        $balance = $this->wpointService->credit($user, $value, 'spin_reward', null, [
                            'prize_index' => $prizeIndex,
                        ]);
                    }
                    break;

                case 'yuanbao':
                    if (! $serverId) {
                        throw new Exception('Bạn cần chọn server để nhận KC.');
                    }

                    $server = Server::find($serverId);
                    if (! $server || ! $server->db_connection_name) {
                        throw new Exception('Server không hợp lệ hoặc chưa cấu hình DB.');
                    }

                    $actor = DB::connection($server->db_connection_name)
                        ->table('actors')
                        ->where('accountname', $user->username)
                        ->first(['actorid']);

                    if (! $actor) {
                        throw new Exception('Bạn chưa có nhân vật trên server này. Hãy vào game tạo nhân vật trước.');
                    }

                    $actionUuid = Str::uuid()->toString();

                    $gmAction = GmAction::create([
                        'action_uuid' => $actionUuid,
                        'admin_id' => null,
                        'server_id' => $serverId,
                        'action_type' => 'charge_yuanbao',
                        'target_user' => $user->username,
                        'payload' => [
                            'account_name' => $user->username,
                            'amount' => $value,
                            'reason' => 'Vòng quay may mắn',
                        ],
                        'status' => 'pending',
                    ]);

                    ExecuteGmCommand::dispatch($gmAction->id);
                    break;

                case 'lose_turn':
                    break;

                case 'extra_turn':
                    $extraSpin = $this->canGrantExtraTurn($user->id);
                    if ($extraSpin) {
                        Cache::put("spin_free_{$user->id}", true, now()->addMinutes(5));
                    }
                    break;
            }

            $newSpinsToday = $spinsToday + 1;

            $milestoneBonus = $this->checkMilestoneBonus($user->id, $newSpinsToday, $today);
            if ($milestoneBonus > 0) {
                $balance = $this->wpointService->credit($user, $milestoneBonus, 'spin_milestone', null, [
                    'spins_today' => $newSpinsToday,
                ]);
            }

            return [
                'success' => true,
                'prize_index' => $prizeIndex,
                'prize_type' => $type,
                'prize_value' => $value,
                'label' => (string) $prize['label'],
                'wpoint_balance' => $balance,
                'extra_spin' => $extraSpin,
                'free_spin' => $isFree,
                'spins_today' => $newSpinsToday,
                'spins_remaining' => max(0, $dailyLimit - $newSpinsToday),
                'milestone_bonus' => $milestoneBonus > 0 ? $milestoneBonus : null,
            ];
        });
    }

    /**
     * Calculate diminishing spin cost after threshold.
     *
     * Formula: baseCost × multiplier^(spinsToday - threshold)
     */
    private function calculateDiminishingCost(int $baseCost, int $spinsToday): int
    {
        $threshold = (int) config('economy.spin_diminish_after', 10);
        $multiplier = (float) config('economy.spin_diminish_multiplier', 1.3);

        if ($spinsToday < $threshold) {
            return $baseCost;
        }

        return (int) ceil($baseCost * pow($multiplier, $spinsToday - $threshold));
    }

    /**
     * Clamp WCoin reward based on spin-specific daily cap.
     *
     * Tracks WPoint earned today from spin_reward + spin_milestone transactions.
     * Returns clamped value (may be 0 if cap reached).
     */
    private function clampWCoinReward(int $userId, int $value, string $today): int
    {
        $spinCap = (int) config('economy.wcoin_spin_reward_cap', 55);

        $earnedToday = (int) WPointTransaction::where('user_id', $userId)
            ->whereIn('type', ['spin_reward', 'spin_milestone'])
            ->whereDate('created_at', $today)
            ->sum('amount');

        $remaining = max(0, $spinCap - $earnedToday);

        return min($value, $remaining);
    }

    /**
     * Check and award milestone bonus for reaching spin thresholds.
     *
     * Milestones are randomized within a range for engagement.
     * Subject to spin reward cap.
     */
    private function checkMilestoneBonus(int $userId, int $spinsToday, string $today): int
    {
        $milestones = config('economy.spin_milestones', []);

        if (! isset($milestones[$spinsToday])) {
            return 0;
        }

        $range = $milestones[$spinsToday];
        $bonus = random_int((int) $range[0], (int) $range[1]);

        $spinCap = (int) config('economy.wcoin_spin_reward_cap', 55);
        $earnedToday = (int) WPointTransaction::where('user_id', $userId)
            ->whereIn('type', ['spin_reward', 'spin_milestone'])
            ->whereDate('created_at', $today)
            ->sum('amount');

        $remaining = max(0, $spinCap - $earnedToday);

        return min($bonus, $remaining);
    }

    /**
     * Check if extra_turn can be granted (chain limit).
     *
     * Counts consecutive free spins in recent spin logs.
     * Prevents infinite extra_turn chains.
     */
    private function canGrantExtraTurn(int $userId): bool
    {
        $maxChain = (int) config('economy.spin_extra_turn_max_chain', 2);

        $recentFreeSpins = SpinLog::where('user_id', $userId)
            ->where('wcoin_cost', 0)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        return $recentFreeSpins < $maxChain;
    }

    /**
     * Weighted random roll from prize table.
     *
     * @param  array<int, array{type: string, value: int, weight: int|float, label: string}>  $prizes
     */
    private function weightedRoll(array $prizes): int
    {
        $totalWeight = array_sum(array_column($prizes, 'weight'));
        $roll = mt_rand(1, (int) ($totalWeight * 100)) / 100;

        $cumulative = 0;
        foreach ($prizes as $index => $prize) {
            $cumulative += $prize['weight'];
            if ($roll <= $cumulative) {
                return $index;
            }
        }

        return count($prizes) - 1;
    }
}
