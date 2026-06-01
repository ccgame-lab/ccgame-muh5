<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WCoinTransaction;
use App\Models\WebWallet;
use Exception;
use Illuminate\Support\Facades\DB;

class WCoinService
{
    /**
     * Credit WCoin to user's web wallet atomically.
     */
    public function credit(int $userId, int $amount, string $type, ?string $reference = null, array $meta = []): int
    {
        if ($amount <= 0) {
            throw new Exception('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount, $type, $reference, $meta): int {
            $wallet = WebWallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0]
            );

            /** @var WebWallet $lockedWallet */
            $lockedWallet = WebWallet::query()->where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $lockedWallet->increment('balance', $amount);
            $lockedWallet->increment('lifetime_earned', $amount);
            $lockedWallet->refresh();
            $newBalance = (int) $lockedWallet->balance;

            WCoinTransaction::create([
                'user_id' => $userId,
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
     * Debit WCoin from user's web wallet atomically.
     *
     * @throws Exception When balance is insufficient
     */
    public function debit(int $userId, int $amount, string $type, ?string $reference = null, array $meta = []): int
    {
        if ($amount <= 0) {
            throw new Exception('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount, $type, $reference, $meta): int {
            $wallet = WebWallet::where('user_id', $userId)->lockForUpdate()->first();

            if (! $wallet || $wallet->balance < $amount) {
                throw new Exception('Not enough WCoin.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('lifetime_spent', $amount);
            $wallet->refresh();
            $newBalance = (int) $wallet->balance;

            WCoinTransaction::create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'meta' => $meta ?: null,
            ]);

            return $newBalance;
        });
    }

    /**
     * Get current WCoin balance for user.
     */
    public function getBalance(int $userId): int
    {
        return (int) (WebWallet::where('user_id', $userId)->value('balance') ?? 0);
    }
}
