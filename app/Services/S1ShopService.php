<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ExecuteGmCommand;
use App\Models\DiamondMachine;
use App\Models\DiamondWallet;
use App\Models\GmAction;
use App\Models\S1PlayerBoost;
use App\Models\S1ShopPurchase;
use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class S1ShopService
{
    public function __construct(
        private PointService $pointService,
        private GmApiService $gmApiService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check whether a user is eligible to access the S1 Legacy Shop.
     * Eligibility requires an active character on S1 (id=1).
     * Result is cached for 10 minutes to avoid repeated DB hits.
     */
    public function isEligible(User $user, Server $server): bool
    {
        if ($server->opened_at && $server->opened_at > now()->subDays(7)) {
            return false;
        }

        return (bool) Cache::remember("legacy_shop_eligible_{$user->id}_{$server->id}", 600, function () use ($user, $server): bool {
            if (empty($server->db_connection_name)) {
                return false;
            }

            return DB::connection($server->db_connection_name)
                ->table('actors')
                ->where('accountname', $user->username)
                ->exists();
        });
    }

    /**
     * Get the current week number of the server based on opened_at.
     */
    public function getServerWeek(Server $server): int
    {
        if (! $server->opened_at) {
            return 1;
        }

        $daysSinceOpen = (int) $server->opened_at->diffInDays(now());

        return max(1, (int) ceil(($daysSinceOpen + 1) / 7));
    }

    /**
     * Return config-driven items filtered by current server week.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableItems(Server $server): array
    {
        $currentWeek = $this->getServerWeek($server);
        $items = config('economy.s1_shop.items', []);

        return array_values(array_filter((array) $items, fn (array $item) => ($item['unlock_week'] ?? 1) <= $currentWeek));
    }

    /**
     * Get all items from config regardless of unlock status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllItems(): array
    {
        return (array) config('economy.s1_shop.items', []);
    }

    /**
     * Count how many times a user has purchased an item in the current period.
     */
    public function getPurchaseCountInPeriod(User $user, string $slug, string $limitType, Server $server): int
    {
        $periodKey = $this->buildPeriodKey($limitType);

        return S1ShopPurchase::where('user_id', $user->id)
            ->where('item_slug', $slug)
            ->where('server_id', $server->id)
            ->where('period_key', $periodKey)
            ->count();
    }

    /**
     * Main purchase entry point. Validates, charges, delivers, and logs.
     *
     * @param  array<string, mixed>  $itemConfig  A single item from config('economy.s1_shop.items')
     * @return array<string, mixed>
     *
     * @throws Exception On validation or delivery failure
     */
    public function purchase(User $user, Server $server, array $itemConfig): array
    {
        $slug = (string) $itemConfig['slug'];
        $limitType = (string) $itemConfig['limit_type'];
        $limitCount = (int) $itemConfig['limit_count'];
        $currency = (string) $itemConfig['currency'];
        $price = (int) $itemConfig['price'];

        // ── Guard: unlock week ──
        $currentWeek = $this->getServerWeek($server);
        if (($itemConfig['unlock_week'] ?? 1) > $currentWeek) {
            throw new Exception("Vật phẩm này chưa mở bán. Cần đến Tuần {$itemConfig['unlock_week']}.");
        }

        // ── Guard: period limit ──
        $purchasedCount = $this->getPurchaseCountInPeriod($user, $slug, $limitType, $server);
        if ($purchasedCount >= $limitCount) {
            $periodLabel = $limitType === 'daily' ? 'hôm nay' : 'tuần này';
            throw new Exception("Bạn đã mua vật phẩm này đủ {$limitCount} lần {$periodLabel}.");
        }

        // ── Guard: balance check ──
        $this->assertSufficientBalance($user, $server, $currency, $price);

        $reference = (string) Str::uuid();
        $periodKey = $this->buildPeriodKey($limitType);

        return DB::transaction(function () use ($user, $itemConfig, $server, $currency, $price, $slug, $periodKey, $reference): array {
            // ── Deduct currency ──
            $this->deductCurrency($user, $server, $currency, $price, $slug, $reference);

            // ── Deliver item ──
            $gmActionId = $this->deliver($user, $server, $itemConfig, $reference);

            // ── Log purchase ──
            S1ShopPurchase::create([
                'user_id' => $user->id,
                'item_slug' => $slug,
                'server_id' => $server->id,
                'reference' => $reference,
                'currency' => $currency,
                'amount_spent' => $price,
                'period_key' => $periodKey,
                'gm_action_id' => $gmActionId,
            ]);

            try {
                // Broadcast the purchase
                app(SocialEventService::class)->push([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'server_id' => $server->id,
                    'event_type' => 'purchase_item',
                    'template' => 'user_purchase_item',
                    'metadata' => [
                        'item_name' => $itemConfig['name'],
                        'price' => $price,
                        'currency' => $currency,
                    ],
                    'priority' => 1,
                ]);
            } catch (Exception) {
            }

            return [
                'success' => true,
                'message' => $this->buildSuccessMessage($itemConfig),
                'new_balance' => $this->getFreshBalance($user, $currency),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Period & Balance Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a period key string for limit tracking.
     * Weekly → "2026-W14", Daily → "2026-04-02"
     */
    public function buildPeriodKey(string $limitType): string
    {
        if ($limitType === 'weekly') {
            return now()->format('Y-\WW');
        }

        return now()->toDateString();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Validation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @throws Exception When balance is insufficient
     */
    private function assertSufficientBalance(User $user, Server $server, string $currency, int $price): void
    {
        if ($currency === 'points') {
            if ($user->points < $price) {
                throw new Exception("Không đủ POINT. Cần {$price} POINT, bạn có {$user->points} POINT.");
            }

            return;
        }

        // kc → check in-game yuanbao on server
        $connectionName = $this->gmApiService->resolveConnection($server);

        $actor = DB::connection($connectionName)
            ->table('actors')
            ->where('accountname', $user->username)
            ->first(['yuanbao']);

        if (! $actor) {
            throw new Exception('Không tìm thấy nhân vật trên server này. Vui lòng đăng nhập game ít nhất 1 lần.');
        }

        $yuanbao = (int) ($actor->yuanbao ?? 0);
        if ($yuanbao < $price) {
            $formatted = number_format($price);
            $balance = number_format($yuanbao);
            throw new Exception("Không đủ Kim Cương. Cần {$formatted} KC, bạn có {$balance} KC trên server này.");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Currency Deduction
    // ─────────────────────────────────────────────────────────────────────────

    private function deductCurrency(User $user, Server $server, string $currency, int $price, string $slug, string $reference): void
    {
        if ($currency === 'points') {
            $this->pointService->debit($user, $price, 'legacy_shop_purchase', [
                'item_slug' => $slug,
                'reference' => $reference,
            ]);

            return;
        }

        // kc → deduct in-game via GmApiService (safe for online + offline)
        $this->gmApiService->deductDiamond($server, $user->username, $price);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Delivery
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Route delivery based on delivery_type. Returns gm_action_id if applicable.
     *
     * @param  array<string, mixed>  $itemConfig
     */
    private function deliver(User $user, Server $server, array $itemConfig, string $reference): ?int
    {
        return match ($itemConfig['delivery_type']) {
            'mail' => $this->deliverMail($user, $server, $itemConfig, $reference),
            'boost' => $this->deliverBoost($user, $itemConfig),
            'claim_reset' => $this->deliverClaimReset($user),
            'boost_slot' => $this->deliverBoostSlot($user, $itemConfig),
            default => throw new Exception("Unknown delivery_type: {$itemConfig['delivery_type']}"),
        };
    }

    /**
     * Deliver via in-game mail using gmcmd sendMail (via ExecuteGmCommand job).
     */
    private function deliverMail(User $user, Server $server, array $itemConfig, string $reference): int
    {
        /** @var array<string, mixed> $deliveryConfig */
        $deliveryConfig = (array) ($itemConfig['delivery_config'] ?? []);
        $actor = $this->gmApiService->findActor($server, $user->username);

        /** @var array<int, array{item_id: int|null, amount: int}> $mailItems */
        $mailItems = (array) ($deliveryConfig['items'] ?? []);
        $itemPayloadParts = [];
        foreach ($mailItems as $mailItem) {
            $itemId = $mailItem['item_id'] ?? null;
            $itemAmount = (int) ($mailItem['amount'] ?? 0);
            if (! empty($itemId) && $itemAmount > 0) {
                $itemPayloadParts[] = "1,{$itemId},{$itemAmount}";
            }
        }
        $itemPayload = implode(';', $itemPayloadParts);

        $gmAction = GmAction::create([
            'target_user' => $user->username,
            'server_id' => $server->id,
            'action_type' => 'send_mail',
            'action_uuid' => $reference,
            'payload' => [
                'account_name' => $user->username,
                'player_id' => (string) $actor['actorid'],
                'title' => (string) ($deliveryConfig['title'] ?? 'S1 Legacy Shop'),
                'body' => (string) ($deliveryConfig['body'] ?? 'Vật phẩm mua từ S1 Legacy Shop.'),
                'item_payload' => $itemPayload,
                'item_slug' => $itemConfig['slug'],
            ],
            'status' => 'pending',
        ]);

        ExecuteGmCommand::dispatch($gmAction->id);

        return (int) $gmAction->id;
    }

    /**
     * Deliver a boost directly to s1_player_boosts (portal-side only).
     */
    private function deliverBoost(User $user, array $itemConfig): ?int
    {
        /** @var array<string, mixed> $deliveryConfig */
        $deliveryConfig = (array) ($itemConfig['delivery_config'] ?? []);

        S1PlayerBoost::create([
            'user_id' => $user->id,
            'boost_category' => (string) ($deliveryConfig['boost_category'] ?? ''),
            'value' => (float) ($deliveryConfig['value'] ?? 0.0),
            'source_slug' => (string) $itemConfig['slug'],
            'expires_at' => now()->addHours((int) ($deliveryConfig['duration_hours'] ?? 0)),
        ]);

        return null;
    }

    /**
     * Reset last_claim_at to (now - min_claim_interval) on ALL of user's machines.
     * Portal-side only, limit 1 time/week/user enforced by S1ShopPurchase period_key.
     */
    private function deliverClaimReset(User $user): ?int
    {
        $minInterval = (int) config('economy.min_claim_interval', 60);
        $resetTo = now()->subSeconds($minInterval);

        DiamondMachine::where('user_id', $user->id)
            ->update(['last_claim_at' => $resetTo]);

        return null;
    }

    /**
     * Grant the user an extra boost slot for duration_hours by incrementing max_active_boosts.
     * Creates a timed record in s1_player_boosts with boost_category = 'boost_slot' to track expiry.
     */
    private function deliverBoostSlot(User $user, array $itemConfig): ?int
    {
        /** @var array<string, mixed> $deliveryConfig */
        $deliveryConfig = (array) ($itemConfig['delivery_config'] ?? []);
        $durationHours = (int) ($deliveryConfig['duration_hours'] ?? 168);

        // Increment the slot count on the wallet
        $wallet = DiamondWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'lifetime_mined' => 0, 'lifetime_spent' => 0]
        );
        $wallet->increment('max_active_boosts');

        // Track the unlock expiry so we can revert when it expires
        S1PlayerBoost::create([
            'user_id' => $user->id,
            'boost_category' => 'offline', // reuse offline as a sentinel for slot tracking
            'value' => 0.0,
            'source_slug' => (string) $itemConfig['slug'],
            'expires_at' => now()->addHours($durationHours),
        ]);

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSuccessMessage(array $itemConfig): string
    {
        return match ($itemConfig['delivery_type']) {
            'mail' => "✅ {$itemConfig['name']}: vật phẩm đã gửi vào thư ingame trong giây lát!",
            'boost' => "⚡ {$itemConfig['name']} đã kích hoạt! Buff sẽ có hiệu lực ngay.",
            'claim_reset' => "🔄 {$itemConfig['name']}: Thời gian đào của tất cả máy đã được reset!",
            'boost_slot' => "🔓 {$itemConfig['name']}: Slot Boost thứ 2 đã mở trong 1 tuần!",
            default => '✅ Giao dịch thành công!',
        };
    }

    /**
     * Get fresh balance for the given currency after deduction.
     */
    private function getFreshBalance(User $user, string $currency): int|float
    {
        if ($currency === 'points') {
            $freshUser = $user->fresh();

            return $freshUser ? (int) $freshUser->points : 0;
        }

        // For KC, we don't track portal-side balance — return 0 as sentinel
        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DiamondMiningService integration helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all active S1 boosts for a user by category.
     *
     * @return array<string, array<int, S1PlayerBoost>>
     */
    public function getActiveBoostsByCategory(User $user): array
    {
        $boosts = S1PlayerBoost::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->get();

        $grouped = [];
        foreach ($boosts as $boost) {
            $grouped[(string) $boost->boost_category][] = $boost;
        }

        return $grouped;
    }

    /**
     * Calculate the combined regen multiplier bonus from active S1 regen boosts.
     * Example: two +20% boosts → 1.40 (additive stacking)
     */
    public function getRegenMultiplierBonus(User $user): float
    {
        $boosts = S1PlayerBoost::where('user_id', $user->id)
            ->where('boost_category', 'regen')
            ->where('expires_at', '>', now())
            ->get();

        $total = 0.0;
        foreach ($boosts as $boost) {
            $total += (float) $boost->value;
        }

        return $total;
    }

    /**
     * Calculate the combined daily cap bonus from active S1 daily_cap boosts.
     */
    public function getDailyCapBonus(User $user): int
    {
        $boosts = S1PlayerBoost::where('user_id', $user->id)
            ->where('boost_category', 'daily_cap')
            ->where('expires_at', '>', now())
            ->get();

        $total = 0;
        foreach ($boosts as $boost) {
            $total += (int) $boost->value;
        }

        return $total;
    }

    /**
     * Calculate the combined offline bonus hours from active S1 offline boosts.
     */
    public function getOfflineBonus(User $user): float
    {
        $boosts = S1PlayerBoost::where('user_id', $user->id)
            ->where('boost_category', 'offline')
            ->where('expires_at', '>', now())
            ->get();

        $totalHours = 0.0;
        foreach ($boosts as $boost) {
            $totalHours += (float) $boost->value;
        }

        return $totalHours;
    }

    /**
     * Get the S1 server's Carbon opened_at date.
     */
    public function getServerOpenedAt(Server $server): ?Carbon
    {
        return $server->opened_at;
    }
}
