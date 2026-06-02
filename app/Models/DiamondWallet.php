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
        'last_maintained_at',
        'boost_multiplier',
        'boost_until',
        'cap_multiplier',
        'cap_until',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lifetime_mined' => 'integer',
            'lifetime_spent' => 'integer',
            'ascension_level' => 'integer',
            'last_maintained_at' => 'datetime',
            'boost_until' => 'datetime',
            'cap_until' => 'datetime',
            'boost_multiplier' => 'decimal:2',
            'cap_multiplier' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
