<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins\Widgets;

use App\Models\SdkDailyCheckin;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SdkDailyCheckinStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $checkinToday = SdkDailyCheckin::whereDate('checked_at', today())->count();
        $checkinWeek  = SdkDailyCheckin::whereBetween('checked_at', [
            now()->startOfWeek(Carbon::MONDAY),
            now()->endOfWeek(Carbon::SUNDAY),
        ])->distinct('user_id')->count('user_id');

        return [
            Stat::make('Checkin hôm nay', $checkinToday)
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('User tuần này', $checkinWeek)
                ->icon('heroicon-o-users')
                ->color('info'),
        ];
    }
}
