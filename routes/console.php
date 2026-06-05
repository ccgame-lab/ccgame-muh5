<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Đối soát + tự giao lại đơn mua Tôm chưa hoàn tất (chống mất hàng sau khi đã trừ Tôm).
Schedule::command('tom:reconcile-deliveries')
    ->everyTwoMinutes()
    ->withoutOverlapping();
