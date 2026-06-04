<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notification.discord_webhook', null);
        $this->migrator->add('notification.admin_email', null);
    }
};
