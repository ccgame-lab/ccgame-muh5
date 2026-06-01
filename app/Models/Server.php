<?php

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property bool $visible
 * @property Carbon|null $opened_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Server extends Model
{
    /** Badge hiển thị trong danh sách server */
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

    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    /** @var bool */
    public $incrementing = false; // ID được nhập thủ công từ legacy

    /** @var list<string> */
    protected $fillable = [
        'id',
        'name',
        'host',
        'port',
        'db_name',
        'db_connection_name',
        'status',
        'visible',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'port' => 'integer',
            'status' => 'integer',
            'visible' => 'boolean',
            'opened_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Không xác định';
    }
}
