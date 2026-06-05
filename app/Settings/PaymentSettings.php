<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public int $exchange_rate;

    public int $max_exchange_per_request;

    public int $spin_cost;

    public int $spin_daily_limit;

    public static function group(): string
    {
        return 'payment';
    }
}
