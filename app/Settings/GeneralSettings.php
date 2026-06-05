<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $server_name;

    public bool $server_open;

    public bool $maintenance_mode;

    public static function group(): string
    {
        return 'general';
    }
}
