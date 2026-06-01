<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $giftcode_id
 * @property int $user_id
 * @property string|null $ip_address
 */
class GiftcodeRedemption extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'giftcode_id',
        'user_id',
        'ip_address',
    ];

    public function giftcode(): BelongsTo
    {
        return $this->belongsTo(Giftcode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
