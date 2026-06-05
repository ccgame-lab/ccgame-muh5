<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ExecuteGmCommand;
use App\Models\FruitPurchaseLog;
use App\Models\GmAction;
use App\Models\Server;
use App\Models\TomPurchaseLog;
use App\Models\User;
use App\Support\TelegramAlert;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointShopService
{
    public function __construct(
        protected PointService $pointService,
        protected GreenJadeClient $greenJadeClient,
    ) {}

    /**
     * Purchase High Fruit with POINT and deliver via in-game mail.
     *
     * @return array{success: bool, spent: int, items: array<int, array{item_id: int, quantity: int}>, balance: int}
     *
     * @throws Exception
     */
    public function buyFruit(User $user, int $itemId, int $quantity, int $serverId): array
    {
        $prices = config('economy.high_fruit_prices', []);

        if (! isset($prices[$itemId])) {
            throw new Exception('Vật phẩm không hợp lệ.');
        }

        if ($quantity < 1) {
            throw new Exception('Số lượng phải ít nhất là 1.');
        }

        $weeklyLimit = (int) config('economy.high_fruit_weekly_limit', 3);

        if ($weeklyLimit > 0) {
            $purchasedThisWeek = $this->getWeeklyPurchased($user);
            if ($purchasedThisWeek + $quantity > $weeklyLimit) {
                $remaining = max(0, $weeklyLimit - $purchasedThisWeek);
                throw new Exception("Giới hạn mua hàng tuần: còn {$remaining} trái cây tuần này.");
            }
        }

        $server = Server::find($serverId);
        if (! $server || ! $server->db_connection_name) {
            throw new Exception('Server không hợp lệ hoặc chưa cấu hình DB.');
        }

        $unitPrice = (int) $prices[$itemId];
        $totalCost = $unitPrice * $quantity;
        $actor = $this->findActor($server, $user->username);

        return DB::transaction(function () use ($user, $itemId, $quantity, $serverId, $totalCost, $actor): array {
            $newBalance = $this->pointService->debit(
                $user,
                $totalCost,
                'fruit_purchase',
                ['item_id' => $itemId, 'quantity' => $quantity, 'server_id' => $serverId]
            );

            FruitPurchaseLog::create([
                'user_id' => $user->id,
                'server_id' => $serverId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'wcoin_spent' => $totalCost,
            ]);

            $itemPayload = "1,{$itemId},{$quantity}";
            $actionUuid = Str::uuid()->toString();

            $gmAction = GmAction::create([
                'action_uuid' => $actionUuid,
                'admin_id' => null,
                'server_id' => $serverId,
                'action_type' => 'send_mail',
                'target_user' => $user->username,
                'payload' => [
                    'player_id' => (string) $actor->actorid,
                    'title' => 'Cửa hàng',
                    'body' => "Bạn đã mua {$quantity}x vật phẩm từ cửa hàng.",
                    'item_payload' => $itemPayload,
                ],
                'status' => 'pending',
            ]);

            ExecuteGmCommand::dispatch($gmAction->id);

            return [
                'success' => true,
                'spent' => $totalCost,
                'items' => [['item_id' => $itemId, 'quantity' => $quantity]],
                'balance' => $newBalance,
            ];
        });
    }

    /**
     * Purchase a pshop item priced in Tom (GreenJade wallet).
     * Flow: deduct Tom via GreenJade API then deliver via GM command.
     * No auto-rollback: GM job retries. Manual GJ admin refund if exhausted.
     *
     * @return array{success: bool, exchange_id: string, tom_spent: int, remaining_tom: int}
     *
     * @throws InsufficientTomException
     * @throws Exception
     */
    public function buyWithTom(User $user, string $itemId, int $serverId): array
    {
        $item = config("pshop.items.{$itemId}");
        if (! $item || empty($item['price_tom'])) {
            throw new Exception('Vật phẩm không hợp lệ hoặc không bán bằng Tôm.');
        }

        if (! $user->portal_uid) {
            throw new Exception('Tài khoản chưa liên kết GreenJade. Vui lòng đăng nhập lại.');
        }

        $tomCost = (int) $item['price_tom'];

        $limit = $item['limit_per_user'] ?? null;
        if ($limit !== null) {
            $purchased = TomPurchaseLog::where('user_id', $user->id)
                ->where('item_id', $itemId)
                ->whereIn('status', ['spent', 'delivered'])
                ->count();
            if ($purchased >= $limit) {
                throw new Exception('Bạn đã đạt giới hạn mua vật phẩm này.');
            }
        }

        $idempotencyKey = 'muh5-pshop-'.Str::ulid();
        $logId = (string) Str::uuid();

        $log = TomPurchaseLog::create([
            'id' => $logId,
            'user_id' => $user->id,
            'item_id' => $itemId,
            'server_id' => $serverId,
            'tom_spent' => $tomCost,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'meta' => ['item_name' => $item['name'] ?? $itemId],
        ]);

        $spendResult = $this->greenJadeClient->spend(
            portalUid: $user->portal_uid,
            tomAmount: $tomCost,
            idempotencyKey: $idempotencyKey,
            reason: 'Mua '.($item['name'] ?? $itemId).' tại muh5',
            metadata: ['server_id' => $serverId, 'item_id' => $itemId],
        );

        $log->update([
            'status' => 'spent',
            'greenjade_exchange_id' => $spendResult['exchange_id'],
            'remaining_tom' => $spendResult['remaining_tom'],
        ]);

        $this->deliverTomItem($user, $item, $serverId, $log);

        return [
            'success' => true,
            'exchange_id' => $spendResult['exchange_id'],
            'tom_spent' => $tomCost,
            'remaining_tom' => $spendResult['remaining_tom'],
        ];
    }

    /**
     * Get total fruit quantity purchased this week (all item_ids combined).
     */
    public function getWeeklyPurchased(User $user): int
    {
        return (int) FruitPurchaseLog::where('user_id', $user->id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('quantity');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function deliverTomItem(User $user, array $item, int $serverId, TomPurchaseLog $log): void
    {
        $server = Server::find($serverId);
        if (! $server || ! $server->db_connection_name) {
            $this->markDeliveryFailed($log, $user, 'Server không hợp lệ.');
            throw new Exception('Server không hợp lệ. Tom đã bị trừ — liên hệ hỗ trợ với mã: '.$log->id);
        }

        $actor = DB::connection($server->db_connection_name)
            ->table('actors')
            ->where('accountname', $user->username)
            ->first(['actorid']);

        if (! $actor) {
            $this->markDeliveryFailed($log, $user, 'Nhân vật không tồn tại trên server.');
            throw new Exception('Bạn chưa có nhân vật trên server này. Tom đã bị trừ — liên hệ hỗ trợ với mã: '.$log->id);
        }

        $kcYield = (int) ($item['base_kc_yield'] ?? 0);
        $gameItemId = $item['game_item_id'] ?? null;
        $isLifetimeCard = ! empty($item['is_lifetime_card']);
        $itemId = $item['id'];

        if ($isLifetimeCard) {
            $actionPayload = [
                'player_id' => (string) $actor->actorid,
                'title' => 'Đặc Quyền Trọn Đời',
                'body' => 'Bạn đã kích hoạt Đặc Quyền Trọn Đời.',
                'item_payload' => '1,'.($item['feecallback_item_id'] ?? 88899).',1',
            ];
        } elseif ($kcYield > 0) {
            $actionPayload = [
                'player_id' => (string) $actor->actorid,
                'title' => 'Cửa hàng Tôm',
                'body' => 'Bạn đã đổi Tôm lấy '.number_format($kcYield).' Kim Cương.',
                'item_payload' => '2,'.$kcYield,
            ];
        } elseif ($gameItemId) {
            $actionPayload = [
                'player_id' => (string) $actor->actorid,
                'title' => 'Cửa hàng Tôm',
                'body' => 'Bạn đã mua '.($item['name'] ?? $itemId).' bằng Tôm.',
                'item_payload' => '1,'.$gameItemId.',1',
            ];
        } else {
            $this->markDeliveryFailed($log, $user, 'Item config thiếu delivery method.');
            throw new Exception('Lỗi cấu hình vật phẩm. Tom đã bị trừ — liên hệ hỗ trợ với mã: '.$log->id);
        }

        $gmAction = GmAction::create([
            'action_uuid' => Str::uuid()->toString(),
            'admin_id' => null,
            'server_id' => $serverId,
            'action_type' => 'send_mail',
            'target_user' => $user->username,
            'payload' => $actionPayload,
            'status' => 'pending',
        ]);

        ExecuteGmCommand::dispatch($gmAction->id);

        $log->update(['status' => 'delivered', 'meta' => array_merge((array) $log->meta, ['gm_action_id' => $gmAction->id])]);
    }

    /**
     * Mark a Tom purchase as delivery_failed and alert ops.
     *
     * Tom is already deducted at this point but the item never reached the
     * player. GreenJade has no refund endpoint, so each failure must page ops
     * for a manual refund.
     */
    protected function markDeliveryFailed(TomPurchaseLog $log, User $user, string $reason): void
    {
        $log->update(['status' => 'delivery_failed', 'failure_reason' => $reason]);

        $itemName = $log->meta['item_name'] ?? $log->item_id;

        TelegramAlert::send(implode("\n", [
            '🚨 muh5 pshop — GIAO HÀNG THẤT BẠI (cần refund tay)',
            "Người chơi: {$user->username} (portal_uid: {$user->portal_uid})",
            "Vật phẩm: {$itemName} ({$log->item_id})",
            "Đã trừ: {$log->tom_spent} Tôm — GreenJade exchange: {$log->greenjade_exchange_id}",
            "Lý do: {$reason}",
            "Mã log: {$log->id}",
        ]));
    }

    /**
     * @throws Exception
     */
    protected function findActor(Server $server, string $username): object
    {
        $actor = DB::connection($server->db_connection_name)
            ->table('actors')
            ->where('accountname', $username)
            ->first(['actorid', 'actorname']);

        if (! $actor) {
            throw new Exception('Bạn chưa có nhân vật trên server này. Hãy vào game tạo nhân vật trước.');
        }

        return $actor;
    }
}
