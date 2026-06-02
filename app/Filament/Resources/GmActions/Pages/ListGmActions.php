<?php

declare(strict_types=1);

namespace App\Filament\Resources\GmActions\Pages;

use App\Filament\Resources\GmActions\GmActionResource;
use Filament\Resources\Pages\ListRecords;

class ListGmActions extends ListRecords
{
    protected static string $resource = GmActionResource::class;
}
