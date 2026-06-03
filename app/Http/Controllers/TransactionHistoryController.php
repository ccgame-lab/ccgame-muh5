<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    /**
     * Paginated POINT transaction history.
     */
    public function pointHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $transactions = PointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15, ['type', 'amount', 'balance_after', 'reference', 'created_at']);

        return response()->json($transactions);
    }
}
