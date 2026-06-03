<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $checked_at
 * @property int $streak
 * @property bool $reward_given
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SdkDailyCheckin extends Model
{
    protected $fillable = [
        'user_id',
        'checked_at',
        'streak',
        'reward_given',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'date',
            'reward_given' => 'boolean',
        ];
    }

    /**
     * @param int $userId
     * @return Builder<static>
     */
    public function scopeTodayFor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->whereDate('checked_at', today());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
