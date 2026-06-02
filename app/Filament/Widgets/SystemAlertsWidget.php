<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SystemAlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.system-alerts-widget';

    protected ?string $pollingInterval = null; // chưa nối data

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{type: string, level: string, title: string, message: string, count: int}>
     */
    public function getAlerts(): array
    {
        return [];
    }
}
