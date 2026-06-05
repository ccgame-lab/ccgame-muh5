<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\GiftcodeRedemption;
use App\Models\GmAction;
use App\Models\SdkDailyCheckin;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SdkStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $checkinToday = SdkDailyCheckin::whereDate('checked_at', today())->count();
        $checkinWeek = SdkDailyCheckin::whereBetween('checked_at', [
            now()->startOfWeek(Carbon::MONDAY),
            now()->endOfWeek(Carbon::SUNDAY),
        ])->distinct('user_id')->count('user_id');
        $totalUsers = User::count();

        return [
            Stat::make('Checkin hôm nay', $checkinToday)
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Checkin tuần này', $checkinWeek)
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Tổng user', $totalUsers)
                ->icon('heroicon-o-user-group')
                ->color('gray'),
            Stat::make('Giftcode dùng hôm nay', GiftcodeRedemption::whereDate('created_at', today())->count())
                ->icon('heroicon-o-ticket')
                ->color('warning'),
            Stat::make('GM actions thất bại', GmAction::where('status', 'failed')->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
