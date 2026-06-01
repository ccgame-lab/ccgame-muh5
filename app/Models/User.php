<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Người dùng game — được đồng bộ từ GreenJade Portal sau mỗi lần đăng nhập.
 *
 * @property int $id
 * @property string $portal_uid
 * @property string $username
 * @property string|null $name
 * @property string|null $email
 * @property string $tier
 * @property int $wcoin
 * @property int $wpoint
 * @property Carbon|null $checkin_boost_expires_at
 * @property string|null $last_login_ip
 * @property Carbon|null $last_login_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'portal_uid',
        'username',
        'password',
        'name',
        'email',
        'tier',
        'wcoin',
        'wpoint',
        'last_login_ip',
        'last_login_at',
        'checkin_boost_expires_at',
        'last_seen_announcement_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'checkin_boost_expires_at' => 'datetime',
            'wcoin' => 'integer',
            'wpoint' => 'integer',
        ];
    }

    /**
     * Check if user has active x2 check-in boost ("Monthly Supporter").
     */
    public function hasActiveCheckinBoost(): bool
    {
        return $this->checkin_boost_expires_at !== null
            && $this->checkin_boost_expires_at->isFuture();
    }

    /**
     * Activate (or refresh) x2 check-in boost for given days.
     */
    public function activateCheckinBoost(int $days = 30): void
    {
        $expiresAt = $this->hasActiveCheckinBoost()
            ? $this->checkin_boost_expires_at->addDays($days)
            : now()->addDays($days);

        $this->update(['checkin_boost_expires_at' => $expiresAt]);
    }

    /**
     * Upsert local user từ response Portal API.
     *
     * @param  array{uid: string, username: string}  $portalData
     */
    public static function syncFromPortal(array $portalData, string $ip): static
    {
        /** @var static */
        return static::updateOrCreate(
            ['portal_uid' => $portalData['uid']],
            [
                'username' => $portalData['username'],
                'password' => 'portal-auth',
                'last_login_ip' => $ip,
                'last_login_at' => now(),
            ]
        );
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(DiamondWallet::class);
    }

    public function webWallet(): HasOne
    {
        return $this->hasOne(WebWallet::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(DiamondMachine::class);
    }

    public function claimLogs()
    {
        return $this->hasMany(DiamondClaimLog::class);
    }

    public function upgrades()
    {
        return $this->hasMany(DiamondUpgrade::class);
    }

    public function dailyLogs()
    {
        return $this->hasMany(DiamondDailyLog::class);
    }

    public function boosts()
    {
        return $this->hasMany(DiamondBoost::class);
    }

    public function wpointTransactions(): HasMany
    {
        return $this->hasMany(WPointTransaction::class);
    }

    public function wcoinTransactions(): HasMany
    {
        return $this->hasMany(WCoinTransaction::class);
    }
}
