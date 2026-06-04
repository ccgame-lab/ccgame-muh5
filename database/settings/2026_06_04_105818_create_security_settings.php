<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('security.allowed_usernames', config('muh5.allowed_usernames', []));
        $this->migrator->add('security.gm_alert_threshold', (int) config('economy.gm_alert_threshold', 500000));
    }
};
