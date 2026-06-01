<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpinLog extends Model
{
    protected $fillable = [
        'user_id',
        'prize_index',
        'prize_type',
        'prize_value',
        'wcoin_cost',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
