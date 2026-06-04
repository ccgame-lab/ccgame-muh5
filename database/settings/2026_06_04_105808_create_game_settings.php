<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('game.rate_per_hour', (int) config('economy.legacy_mining.base_rate_per_hour', 20000));
        $this->migrator->add('game.daily_cap', (int) config('economy.legacy_mining.base_daily_cap', 300000));
        $this->migrator->add('game.maintenance_cooldown_hours', (int) config('economy.legacy_mining.maintenance_cooldown_hours', 6));
    }
};
