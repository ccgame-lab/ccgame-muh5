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
                ->whereIn('status', ['spent', 'dispatched', 'delivered', 'delivery_failed'])
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
            throw new Exception('Server không hợp lệ. Tom đã bị trừ, sẽ tự giao lại. Mã: '.$log->id);
        }

        $actor = DB::connection($server->db_connection_name)
            ->table('actors')
            ->where('accountname', $user->username)
            ->first(['actorid']);

        if (! $actor) {
            // Tu lanh: tom da tru, vat pham se tu giao khi nguoi choi co nhan vat. Khong bao ops ngay.
            $this->markDeliveryFailed($log, $user, 'Nhân vật chưa tồn tại trên server.', alert: false);
            throw new Exception('Bạn chưa có nhân vật trên server này. Tom đã bị trừ, vật phẩm sẽ tự giao khi bạn tạo nhân vật. Mã: '.$log->id);
        }

        $payload = $this->buildDeliveryPayload($item, $actor);
        if ($payload === null) {
            $this->markDeliveryFailed($log, $user, 'Item config thiếu delivery method.');
            throw new Exception('Lỗi cấu hình vật phẩm. Tom đã bị trừ, liên hệ hỗ trợ. Mã: '.$log->id);
        }

        $this->dispatchGmMail($log, $serverId, $user->username, $payload);
    }

    /**
     * Dựng payload mail GM theo loại vật phẩm. Trả null nếu item thiếu cách giao.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function buildDeliveryPayload(array $item, object $actor): ?array
    {
        $playerId = (string) $actor->actorid;
        $kcYield = (int) ($item['base_kc_yield'] ?? 0);
        $gameItemId = $item['game_item_id'] ?? null;
        $itemName = $item['name'] ?? ($item['id'] ?? 'vật phẩm');

        if (! empty($item['is_lifetime_card'])) {
            return [
                'player_id' => $playerId,
                'title' => 'Đặc Quyền Trọn Đời',
                'body' => 'Bạn đã kích hoạt Đặc Quyền Trọn Đời.',
                'item_payload' => '1,'.($item['feecallback_item_id'] ?? 88899).',1',
            ];
        }

        if ($kcYield > 0) {
            return [
                'player_id' => $playerId,
                'title' => 'Cửa hàng Tôm',
                'body' => 'Bạn đã đổi Tôm lấy '.number_format($kcYield).' Kim Cương.',
                'item_payload' => '2,'.$kcYield,
            ];
        }

        if ($gameItemId) {
            return [
                'player_id' => $playerId,
                'title' => 'Cửa hàng Tôm',
                'body' => 'Bạn đã mua '.$itemName.' bằng Tôm.',
                'item_payload' => '1,'.$gameItemId.',1',
            ];
        }

        return null;
    }

    /**
     * Tạo GmAction gửi mail + dispatch job, đánh dấu log 'dispatched' (CHƯA 'delivered').
     * Job ExecuteGmCommand chạy async; tom:reconcile-deliveries sẽ xác nhận kết quả.
     * Chống giao trùng: nếu GmAction trước đó đã 'executed' thì chỉ xác nhận 'delivered'.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchGmMail(TomPurchaseLog $log, int $serverId, string $targetUsername, array $payload): void
    {
        $priorId = $log->meta['gm_action_id'] ?? null;
        if ($priorId) {
            $prior = GmAction::find($priorId);
            if ($prior && $prior->status === 'executed') {
                $log->update(['status' => 'delivered']);

                return;
            }
        }

        $gmAction = GmAction::create([
            'action_uuid' => Str::uuid()->toString(),
            'admin_id' => null,
            'server_id' => $serverId,
            'action_type' => 'send_mail',
            'target_user' => $targetUsername,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        ExecuteGmCommand::dispatch($gmAction->id);

        $attempts = (int) ($log->meta['delivery_attempts'] ?? 0) + 1;
        $log->update([
            'status' => 'dispatched',
            'meta' => array_merge((array) $log->meta, [
                'gm_action_id' => $gmAction->id,
                'delivery_attempts' => $attempts,
            ]),
        ]);
    }

    /**
     * Đối soát + tự giao lại 1 đơn chưa hoàn tất. Gọi từ lệnh tom:reconcile-deliveries.
     * Tôm đã trừ đúng từ trước; đây chỉ xử lý khâu giao hàng, KHÔNG hoàn tiền tự động.
     * cap = số lần dispatch tối đa cho lỗi gửi mail (lỗi "chưa có nhân vật" không tính cap).
     */
    public function retryOrFailDelivery(TomPurchaseLog $log, int $cap): void
    {
        if ($log->status === 'delivered' || ! empty($log->meta['terminal'])) {
            return;
        }

        // Xác nhận kết quả GmAction gần nhất (nếu có).
        $gmId = $log->meta['gm_action_id'] ?? null;
        if ($gmId) {
            $gm = GmAction::find($gmId);
            if ($gm && $gm->status === 'executed') {
                $log->update(['status' => 'delivered']);

                return;
            }
            if ($gm && in_array($gm->status, ['pending', 'executing'], true)) {
                return; // đang chạy, chờ lượt sau
            }
            if ($gm && $gm->status === 'failed' && (int) ($log->meta['delivery_attempts'] ?? 0) >= $cap) {
                $this->failDeliveryTerminal($log);

                return;
            }
        }

        $user = $log->user;
        $item = config('pshop.items.'.$log->item_id);
        if (! $user || ! is_array($item)) {
            $this->failDeliveryTerminal($log); // user/item không còn, cần người xử lý

            return;
        }

        $server = $log->server_id ? Server::find($log->server_id) : null;
        if (! $server || ! $server->db_connection_name) {
            return; // hạ tầng server tạm thiếu, thử lại lượt sau, không tính lần
        }

        $actor = DB::connection($server->db_connection_name)
            ->table('actors')
            ->where('accountname', $user->username)
            ->first(['actorid']);
        if (! $actor) {
            if ($log->status !== 'delivery_failed') {
                $log->update(['status' => 'delivery_failed', 'failure_reason' => 'Nhân vật chưa tồn tại trên server.']);
            }

            return; // chờ người chơi tạo nhân vật, không tính vào cap
        }

        $payload = $this->buildDeliveryPayload($item, $actor);
        if ($payload === null) {
            $this->failDeliveryTerminal($log);

            return;
        }

        $this->dispatchGmMail($log, (int) $log->server_id, $user->username, $payload);
    }

    /**
     * Đánh dấu đơn giao hàng thất bại vĩnh viễn (cần người xử lý: giao tay hoặc hoàn tay).
     */
    protected function failDeliveryTerminal(TomPurchaseLog $log): void
    {
        $log->update([
            'status' => 'delivery_failed',
            'meta' => array_merge((array) $log->meta, ['terminal' => true]),
        ]);

        $user = $log->user;
        if ($user) {
            $this->markDeliveryFailed($log, $user, $log->failure_reason ?: 'Giao hàng thất bại sau nhiều lần thử.', alert: true, terminal: true);
        }
    }

    /**
     * Đánh dấu delivery_failed + (tùy chọn) báo ops Telegram.
     * Tôm đã bị trừ; đơn sẽ được tự giao lại bởi tom:reconcile-deliveries, hoặc xử lý tay nếu terminal.
     */
    protected function markDeliveryFailed(TomPurchaseLog $log, User $user, string $reason, bool $alert = true, bool $terminal = false): void
    {
        $log->update(['status' => 'delivery_failed', 'failure_reason' => $reason]);

        if (! $alert) {
            return;
        }

        $itemName = $log->meta['item_name'] ?? $log->item_id;
        $head = $terminal
            ? '🚨 muh5 pshop: GIAO HÀNG THẤT BẠI (cần giao tay hoặc hoàn tay)'
            : '⚠️ muh5 pshop: giao hàng hoãn (sẽ tự thử lại)';

        TelegramAlert::send(implode("\n", [
            $head,
            "Người chơi: {$user->username} (portal_uid: {$user->portal_uid})",
            "Vật phẩm: {$itemName} ({$log->item_id})",
            "Đã trừ: {$log->tom_spent} Tôm, GreenJade exchange: {$log->greenjade_exchange_id}",
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
