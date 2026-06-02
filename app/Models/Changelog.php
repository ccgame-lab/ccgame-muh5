<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Changelog extends Model
{
    protected $fillable = [
        'server_id',
        'version_date',
        'title',
        'dev_notes',
        'player_notes',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'version_date' => 'date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeByServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }
}
