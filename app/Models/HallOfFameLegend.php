<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallOfFameLegend extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_name',
        'server_key',
        'server_status',
        'category',
        'category_label',
        'player_name',
        'score_value',
        'score_label',
        'rewards',
        'sort_order',
    ];

    public function casts(): array
    {
        return [
            'rewards' => 'array',
            'score_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function isOngoing(): bool
    {
        return $this->server_status === 'ongoing';
    }

    public function isMystery(): bool
    {
        return $this->player_name === null;
    }
}
