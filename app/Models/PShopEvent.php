<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PShopEvent extends Model
{
    protected $fillable = [
        'type',
        'name',
        'status',
        'start_time',
        'end_time',
        'target',
        'multiplier',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'multiplier' => 'decimal:2',
            'config' => 'json',
        ];
    }

    public function scopeActive($query)
    {
        $now = now();

        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
            });
    }
}
