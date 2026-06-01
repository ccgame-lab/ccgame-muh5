<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmAction extends Model
{
    protected $fillable = [
        'action_uuid',
        'admin_id',
        'server_id',
        'action_type',
        'target_user',
        'payload',
        'status',
        'ip_address',
        'response',
        'duration_ms',
        'executing_started_at',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'duration_ms' => 'float',
            'executing_started_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
