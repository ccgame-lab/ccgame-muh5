<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins\Pages;

use App\Filament\Resources\SdkDailyCheckins\SdkDailyCheckinResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSdkDailyCheckin extends ViewRecord
{
    protected static string $resource = SdkDailyCheckinResource::class;
}
