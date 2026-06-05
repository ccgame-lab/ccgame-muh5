<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GameSettings extends Settings
{
    public int $rate_per_hour;

    public int $daily_cap;

    public int $maintenance_cooldown_hours;

    public static function group(): string
    {
        return 'game';
    }
}
