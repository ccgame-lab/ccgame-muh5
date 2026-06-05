<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $item_id
 * @property int|null $server_id
 * @property int $tom_spent
 * @property string $idempotency_key
 * @property string|null $greenjade_exchange_id
 * @property int|null $remaining_tom
 * @property string $status  pending|spent|delivered|failed|delivery_failed
 * @property string|null $failure_reason
 * @property array|null $meta
 */
class TomPurchaseLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'item_id',
        'server_id',
        'tom_spent',
        'idempotency_key',
        'greenjade_exchange_id',
        'remaining_tom',
        'status',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
