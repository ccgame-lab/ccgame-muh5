<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'amount',
        'error_message',
        'refund_error_message',
        'meta',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'meta' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
