<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiamondMachine extends Model
{
    protected $fillable = [
        'user_id',
        'machine_index',
        'level',
        'speed_level',
        'storage_level',
        'efficiency_level',
        'base_rate',
        'capacity',
        'speed_multiplier',
        'storage_limit',
        'last_claim_at',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_claim_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'speed_multiplier' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
