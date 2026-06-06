<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiamondTransactions\Pages;

use App\Filament\Resources\DiamondTransactions\DiamondTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListDiamondTransactions extends ListRecords
{
    protected static string $resource = DiamondTransactionResource::class;
}
