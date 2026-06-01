<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WPointTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WPointService
{
    /**
     * Credit WPoint to user's balance atomically.
     */
    public function credit(User $user, int $amount, string $type, ?string $reference = null, array $meta = []): int
    {
        if ($amount <= 0) {
            throw new Exception('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $reference, $meta): int {
            /** @var User $lockedUser */
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            $lockedUser->increment('wpoint', $amount);
            $lockedUser->refresh();
            $newBalance = (int) $lockedUser->wpoint;

            WPointTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'meta' => $meta ?: null,
            ]);

            return $newBalance;
        });
    }

    /**
     * Debit WPoint from user's balance atomically.
     *
     * @throws Exception When balance is insufficient
     */
    public function debit(User $user, int $amount, string $type, array $meta = []): int
    {
        if ($amount <= 0) {
            throw new Exception('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta): int {
            /** @var User $lockedUser */
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->wpoint < $amount) {
                throw new Exception('Not enough WPoint.');
            }

            $lockedUser->decrement('wpoint', $amount);
            $lockedUser->refresh();
            $newBalance = (int) $lockedUser->wpoint;

            WPointTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'reference' => null,
                'meta' => $meta ?: null,
            ]);

            return $newBalance;
        });
    }

    /**
     * Get fresh WPoint balance for user.
     */
    public function getBalance(User $user): int
    {
        return (int) User::query()->where('id', $user->id)->value('wpoint');
    }
}
