<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class PShopHourlyRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Doanh thu & Lượng Payer (48H) — chưa nối data';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Total Tôm (Revenue)',
                    'data' => [],
                ],
                [
                    'label' => 'Total Payers',
                    'data' => [],
                ],
            ],
            'labels' => [],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
