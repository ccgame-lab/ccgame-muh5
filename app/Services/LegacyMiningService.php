<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiamondClaimLog;
use App\Models\DiamondDailyLog;
use App\Models\DiamondWallet;
use App\Models\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Legacy mining — simplified maintenance-based idle KC faucet.
 *
 * One user = one mining state. No multiple machines, upgrades, ascension, or
 * complex shops. Core loop: claim → efficiency decays → maintain to restore
 * → optional timed boost for supporters.
 *
 * Formula:
 *   effective_rate = base_rate_per_hour × efficiency × boost_multiplier × legacy_power_multiplier
 *   daily_cap      = base_daily_cap × cap_multiplier
 */
final class LegacyMiningService
{
    /**
     * Fetch or create the user's mining state row.
     */
    private function getState(User $user): DiamondWallet
    {
        return DiamondWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance'          => 0,
                'lifetime_mined'   => 0,
                'lifetime_spent'   => 0,
                'ascension_level'  => 0,
                'boost_multiplier' => 1.0,
                'cap_multiplier'   => 1.0,
            ]
        );
    }

    /**
     * Compute legacy power bonus from old mining investments.
     *
     * Scans existing DiamondMachine rows + ascension_level for users who
     * upgraded/ascended/unlocked before the simplification. Bonus only
     * affects rate, never daily cap.
     *
     * Gracefully returns 0.0 if table/relation is missing.
     *
     * @return array{bonus: float, multiplier: float, breakdown: array}
     */
    private function computeLegacyPowerBonus(User $user): array
    {
        $cfg = config('economy.legacy_mining.legacy_power', []);
        if (! ($cfg['enabled'] ?? true)) {
            return ['bonus' => 0.0, 'multiplier' => 1.0, 'breakdown' => []];
        }

        $maxBonus = (float) ($cfg['max_bonus'] ?? 0.50);
        $spdBonus = (float) ($cfg['speed_level_bonus'] ?? 0.03);
        $capBonus = (float) ($cfg['capacity_level_bonus'] ?? 0.01);
        $ascBonus = (float) ($cfg['ascension_level_bonus'] ?? 0.05);
        $machBonus = (float) ($cfg['extra_machine_bonus'] ?? 0.05);

        $breakdown = [
            'speed' => 0.0,
            'capacity' => 0.0,
            'ascension' => 0.0,
            'machines' => 0.0,
        ];

        try {
            $machines = $user->machines;
            $machineCount = $machines->count();

            foreach ($machines as $m) {
                $breakdown['speed'] += (float) max(0, ($m->speed_level - 1)) * $spdBonus;
                $breakdown['capacity'] += (float) max(0, ($m->storage_level - 1)) * $capBonus;
            }

            // Extra machines beyond first
            if ($machineCount > 1) {
                $breakdown['machines'] = ($machineCount - 1) * $machBonus;
            }

            $wallet = $this->getState($user);
            $ascLevel = (int) ($wallet->ascension_level ?? 0);
            $breakdown['ascension'] = $ascLevel * $ascBonus;
        } catch (\Throwable) {
            // Table/relation missing → bonus = 0, no crash
            return ['bonus' => 0.0, 'multiplier' => 1.0, 'breakdown' => []];
        }

        $total = $breakdown['speed'] + $breakdown['capacity'] + $breakdown['ascension'] + $breakdown['machines'];
        $total = min($total, $maxBonus);

        return [
            'bonus'      => round($total, 4),
            'multiplier' => round(1 + $total, 4),
            'breakdown'  => $breakdown,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return the current mining quote: rate, efficiency, cap, boost info.
     *
     * Read-only. Client never sends rate/amount/multiplier.
     *
     * @return array{rate_per_hour: int, efficiency: float, boost_multiplier: float, daily_cap: int, cap_multiplier: float, legacy_power_bonus: float, legacy_power_multiplier: float, boost_until: ?string, cap_until: ?string, last_maintained_at: ?string}
     */
    public function quote(User $user): array
    {
        $state = $this->getState($user);

        $efficiency     = $this->efficiency($state);
        $boost          = $this->activeBoost($state);
        $capMultiplier  = $this->activeCapMultiplier($state);
        $legacyPower    = $this->computeLegacyPowerBonus($user);

        $rate = (int) floor(
            config('economy.legacy_mining.base_rate_per_hour', 20000)
            * $efficiency
            * $boost
            * $legacyPower['multiplier']
        );

        $dailyCap = (int) floor(
            config('economy.legacy_mining.base_daily_cap', 300000)
            * $capMultiplier
        );

        return [
            'rate_per_hour'           => $rate,
            'efficiency'              => round($efficiency, 3),
            'boost_multiplier'        => round($boost, 2),
            'daily_cap'               => $dailyCap,
            'cap_multiplier'          => round($capMultiplier, 2),
            'legacy_power_bonus'      => $legacyPower['bonus'],
            'legacy_power_multiplier' => $legacyPower['multiplier'],
            'boost_until'             => $state->boost_until?->toIso8601String(),
            'cap_until'               => $state->cap_until?->toIso8601String(),
            'last_maintained_at'      => $state->last_maintained_at?->toIso8601String(),
        ];
    }

    /**
     * Maintain the machine — reset efficiency to 100%.
     *
     * Subject to cooldown (default 6h).
     *
     * @return array{rate_per_hour: int, efficiency: float, ...}
     *
     * @throws Exception When cooldown not expired
     */
    public function maintain(User $user): array
    {
        $state = $this->getState($user);
        $cooldownHours = config('economy.legacy_mining.maintenance_cooldown_hours', 6);

        if ($state->last_maintained_at && $state->last_maintained_at->gt(now()->subHours((int) $cooldownHours))) {
            $remaining = (int) ceil($state->last_maintained_at->addHours((int) $cooldownHours)->diffInMinutes(now()));
            throw new Exception("Bảo trì đang hồi phục. Còn {$remaining} phút nữa.");
        }

        $state->last_maintained_at = now();
        $state->save();

        return $this->quote($user);
    }

    /**
     * Claim accumulated KC. Subject to daily cap and efficiency/boost.
     *
     * Writes claim log with economy snapshots for audit.
     * Credits KC via DiamondMiningJob to game server.
     * Updates last_claimed_at and daily log.
     *
     * @param  int  $serverId  Game server to credit KC to
     * @return array{amount: int, quote: array, daily_remaining: int}
     *
     * @throws Exception When mining is disabled or claim yields zero
     */
    public function claim(User $user, int $serverId): array
    {
        if (! config('economy.legacy_mining.enabled', true)) {
            throw new Exception('Mining is temporarily unavailable.');
        }

        return DB::transaction(function () use ($user, $serverId) {
            $state = $this->getState($user);

            // Lock row to prevent parallel claims
            $state = DiamondWallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $q = $this->quote($user);

            $now = now();
            $elapsedHours = max(0, $state->last_claimed_at instanceof Carbon
                ? $state->last_claimed_at->diffInSeconds($now) / 3600
                : 0);

            $rawAmount = (int) floor($elapsedHours * $q['rate_per_hour']);

            // Daily cap
            $today = $now->toDateString();
            $todayClaimed = (int) DiamondClaimLog::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->sum('amount_claimed');

            $amount = min($rawAmount, max(0, $q['daily_cap'] - $todayClaimed));

            if ($amount <= 0) {
                // Still update last_claimed_at so time doesn't accumulate forever
                $state->last_claimed_at = $now;
                $state->save();

                return [
                    'amount'          => 0,
                    'quote'           => $q,
                    'daily_remaining' => max(0, $q['daily_cap'] - $todayClaimed),
                ];
            }

            // Credit KC to game server via DiamondMiningJob
            $server = \App\Models\Server::find($serverId);
            if ($server && $server->db_connection_name) {
                $actionUuid = \Illuminate\Support\Str::uuid();

                \App\Models\GmAction::create([
                    'action_uuid' => $actionUuid,
                    'admin_id'    => null,
                    'server_id'   => $server->id,
                    'action_type' => 'charge_currency',
                    'target_user' => (string) $user->id,
                    'payload'     => [
                        'amount'       => $amount,
                        'account_name' => $user->username,
                        'server_name'  => $server->name,
                    ],
                    'status'      => 'pending',
                ]);

                \App\Jobs\DiamondMiningJob::dispatch(
                    $server,
                    $user->username,
                    $amount,
                    $actionUuid
                );
            }

            // Write claim log with economy snapshots
            DiamondClaimLog::create([
                'user_id'              => $user->id,
                'machine_index'        => 0, // single machine
                'amount_claimed'       => $amount,
                'production_seconds'   => (int) ($elapsedHours * 3600),
                'machine_level'        => 1,
                'speed_level'          => 1,
                'storage_level'        => 1,
                'efficiency_level'     => 1,
                'machine_snapshot'     => [
                    'elapsed_hours'            => round($elapsedHours, 4),
                    'raw_amount'               => $rawAmount,
                    'today_claimed'            => $todayClaimed + $amount,
                    'legacy_power_bonus'       => $q['legacy_power_bonus'],
                    'legacy_power_multiplier'  => $q['legacy_power_multiplier'],
                ],
                'rate_snapshot'        => $q['rate_per_hour'],
                'cap_snapshot'         => $q['daily_cap'],
                'efficiency_snapshot'  => $q['efficiency'],
                'boost_snapshot'       => $q['boost_multiplier'],
                'ip'                   => request()->ip(),
                'user_agent'           => request()->userAgent(),
            ]);

            // Update daily tracking
            DiamondDailyLog::firstOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                ['daily_diamond_mined' => 0]
            )->increment('daily_diamond_mined', $amount);

            // Update wallet
            $state->increment('balance', $amount);
            $state->increment('lifetime_mined', $amount);
            $state->last_claimed_at = $now;
            $state->save();

            return [
                'amount'          => $amount,
                'quote'           => $q,
                'daily_remaining' => $q['daily_cap'] - ($todayClaimed + $amount),
            ];
        });
    }

    /**
     * Apply a boost tier to the user's mining state.
     *
     * Tiers: small, medium, whale — defined in economy.legacy_mining.boosts.
     * Stacking: overwrites any existing boost; does not extend.
     *
     * @throws Exception When tier is invalid
     */
    public function applyBoost(User $user, string $tier): void
    {
        $boosts = config('economy.legacy_mining.boosts', []);
        if (! isset($boosts[$tier])) {
            throw new Exception("Invalid boost tier: {$tier}");
        }

        $cfg = $boosts[$tier];

        DB::transaction(function () use ($user, $tier, $cfg) {
            $state = DiamondWallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $state->boost_multiplier = (float) $cfg['multiplier'];
            $state->boost_until      = now()->addHours((int) $cfg['hours']);
            $state->cap_multiplier   = (float) $cfg['cap_multiplier'];
            $state->cap_until        = now()->addHours((int) $cfg['hours']);
            $state->save();
        });
    }

    /**
     * Get today's claimed amount for a user.
     */
    public function claimedToday(User $user): int
    {
        return (int) DiamondClaimLog::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount_claimed');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calculate current efficiency based on time since last maintenance.
     *
     * Starts at 1.0 after maintenance, decays linearly by decay_per_hour,
     * never below min_efficiency.
     */
    private function efficiency(DiamondWallet $state): float
    {
        if (! $state->last_maintained_at) {
            // Never maintained — start at floor
            return config('economy.legacy_mining.min_efficiency', 0.35);
        }

        $hoursSince = $state->last_maintained_at->diffInHours(now());
        $decay = config('economy.legacy_mining.efficiency_decay_per_hour', 0.03);
        $min   = config('economy.legacy_mining.min_efficiency', 0.35);

        return max($min, 1 - ($hoursSince * $decay));
    }

    /**
     * Active boost multiplier (1.0 when none or expired).
     */
    private function activeBoost(DiamondWallet $state): float
    {
        if ($state->boost_until && $state->boost_until > now()) {
            return (float) ($state->boost_multiplier ?: 1.0);
        }

        return 1.0;
    }

    /**
     * Active cap multiplier (1.0 when none or expired).
     */
    private function activeCapMultiplier(DiamondWallet $state): float
    {
        if ($state->cap_until && $state->cap_until > now()) {
            return (float) ($state->cap_multiplier ?: 1.0);
        }

        return 1.0;
    }
}
