<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class S1ShopItem extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'track',
        'currency',
        'price',
        'unlock_week',
        'limit_type',
        'limit_count',
        'delivery_type',
        'delivery_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'unlock_week' => 'integer',
            'limit_count' => 'integer',
            'delivery_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
