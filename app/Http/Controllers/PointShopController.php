<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TomPurchaseLog;
use App\Models\User;
use App\Services\InsufficientTomException;
use App\Services\PointShopService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PointShopController extends Controller
{
    public function __construct(protected PointShopService $pointShopService) {}

    /**
     * List shop items purchasable with Tom (GreenJade wallet).
     * Lazy-fetched by the SDK when the "Đặc quyền" tab opens.
     */
    public function items(Request $request): JsonResponse
    {
        $user = User::where('username', (string) $request->query('u', ''))->first();

        $purchasedByItem = [];
        if ($user) {
            $purchasedByItem = TomPurchaseLog::where('user_id', $user->id)
                ->whereIn('status', ['spent', 'delivered'])
                ->selectRaw('item_id, count(*) as cnt')
                ->groupBy('item_id')
                ->pluck('cnt', 'item_id')
                ->toArray();
        }

        $items = [];
        foreach (config('pshop.items', []) as $id => $item) {
            if (empty($item['price_tom'])) {
                continue;
            }

            $limit = $item['limit_per_user'] ?? null;
            $purchased = (int) ($purchasedByItem[$id] ?? 0);

            $items[] = [
                'id' => $id,
                'name' => $item['name'] ?? $id,
                'description' => $item['description'] ?? '',
                'price_tom' => (int) $item['price_tom'],
                'badge' => $item['badge'] ?? null,
                'tags' => $item['tags'] ?? [],
                'limit_per_user' => $limit,
                'purchased' => $purchased,
                'sold_out' => $limit !== null && $purchased >= $limit,
            ];
        }

        return response()->json(['items' => $items]);
    }

    /**
     * Purchase a Tom-priced item. Deducts Tom via GreenJade, delivers via GM mail.
     */
    public function buyWithTom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'u' => ['required', 'string'],
            'item_id' => ['required', 'string'],
            'server_id' => ['nullable', 'integer'],
        ]);

        $user = User::where('username', $validated['u'])->first();
        if (! $user) {
            return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
        }

        $serverId = (int) ($validated['server_id'] ?? 1);

        try {
            $result = $this->pointShopService->buyWithTom($user, $validated['item_id'], $serverId);

            return response()->json($result + [
                'message' => 'Đổi Tôm thành công! Vật phẩm sẽ vào hộp thư trong game.',
            ]);
        } catch (InsufficientTomException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            // GreenJade unreachable / unexpected response — Tom NOT deducted.
            return response()->json(['error' => 'Dịch vụ ví Tôm tạm gián đoạn, vui lòng thử lại sau ít phút.'], 503);
        } catch (Exception $e) {
            // Validation failures (invalid item, limit) and delivery_failed
            // (message already carries the support code).
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
