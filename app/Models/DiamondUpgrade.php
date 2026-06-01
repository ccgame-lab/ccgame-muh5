<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiamondUpgrade extends Model
{
    protected $fillable = [
        'user_id',
        'machine_index',
        'upgrade_type',
        'from_level',
        'to_level',
        'cost',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
