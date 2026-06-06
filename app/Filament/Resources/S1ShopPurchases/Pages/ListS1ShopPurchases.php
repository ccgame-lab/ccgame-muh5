<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopPurchases\Pages;

use App\Filament\Resources\S1ShopPurchases\S1ShopPurchaseResource;
use Filament\Resources\Pages\ListRecords;

class ListS1ShopPurchases extends ListRecords
{
    protected static string $resource = S1ShopPurchaseResource::class;
}
