<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialEvent extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'server_id',
        'event_type',
        'template',
        'metadata',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
