<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ExecuteGmCommand;
use App\Models\FruitPurchaseLog;
use App\Models\GmAction;
use App\Models\Server;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointShopService
{
    public function __construct(
        protected PointService $pointService,
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
     * Get total fruit quantity purchased this week (all item_ids combined).
     */
    public function getWeeklyPurchased(User $user): int
    {
        return (int) FruitPurchaseLog::where('user_id', $user->id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('quantity');
    }

    /**
     * Find actor by account name in the game DB.
     *
     * @return object
     *
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
