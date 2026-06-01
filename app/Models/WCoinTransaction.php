<?php

namespace App\Models;

use App\Services\SocialEventService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WCoinTransaction extends Model
{
    protected $table = 'wcoin_transactions';

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
        static::created(function (WCoinTransaction $transaction) {
            if ($transaction->amount > 0 && ! in_array($transaction->type, ['milestone_bonus', 'giftcode'])) {
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
                            'template' => 'user_recharge_wcoin',
                            'metadata' => [
                                'amount' => $transaction->amount,
                            ],
                            'priority' => 1,
                        ]);
                    } catch (\Exception $e) {
                    }
                });
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
