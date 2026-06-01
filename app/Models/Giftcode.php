<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 * @property array<string, mixed> $reward_data
 * @property string $reward_type
 */
class Giftcode extends Model
{
    protected $fillable = [
        'code',
        'server_id',
        'limit_usage',
        'used_count',
        'reward_type',
        'reward_data',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'reward_data' => 'array',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(GiftcodeRedemption::class);
    }

    /**
     * Check if this giftcode is still usable (not expired, usage not exceeded).
     */
    public function isUsable(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->limit_usage > 0 && $this->used_count >= $this->limit_usage) {
            return false;
        }

        return true;
    }
}
