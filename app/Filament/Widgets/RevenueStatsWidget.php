<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null; // chưa nối data

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, Stat> */
    public function getStats(): array
    {
        return [
            Stat::make('WPoint Chi', '0 WP')
                ->description('Chưa nối data thật — phase sau')
                ->color('gray'),
            Stat::make('Player hoạt động (24h)', '0')
                ->description('Chưa nối data thật — phase sau')
                ->color('gray'),
            Stat::make('Diamond hôm nay', '0 khai thác')
                ->description('Chưa nối data thật — phase sau')
                ->color('gray'),
            Stat::make('WCoin (hôm nay)', 'Earned: 0')
                ->description('Chưa nối data thật — phase sau')
                ->color('gray'),
        ];
    }
}
