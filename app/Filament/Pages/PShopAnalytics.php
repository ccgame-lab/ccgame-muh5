<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\PShopHourlyRevenueChart;
use App\Filament\Widgets\PShopStatsWidget;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class PShopAnalytics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'PShop Event Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?string $title = 'PShop Event Analytics (Chờ nối data phase sau)';

    protected string $view = 'filament.pages.pshop-analytics';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [
            PShopHourlyRevenueChart::class,
            PShopStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'activeMilestone' => null,
            'activeRace' => null,
            'activeBoost' => null,
        ];
    }
}
