<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopItems\Pages;

use App\Filament\Resources\S1ShopItems\S1ShopItemResource;
use Filament\Resources\Pages\EditRecord;

class EditS1ShopItem extends EditRecord
{
    protected static string $resource = S1ShopItemResource::class;
}
