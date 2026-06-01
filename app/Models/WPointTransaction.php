<?php

namespace App\Models;

use App\Services\SocialEventService;
use App\Services\TopDonateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WPointTransaction extends Model
{
    protected $table = 'wpoint_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted()
    {
        static::created(function (WPointTransaction $transaction) {
            if ($transaction->amount > 0 && ! in_array($transaction->type, ['milestone_bonus'])) {
                DB::afterCommit(function () use ($transaction) {
                    $user = $transaction->user;
                    if (! $user) {
                        return;
                    }
                    try {
                        app(SocialEventService::class)->push([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'server_id' => null,
                            'event_type' => 'recharge',
                            'template' => 'user_recharge',
                            'metadata' => [
                                'amount' => $transaction->amount,
                            ],
                            'priority' => 1,
                        ]);
                    } catch (\Exception $e) {
                    }
                });
            }

            if ($transaction->amount >= 0) {
                return; // We only track spending for top donate
            }

            DB::afterCommit(function () use ($transaction) {
                $user = $transaction->user;
                if (! $user) {
                    return;
                }

                $testUsers = config('app.test_users', ['quocquoc', 's99', 'admin']);
                if (in_array($user->username, $testUsers)) {
                    return;
                }

                app(TopDonateService::class)->recordSpend(
                    $user->id,
                    $user->username,
                    abs($transaction->amount)
                );
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
