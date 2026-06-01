<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DiamondMiningJob;
use App\Models\DiamondBoost;
use App\Models\DiamondClaimLog;
use App\Models\DiamondDailyLog;
use App\Models\DiamondMachine;
use App\Models\DiamondUpgrade;
use App\Models\DiamondWallet;
use App\Models\GmAction;
use App\Models\Server;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiamondMiningService
{
    public function __construct(
        private WPointService $wpointService
    ) {}

    /**
     * Get the machine's current unclaimed Diamond production.
     *
     * Clamp order (ChatGPT Issue #3):
     * elapsed → offline cap → production → storage cap
     *
     * Daily cap is applied separately at claim time.
     *
     * @return array{produced: float, rate_per_hour: float, max_capacity: float, is_full: bool, production_seconds: int}
     */
    public function calculateUnclaimedDiamond(DiamondMachine $machine, User $user): array
    {
        $now = now();
        /** @var Carbon $lastClaimAt */
        $lastClaimAt = $machine->last_claim_at;
        $elapsedSeconds = max(0, $now->getTimestamp() - $lastClaimAt->getTimestamp());

        // ─── CLAMP 1: Offline cap (+ S1 offline boost) ───
        $maxOfflineSeconds = config('economy.max_offline_hours', 48) * 3600;
        $s1OfflineBonus = app(S1ShopService::class)->getOfflineBonus($user);
        $maxOfflineSeconds += (int) ($s1OfflineBonus * 3600);
        $elapsedSeconds = min($elapsedSeconds, $maxOfflineSeconds);

        // ─── Production rate calculation ───
        $rate = $machine->base_rate;
        $rate *= $this->getSpeedMultiplier($machine->speed_level);

        /** @var DiamondWallet|null $wallet */
        $wallet = $user->wallet;
        if ($wallet) {
            $rate *= $this->getAscensionMultiplier($wallet->ascension_level);
        }

        // Apply all active DiamondBoosts (multi-slot support)
        $activeBoosts = $user->boosts()->where('expires_at', '>', $now)->get();
        foreach ($activeBoosts as $activeBoost) {
            $rate *= $activeBoost->multiplier;
        }
        // Apply S1 regen boost (additive percentage bonus)
        $s1RegenBonus = app(S1ShopService::class)->getRegenMultiplierBonus($user);
        if ($s1RegenBonus > 0) {
            $rate *= (1 + $s1RegenBonus);
        }

        // V5 Impact: Single Source of Truth Global World Buff
        $worldBuffData = Cache::get('world_buff:global');
        if (is_array($worldBuffData) && isset($worldBuffData['bonus'])) {
            $rate *= (1 + (float) $worldBuffData['bonus']);
        }

        $ascensionLevel = $wallet->ascension_level ?? 0;
        $maxMultiplier = config('economy.max_total_multiplier_base', 5.0)
            + $ascensionLevel * config('economy.max_total_multiplier_per_ascension', 0.5);
        $rate = min($rate, $machine->base_rate * $maxMultiplier);

        $produced = $rate * ($elapsedSeconds / 3600);

        // ─── CLAMP 2: Storage cap ───
        $maxCapacity = $machine->capacity * $this->getStorageMultiplier($machine->storage_level);
        $cappedProduction = min($produced, $maxCapacity);

        return [
            'produced' => round($cappedProduction),
            'rate_per_hour' => round($rate),
            'max_capacity' => $maxCapacity,
            'is_full' => $cappedProduction >= $maxCapacity,
            'production_seconds' => $elapsedSeconds,
        ];
    }

    /**
     * Process a claim request for a specific machine.
     *
     * Clamp order (ChatGPT Issue #3):
     * elapsed → offline cap → production → storage cap → daily cap
     *
     * Uses DiamondWallet (Issue #1) instead of users.diamond_balance.
     * Logs full machine snapshot (Issue #2) for economy debugging.
     *
     * @return array{success: bool, amount: int, lucky_drop: ?string, wallet_balance: int, machine: array{stored: int, capacity: float, rate_per_hour: float}}
     */
    public function claim(User $user, int $machineIndex, string $claimToken, int $serverId): array
    {
        $tokenCacheKey = "diamond_claim_token_{$user->id}_{$claimToken}";
        if (! Cache::pull($tokenCacheKey)) {
            Log::info('diamond_claim_rejected', ['user_id' => $user->id, 'reason' => 'invalid_token']);

            throw new Exception('Token nhận thưởng không hợp lệ hoặc đã hết hạn.');
        }

        $lock = Cache::lock("diamond_claim:{$user->id}", 5);

        try {
            $lock->block(3);
        } catch (LockTimeoutException) {
            Log::info('diamond_claim_rejected', ['user_id' => $user->id, 'reason' => 'lock_contention']);

            throw new Exception('Đang xử lý nhận thưởng. Vui lòng thử lại sau.');
        }

        try {
            return DB::transaction(function () use ($user, $machineIndex, $serverId) {
                // Lock machine row — prevents parallel claim exploits
                $machine = DiamondMachine::where('user_id', $user->id)
                    ->where('machine_index', $machineIndex)
                    ->lockForUpdate()
                    ->firstOrFail();

                $now = now();
                /** @var Carbon $machineLastClaim */
                $machineLastClaim = $machine->last_claim_at;
                $elapsedSeconds = max(0, $now->getTimestamp() - $machineLastClaim->getTimestamp());
                $minInterval = config('economy.min_claim_interval', 60);

                if ($elapsedSeconds < $minInterval) {
                    Log::info('diamond_claim_rejected', ['user_id' => $user->id, 'reason' => 'min_interval', 'elapsed' => $elapsedSeconds]);

                    throw new Exception('Chưa đến thời gian nhận thưởng tiếp theo.');
                }

                // ─── Server-side calculation (clamp 1+2: offline + storage) ───
                $calc = $this->calculateUnclaimedDiamond($machine, $user);
                $amountToClaim = (int) $calc['produced'];

                if ($amountToClaim <= 0) {
                    return [
                        'success' => false,
                        'amount' => 0,
                        'lucky_drop' => null,
                        'wallet_balance' => 0,
                        'machine' => [
                            'stored' => 0,
                            'capacity' => $calc['max_capacity'],
                            'rate_per_hour' => $calc['rate_per_hour'],
                        ],
                    ];
                }

                // Efficiency bonus on claim
                $efficiencyBonus = $this->getEfficiencyBonus($machine->efficiency_level);
                $amountToClaim += (int) round($amountToClaim * $efficiencyBonus);

                // ─── CLAMP 3: Daily cap ───
                $today = $now->toDateString();
                $dailyCaps = config('economy.max_diamond_per_day', []);
                $wallet = DiamondWallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'lifetime_mined' => 0, 'lifetime_spent' => 0]
                );
                $ascLevel = $wallet->ascension_level ?? 0;
                $maxPerDay = is_array($dailyCaps)
                    ? ($dailyCaps[$ascLevel] ?? end($dailyCaps) ?: 2_800_000)
                    : (int) $dailyCaps;
                // Apply S1 daily cap bonus
                $s1CapBonus = app(S1ShopService::class)->getDailyCapBonus($user);
                $maxPerDay += (int) $s1CapBonus;
                $dailyLog = DiamondDailyLog::firstOrCreate(
                    ['user_id' => $user->id, 'date' => $today],
                    ['daily_diamond_mined' => 0]
                );

                if ($dailyLog->daily_diamond_mined >= $maxPerDay) {
                    Log::info('diamond_claim_cap_hit', ['user_id' => $user->id, 'daily_mined' => $dailyLog->daily_diamond_mined, 'cap' => $maxPerDay]);

                    throw new Exception('Bạn đã đạt giới hạn nhận Kim Cương trong ngày.');
                }

                $remainingCap = $maxPerDay - $dailyLog->daily_diamond_mined;
                $originalAmount = $amountToClaim;
                $amountToClaim = min($amountToClaim, $remainingCap);

                // Lucky drop (deterministic seeded RNG — Issue #2.2, entropy fix R4)
                $seedInput = "{$user->id}:{$machineIndex}:{$machine->last_claim_at}:{$now->getTimestampMs()}";
                $dropSeed = hash('xxh128', $seedInput);
                $minProduction = config('economy.lucky_drop_min_production', 10000);
                $fillRatio = 1.0;
                if (config('economy.lucky_drop_scale_by_fill', false)) {
                    $maxCapacity = $machine->capacity * $this->getStorageMultiplier($machine->storage_level);
                    $fillRatio = $maxCapacity > 0 ? min(1.0, $calc['produced'] / $maxCapacity) : 0;
                }
                $luckyDrop = $amountToClaim >= $minProduction
                    ? $this->rollLuckyDrop($dropSeed, (float) $fillRatio)
                    : null;

                // ─── Fix Bug 1: Void Wipe (Hoàn trả lại thời gian chưa claim hết) ───
                if ($amountToClaim < $originalAmount && $originalAmount > 0) {
                    $consumedRatio = $amountToClaim / $originalAmount;
                    $retainedSeconds = $calc['production_seconds'] * (1 - $consumedRatio);
                    $machine->last_claim_at = $now->copy()->subSeconds((int) $retainedSeconds);
                } else {
                    $machine->last_claim_at = $now;
                }
                $machine->save();

                // ─── Update daily log ───
                $dailyLog->increment('daily_diamond_mined', $amountToClaim);

                // ─── Update wallet (Issue #1: separate table) ───
                $wallet->increment('balance', $amountToClaim);
                $wallet->increment('lifetime_mined', $amountToClaim);

                // ─── Audit log with full snapshot (Issue #2) ───
                DiamondClaimLog::create([
                    'user_id' => $user->id,
                    'machine_index' => $machineIndex,
                    'amount_claimed' => $amountToClaim,
                    'production_seconds' => $calc['production_seconds'],
                    'machine_level' => $machine->level ?? 1,
                    'speed_level' => $machine->speed_level,
                    'storage_level' => $machine->storage_level,
                    'efficiency_level' => $machine->efficiency_level,
                    'machine_snapshot' => [
                        'base_rate' => $machine->base_rate,
                        'capacity' => $machine->capacity,
                        'rate_per_hour' => $calc['rate_per_hour'],
                        'max_capacity' => $calc['max_capacity'],
                        'efficiency_bonus' => $efficiencyBonus,
                        'daily_used' => $dailyLog->daily_diamond_mined,
                        'seed_input' => $seedInput,
                    ],
                    'is_lucky_drop' => $luckyDrop !== null,
                    'drop_item_id' => $luckyDrop,
                    'drop_seed' => $dropSeed,
                    'drop_table_version' => config('economy.lucky_drop_table_version', '1.0'),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // ─── Dispatch Job via GmAction (Game Server Sync) ───
                $server = Server::where('id', $serverId)
                    ->where('status', '!=', 'MAINTENANCE')
                    ->firstOrFail();
                if ($server->db_connection_name) {
                    // Tạo request ID chống trùng lặp
                    $actionUuid = (string) Str::uuid();

                    GmAction::create([
                        'action_uuid' => $actionUuid,
                        'admin_id' => null, // System action
                        'server_id' => $server->id,
                        'action_type' => 'charge_currency',
                        'target_user' => (string) $user->id,
                        'payload' => [
                            'machine_index' => $machineIndex,
                            'amount' => $amountToClaim,
                            'account_name' => $user->username,
                            'server_name' => $server->name,
                        ],
                        'status' => 'pending',
                    ]);

                    // Đẩy worker thực thi nạp sang game server
                    DiamondMiningJob::dispatch(
                        $server,
                        $user->username, // accountName
                        $amountToClaim,
                        $actionUuid
                    );
                }

                Log::info('diamond_claim_success', [
                    'user_id' => $user->id,
                    'amount' => $amountToClaim,
                    'server_id' => $serverId,
                    'daily_used' => $dailyLog->daily_diamond_mined,
                    'daily_cap' => $maxPerDay,
                ]);

                return [
                    'success' => true,
                    'amount' => $amountToClaim,
                    'lucky_drop' => $luckyDrop,
                    'wallet_balance' => (int) $wallet->balance,
                    'machine' => [
                        'stored' => 0,
                        'capacity' => $calc['max_capacity'],
                        'rate_per_hour' => $calc['rate_per_hour'],
                    ],
                ];
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Handles machine upgrades.
     * Uses lockForUpdate() to prevent race condition with claim (Issue #8).
     */
    public function upgradeMachine(User $user, int $machineIndex, string $upgradeType): bool
    {
        return DB::transaction(function () use ($user, $machineIndex, $upgradeType) {
            $machine = DiamondMachine::where('user_id', $user->id)
                ->where('machine_index', $machineIndex)
                ->lockForUpdate()
                ->firstOrFail();

            // ─── Fix Bug 2: Retroactive Boost (Bắt buộc claim trước khi up) ───
            $calc = $this->calculateUnclaimedDiamond($machine, $user);
            if ($calc['production_seconds'] >= 60) {
                // Bypass if they reached daily cap (otherwise soft-locked)
                $dailyLog = DiamondDailyLog::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();
                $wallet = $user->wallet;
                $ascLevel = $wallet->ascension_level ?? 0;
                $dailyCaps = config('economy.max_diamond_per_day', []);
                $maxPerDay = is_array($dailyCaps) ? ($dailyCaps[$ascLevel] ?? end($dailyCaps) ?: 2800000) : (int) $dailyCaps;

                if (! $dailyLog || $dailyLog->daily_diamond_mined < $maxPerDay) {
                    throw new Exception('Vui lòng Nhận Kim Cương trước. (Chỉ có thể nâng cấp trong vòng 1 phút kể từ lần nhận cuối cùng)');
                }
            }

            $currentLevel = (int) $machine->{"{$upgradeType}_level"};
            $maxLevels = [
                'speed' => count(config('economy.speed_multipliers', [])),
                'storage' => count(config('economy.storage_multipliers', [])),
                'efficiency' => count(config('economy.efficiency_bonuses', [])),
            ];

            if ($currentLevel >= $maxLevels[$upgradeType]) {
                throw new Exception('Maximum level reached.');
            }

            $cost = $this->calculateWPointUpgradeCost($upgradeType, $currentLevel + 1);

            $this->wpointService->debit($user, $cost, 'upgrade', [
                'machine_index' => $machineIndex,
                'upgrade_type' => $upgradeType,
                'from_level' => $currentLevel,
                'to_level' => $currentLevel + 1,
            ]);

            $machine->increment("{$upgradeType}_level");

            DiamondUpgrade::create([
                'user_id' => $user->id,
                'machine_index' => $machineIndex,
                'upgrade_type' => $upgradeType,
                'from_level' => $currentLevel,
                'to_level' => $currentLevel + 1,
                'cost' => $cost,
            ]);

            return true;
        });
    }

    /**
     * Unlock a new machine slot.
     *
     * @throws Exception When invalid tier, already unlocked, insufficient WP, or ascension too low
     */
    public function unlockMachine(User $user, int $machineIndex): bool
    {
        $wpointCosts = config('economy.wpoint_machine_costs', []);
        if (! isset($wpointCosts[$machineIndex])) {
            throw new Exception('Invalid machine tier.');
        }

        $tierConfig = $wpointCosts[$machineIndex];
        $requiredAscension = $tierConfig['min_ascension'] ?? 0;

        if ($requiredAscension > 0) {
            $wallet = $user->wallet;
            $currentAscension = $wallet->ascension_level ?? 0;
            if ($currentAscension < $requiredAscension) {
                throw new Exception("Cần Ascension Lv {$requiredAscension} để mở khóa máy này.");
            }
        }

        return DB::transaction(function () use ($user, $machineIndex, $tierConfig) {
            $exists = DiamondMachine::where('user_id', $user->id)
                ->where('machine_index', $machineIndex)
                ->exists();

            if ($exists) {
                throw new Exception('Machine already unlocked.');
            }

            $cost = $tierConfig['wp'] ?? 0;

            if ($cost > 0) {
                $this->wpointService->debit($user, $cost, 'unlock', [
                    'machine_index' => $machineIndex,
                ]);
            }

            DiamondMachine::create([
                'user_id' => $user->id,
                'machine_index' => $machineIndex,
                'base_rate' => $this->getBaseRate($machineIndex),
                'capacity' => $this->getBaseCapacity($machineIndex),
                'storage_limit' => $this->getBaseCapacity($machineIndex),
                'last_claim_at' => now(),
                'unlocked_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Handle Player Ascension (Prestige Reset).
     *
     * @return array{success: bool, new_level: int, multiplier: float}
     *
     * @throws Exception When max level, insufficient WP, or milestone not met
     */
    public function ascend(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $wallet = DiamondWallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $nextLevel = $wallet->ascension_level + 1;

            $costs = config('economy.ascension_costs', []);
            $maxLevel = count(config('economy.ascension_multipliers', [])) - 1;

            if ($nextLevel > $maxLevel) {
                throw new Exception('Đã đạt cấp Ascension tối đa.');
            }

            $tierConfig = $costs[$nextLevel] ?? null;
            if (! $tierConfig) {
                throw new Exception('Cấp Ascension không hợp lệ.');
            }

            if ($wallet->lifetime_mined < $tierConfig['min_lifetime_mined']) {
                throw new Exception('Chưa đạt mốc khai thác tối thiểu.');
            }

            $this->wpointService->debit($user, $tierConfig['wp'], 'ascend');

            $wallet->increment('ascension_level');

            // Partial reset: retain ceil(level × retention%) of each upgrade
            $retention = config('economy.ascension_upgrade_retention', 0.5);
            $machines = DiamondMachine::where('user_id', $user->id)->get();
            $totalWpSpentOnUpgrades = 0;

            foreach ($machines as $m) {
                // ─── Fix Bug 3: Ascension Wipe (Bắt buộc claim trước khi chuyển sinh) ───
                $calc = $this->calculateUnclaimedDiamond($m, $user);
                if ($calc['production_seconds'] >= 60) {
                    $dailyLog = DiamondDailyLog::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();
                    $oldAscLevel = $nextLevel - 1;
                    $dailyCaps = config('economy.max_diamond_per_day', []);
                    $maxPerDay = is_array($dailyCaps) ? ($dailyCaps[$oldAscLevel] ?? end($dailyCaps) ?: 2800000) : (int) $dailyCaps;

                    if (! $dailyLog || $dailyLog->daily_diamond_mined < $maxPerDay) {
                        throw new Exception('Vui lòng Nhận Kim Cương ở toàn bộ máy đào trước. (Chỉ có thể Chuyển Sinh trong vòng 1 phút kể từ lần nhận cuối cùng)');
                    }
                }

                $totalWpSpentOnUpgrades += $this->estimateUpgradeWpSpent($m);
                $m->speed_level = (int) max(1, (int) ceil($m->speed_level * $retention));
                $m->storage_level = (int) max(1, (int) ceil($m->storage_level * $retention));
                $m->efficiency_level = (int) max(1, (int) ceil($m->efficiency_level * $retention));
                $m->last_claim_at = now();
                $m->save();
            }

            // WP refund: return a % of WP spent on upgrades
            $refundRate = config('economy.ascension_wp_refund_rate', 0.2);
            $refundAmount = (int) floor($totalWpSpentOnUpgrades * $refundRate);
            if ($refundAmount > 0) {
                $user->increment('wpoint', $refundAmount);
            }

            $newMultiplier = $this->getAscensionMultiplier($nextLevel);

            return [
                'success' => true,
                'new_level' => $nextLevel,
                'multiplier' => $newMultiplier,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Boost Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's currently active boost, if any.
     */
    public function getActiveBoost(User $user): ?DiamondBoost
    {
        return $user->boosts()->where('expires_at', '>', now())->first();
    }

    /**
     * Purchase and activate a boost tier for the user.
     *
     * Security fix — retroactive boost exploit:
     * All pending machine production is force-settled (silentClaimMachine) inside
     * a single atomic transaction BEFORE the DiamondBoost row is created.
     * This ensures the boost multiplier only applies to time elapsed after activation.
     *
     * @throws Exception When tier is invalid, slots full, or insufficient WPoint
     */
    public function applyBoost(User $user, int $tierIndex): DiamondBoost
    {
        $boosts = config('economy.boosts', []);
        if (! isset($boosts[$tierIndex])) {
            throw new Exception('Invalid boost tier.');
        }

        $tier = $boosts[$tierIndex];

        return DB::transaction(function () use ($user, $tier, $tierIndex): DiamondBoost {
            // 1. Lock wallet row
            $wallet = DiamondWallet::where('user_id', $user->id)->lockForUpdate()->first();
            
            if (! $wallet) {
                throw new Exception('Wallet not found.');
            }

            $maxSlots = $wallet->max_active_boosts ?? 1;
            $activeCount = $user->boosts()->where('expires_at', '>', now())->count();
            if ($activeCount >= $maxSlots) {
                throw new Exception('Đã đạt giới hạn boost đang hoạt động.');
            }

            // 2. Debit WPoint — fail fast before touching machine state
            $this->wpointService->debit($user, $tier['wp_cost'], 'boost', [
                'tier_index' => $tierIndex,
                'label' => $tier['label'],
            ]);

            // 3. Force-settle all pending production BEFORE multiplier takes effect
            $machines = DiamondMachine::where('user_id', $user->id)->lockForUpdate()->get();
            foreach ($machines as $machine) {
                $this->silentClaimMachine($machine, $wallet, $user, $tierIndex);
            }

            // 4. Persist accumulated wallet balance from silent claims
            $wallet->save();

            // 5. Activate boost — last_claim_at is NOW on all machines;
            //    multiplier only applies to time elapsed after this point
            return DiamondBoost::create([
                'user_id' => $user->id,
                'boost_type' => $tier['label'],
                'multiplier' => $tier['multiplier'],
                'expires_at' => now()->addHours($tier['hours']),
            ]);
        });
    }

    /**
     * Settle pending production for a single machine without regular claim side-effects.
     *
     * Called inside a parent DB::transaction (boost activation). No nested transaction.
     *
     * Rules:
     * - Applies same production math + efficiency bonus as regular claim.
     * - Respects daily cap: credits 0 when cap is hit but ALWAYS resets last_claim_at.
     * - Applies Void Wipe proportional time retention when daily cap truncates the amount.
     * - Does NOT enforce min_claim_interval, roll lucky drops, fire events, or dispatch GmAction.
     * - Wallet balance/lifetime_mined are mutated in-place; caller must call $wallet->save().
     *
     * @return int Amount credited to wallet (may be 0 if daily cap already hit)
     */
    private function silentClaimMachine(DiamondMachine $machine, DiamondWallet $wallet, User $user, int $tierIndex): int
    {
        $now = now();

        $calc = $this->calculateUnclaimedDiamond($machine, $user);
        $produced = (int) $calc['produced'];

        // Efficiency bonus — same math as regular claim
        $efficiencyBonus = $this->getEfficiencyBonus($machine->efficiency_level);
        $produced += (int) round($produced * $efficiencyBonus);

        // ─── Daily cap ───
        $today = $now->toDateString();
        $ascLevel = $wallet->ascension_level ?? 0;
        $dailyCaps = config('economy.max_diamond_per_day', []);
        $maxPerDay = is_array($dailyCaps)
            ? ($dailyCaps[$ascLevel] ?? end($dailyCaps) ?: 2_800_000)
            : (int) $dailyCaps;
        $s1CapBonus = app(S1ShopService::class)->getDailyCapBonus($user);
        $maxPerDay += (int) $s1CapBonus;

        $dailyLog = DiamondDailyLog::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['daily_diamond_mined' => 0]
        );

        $dailyRemaining = max(0, $maxPerDay - $dailyLog->daily_diamond_mined);
        $originalProduced = $produced;
        $amountToCredit = min($produced, $dailyRemaining);

        // ─── Void Wipe: retain proportional time when daily cap truncates production ───
        if ($amountToCredit < $originalProduced && $originalProduced > 0) {
            $consumedRatio = $amountToCredit / $originalProduced;
            $retainedSeconds = $calc['production_seconds'] * (1 - $consumedRatio);
            $machine->last_claim_at = $now->copy()->subSeconds((int) $retainedSeconds);
        } else {
            // Includes the case where $dailyRemaining === 0: last_claim_at is still reset
            $machine->last_claim_at = $now;
        }
        $machine->save();

        // ─── Credit wallet in-memory (caller saves after loop) ───
        if ($amountToCredit > 0) {
            $wallet->balance += $amountToCredit;
            $wallet->lifetime_mined += $amountToCredit;
            $dailyLog->increment('daily_diamond_mined', $amountToCredit);
        }

        // ─── Audit log ───
        DiamondClaimLog::create([
            'user_id' => $user->id,
            'machine_index' => $machine->machine_index,
            'amount_claimed' => $amountToCredit,
            'production_seconds' => $calc['production_seconds'],
            'machine_level' => $machine->level ?? 1,
            'speed_level' => $machine->speed_level,
            'storage_level' => $machine->storage_level,
            'efficiency_level' => $machine->efficiency_level,
            'machine_snapshot' => [
                'base_rate' => $machine->base_rate,
                'capacity' => $machine->capacity,
                'rate_per_hour' => $calc['rate_per_hour'],
                'max_capacity' => $calc['max_capacity'],
                'efficiency_bonus' => $efficiencyBonus,
                'daily_used' => $dailyLog->daily_diamond_mined,
                'daily_remaining_before_claim' => $dailyRemaining,
                'trigger' => 'silent_claim:boost_activation',
                'boost_tier_index' => $tierIndex,
            ],
            'is_lucky_drop' => false,
            'drop_item_id' => null,
            'drop_seed' => null,
            'drop_table_version' => config('economy.lucky_drop_table_version', '1.0'),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('diamond_silent_claim', [
            'user_id' => $user->id,
            'machine_index' => $machine->machine_index,
            'amount_credited' => $amountToCredit,
            'daily_remaining' => $dailyRemaining,
            'trigger' => 'boost_activation',
            'boost_tier_index' => $tierIndex,
        ]);

        return $amountToCredit;
    }

    /*
    |--------------------------------------------------------------------------
    | Economy Multipliers — all config-driven (Issue #7)
    |--------------------------------------------------------------------------
    */

    private function getSpeedMultiplier(int $level): float
    {
        return (float) (config('economy.speed_multipliers')[$level] ?? 1.0);
    }

    private function getStorageMultiplier(int $level): float
    {
        return (float) (config('economy.storage_multipliers')[$level] ?? 1.0);
    }

    private function getEfficiencyBonus(int $level): float
    {
        return (float) (config('economy.efficiency_bonuses')[$level] ?? 0.0);
    }

    private function getAscensionMultiplier(int $level): float
    {
        return (float) (config('economy.ascension_multipliers')[$level] ?? 1.0);
    }

    public function calculateWPointUpgradeCost(string $type, int $newLevel): int
    {
        $baseCosts = config('economy.wpoint_upgrade_base_costs', []);
        $base = $baseCosts[$type] ?? 50;
        $exponent = config('economy.wpoint_cost_exponent', 1.5);

        return (int) ceil($base * pow($newLevel, $exponent));
    }

    /**
     * Estimate total WP spent on upgrades for a machine (for refund calculation).
     */
    private function estimateUpgradeWpSpent(DiamondMachine $machine): int
    {
        $total = 0;
        $types = [
            'speed' => (int) $machine->speed_level, 
            'storage' => (int) $machine->storage_level, 
            'efficiency' => (int) $machine->efficiency_level
        ];

        foreach ($types as $type => $currentLevel) {
            for ($lv = 2; $lv <= $currentLevel; $lv++) {
                $total += $this->calculateWPointUpgradeCost($type, $lv);
            }
        }

        return $total;
    }

    private function getBaseRate(int $machineIndex): int
    {
        return (int) (config('economy.base_rates')[$machineIndex] ?? 100);
    }

    private function getBaseCapacity(int $machineIndex): int
    {
        return (int) (config('economy.base_capacities')[$machineIndex] ?? 200);
    }

    /**
     * Deterministic lucky drop using seeded RNG (Issue #2.2).
     *
     * Pattern: seed → RNG (not random() → seed).
     * Given the same seed, this method always returns the same result,
     * making drops fully replayable for economy audit.
     */
    private function rollLuckyDrop(string $seed, float $fillRatio = 1.0): ?string
    {
        $chance = config('economy.lucky_drop_chance', 0.05) * $fillRatio;

        $chanceRoll = hexdec(substr($seed, 0, 8)) % 10000;
        if ($chanceRoll >= ($chance * 10000)) {
            return null;
        }

        $table = config('economy.lucky_drop_table', []);
        $totalWeight = (int) array_sum(array_column($table, 'weight'));

        if ($totalWeight <= 0) {
            return null;
        }

        $itemRoll = (hexdec(substr($seed, 8, 8)) % $totalWeight) + 1;

        $cumulative = 0;
        foreach ($table as $entry) {
            $cumulative += $entry['weight'];
            if ($itemRoll <= $cumulative) {
                return (string) $entry['item'];
            }
        }

        return null;
    }
}
