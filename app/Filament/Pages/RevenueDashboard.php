<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\SystemAlertsWidget;
use App\Filament\Widgets\TopSpendersWidget;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class RevenueDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Revenue Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Revenue Dashboard';

    protected ?string $subheading = 'Today vs yesterday — chờ nối data thật trong phase sau';

    protected string $view = 'filament.pages.revenue-dashboard';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [
            RevenueStatsWidget::class,
            SystemAlertsWidget::class,
            TopSpendersWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
