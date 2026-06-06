<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopItems\Pages;

use App\Filament\Resources\S1ShopItems\S1ShopItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateS1ShopItem extends CreateRecord
{
    protected static string $resource = S1ShopItemResource::class;
}
