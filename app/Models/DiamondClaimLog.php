<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiamondClaimLog extends Model
{
    protected $fillable = [
        'user_id',
        'machine_index',
        'amount_claimed',
        'production_seconds',
        'machine_level',
        'speed_level',
        'storage_level',
        'efficiency_level',
        'machine_snapshot',
        'is_lucky_drop',
        'drop_item_id',
        'drop_seed',
        'drop_table_version',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_lucky_drop' => 'boolean',
            'machine_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
