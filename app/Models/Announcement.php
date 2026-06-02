<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Broadcast announcement — shown to all logged-in users via polling.
 *
 * @property int $id
 * @property string $title
 * @property string|null $body
 * @property string $type
 * @property string|null $icon
 * @property string|null $link
 * @property bool $is_active
 * @property Carbon|null $expires_at
 */
class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'icon',
        'link',
        'is_active',
        'starts_at',
        'expires_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Active, non-expired announcements.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
