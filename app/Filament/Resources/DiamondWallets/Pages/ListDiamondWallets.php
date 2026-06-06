<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiamondWallets\Pages;

use App\Filament\Resources\DiamondWallets\DiamondWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListDiamondWallets extends ListRecords
{
    protected static string $resource = DiamondWalletResource::class;
}
