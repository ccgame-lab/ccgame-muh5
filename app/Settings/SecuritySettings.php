<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public array $allowed_usernames;
    public int $gm_alert_threshold;

    public static function group(): string
    {
        return 'security';
    }
}
