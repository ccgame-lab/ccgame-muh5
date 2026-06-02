<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class PShopStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        return [
            Stat::make('Milestone Funnel', '')
                ->description(new HtmlString('<div class="text-sm text-gray-400 mt-1">Chưa nối data thật — phase sau</div>'))
                ->color('primary'),
            Stat::make('Race Activity', '')
                ->description(new HtmlString('<div class="text-sm text-gray-400 mt-1">Chưa nối data thật — phase sau</div>'))
                ->color('danger'),
            Stat::make('Near-Miss Targets', '')
                ->description(new HtmlString('<div class="text-sm text-gray-400 mt-1">Chưa nối data thật — phase sau</div>'))
                ->color('warning'),
        ];
    }
}
