<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiamondWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_mined',
        'lifetime_spent',
        'ascension_level',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lifetime_mined' => 'integer',
            'lifetime_spent' => 'integer',
            'ascension_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
