<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins\Pages;

use App\Filament\Resources\SdkDailyCheckins\SdkDailyCheckinResource;
use App\Filament\Resources\SdkDailyCheckins\Widgets\SdkDailyCheckinStats;
use Filament\Resources\Pages\ListRecords;

class ListSdkDailyCheckins extends ListRecords
{
    protected static string $resource = SdkDailyCheckinResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SdkDailyCheckinStats::class,
        ];
    }
}
