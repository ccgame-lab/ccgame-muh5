<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopItems\Pages;

use App\Filament\Resources\S1ShopItems\S1ShopItemResource;
use Filament\Resources\Pages\ListRecords;

class ListS1ShopItems extends ListRecords
{
    protected static string $resource = S1ShopItemResource::class;
}
