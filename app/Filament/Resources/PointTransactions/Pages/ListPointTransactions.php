<?php

declare(strict_types=1);

namespace App\Filament\Resources\PointTransactions\Pages;

use App\Filament\Resources\PointTransactions\PointTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListPointTransactions extends ListRecords
{
    protected static string $resource = PointTransactionResource::class;
}
