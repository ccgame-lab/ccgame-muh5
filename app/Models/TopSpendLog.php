<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopSpendLog extends Model
{
    protected $fillable = [
        'user_id',
        'season_id',
        'event_id',
        'amount',
        'ip_address',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'json',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
