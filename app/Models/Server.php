<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $db_name
 * @property string|null $db_connection_name
 * @property int $status
 * @property int $priority
 * @property Carbon|null $opened_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Server extends Model
{
    public const STATUS_NORMAL = 0;

    public const STATUS_HOT = 1;

    public const STATUS_NEW = 2;

    public const STATUS_RECOMMEND = 3;

    public const STATUS_MAINTENANCE = 4;

    public const STATUS_COMING_SOON = 5;

    /** @var array<int, string> */
    public const STATUS_LABELS = [
        self::STATUS_NORMAL => 'Bình thường',
        self::STATUS_HOT => 'Hot',
        self::STATUS_NEW => 'Mới',
        self::STATUS_RECOMMEND => 'Đề xuất',
        self::STATUS_MAINTENANCE => 'Bảo trì',
        self::STATUS_COMING_SOON => 'Sắp mở',
    ];

    /** @var bool */
    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'name',
        'host',
        'port',
        'db_name',
        'db_connection_name',
        'server_path',
        'status',
        'priority',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'port' => 'integer',
            'status' => 'integer',
            'priority' => 'integer',
            'opened_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Không xác định';
    }
}
