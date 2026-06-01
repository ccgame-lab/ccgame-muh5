<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PShopOrder extends Model
{
    use HasFactory;

    protected $table = 'pshop_orders';

    protected $fillable = [
        'user_id',
        'gifted_by',
        'item_key',
        'currency',
        'amount_spent',
        'quantity',
        'server_id',
        'status',
        'is_test',
        'gm_action_id',
        'reference',
    ];

    /**
     * Get the user who received the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who gifted the order.
     */
    public function gifter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gifted_by');
    }

    /**
     * Get the server the order was sent to.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the associated GM action for delivery tracking.
     */
    public function gmAction(): BelongsTo
    {
        return $this->belongsTo(GmAction::class);
    }
}
