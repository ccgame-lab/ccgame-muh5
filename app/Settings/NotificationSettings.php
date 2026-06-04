<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    public ?string $discord_webhook;
    public ?string $admin_email;

    public static function group(): string
    {
        return 'notification';
    }
}
