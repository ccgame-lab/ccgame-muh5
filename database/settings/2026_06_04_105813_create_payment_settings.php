<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.exchange_rate', (int) config('muh5.portal.exchange_rate', 1000));
        $this->migrator->add('payment.max_exchange_per_request', (int) config('portal.max_exchange_per_request', 10000));
        $this->migrator->add('payment.spin_cost', (int) config('economy.spin_cost', 10));
        $this->migrator->add('payment.spin_daily_limit', (int) config('economy.spin_daily_limit', 20));
    }
};
