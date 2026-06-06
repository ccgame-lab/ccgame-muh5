<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiamondTransaction extends Model
{
    protected $fillable = ['user_id', 'server_id', 'kc_spent', 'block_received', 'status'];

    protected function casts(): array
    {
        return [
            'kc_spent'       => 'integer',
            'block_received' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
