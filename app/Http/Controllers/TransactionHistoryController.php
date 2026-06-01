<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WCoinTransaction;
use App\Models\WPointTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    /**
     * Paginated WPoint transaction history.
     */
    public function wpointHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $transactions = WPointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15, ['type', 'amount', 'balance_after', 'reference', 'created_at']);

        return response()->json($transactions);
    }

    /**
     * Paginated WCoin transaction history.
     */
    public function wcoinHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $transactions = WCoinTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15, ['type', 'amount', 'balance_after', 'reference', 'created_at']);

        return response()->json($transactions);
    }
}
