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
use Illuminate\Support\Facades\Cache;

class SdkStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Dashboard admin: cache moi stat de moi lan mo khong ban 5 query. TTL ngan, du tuoi.
        // Key co kem ngay cho stat "hom nay" de qua nua dem khong dinh gia tri ngay cu.
        $day = today()->toDateString();
        $checkinToday = Cache::remember("sdkstats:checkin_today:{$day}", 60, fn () => SdkDailyCheckin::whereDate('checked_at', today())->count());
        $checkinWeek = Cache::remember('sdkstats:checkin_week', 60, fn () => SdkDailyCheckin::whereBetween('checked_at', [
            now()->startOfWeek(Carbon::MONDAY),
            now()->endOfWeek(Carbon::SUNDAY),
        ])->distinct('user_id')->count('user_id'));
        $totalUsers = Cache::remember('sdkstats:total_users', 300, fn () => User::count());
        $giftcodeToday = Cache::remember("sdkstats:giftcode_today:{$day}", 60, fn () => GiftcodeRedemption::whereDate('created_at', today())->count());
        // Chi dem GM that bai trong 7 ngay gan day, tranh con so tich luy vinh vien gay hieu nham.
        $gmFailed = Cache::remember('sdkstats:gm_failed_7d', 60, fn () => GmAction::where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count());

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
            Stat::make('Giftcode dùng hôm nay', $giftcodeToday)
                ->icon('heroicon-o-ticket')
                ->color('warning'),
            Stat::make('GM thất bại (7 ngày)', $gmFailed)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
