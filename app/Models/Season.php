<?php

namespace App\Models;

use App\Services\SeasonService;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'name',
        'status',
        'start_time',
        'end_time',
        'rewards_config',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'rewards_config' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    protected static function booted()
    {
        static::saved(function ($season) {
            app(SeasonService::class)->clearSeasonCache();
        });

        static::deleted(function ($season) {
            app(SeasonService::class)->clearSeasonCache();
        });
    }
}
