<?php

namespace App\Models;

use App\Services\SocialEventService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WPointTransaction extends Model
{
    protected $table = 'wpoint_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted()
    {
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
