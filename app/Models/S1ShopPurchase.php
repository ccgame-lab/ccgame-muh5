<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S1ShopPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'item_slug',
        'server_id',
        'reference',
        'currency',
        'amount_spent',
        'period_key',
        'gm_action_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_spent' => 'integer',
            'server_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gmAction(): BelongsTo
    {
        return $this->belongsTo(GmAction::class);
    }
}
