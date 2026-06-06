<?php

declare(strict_types=1);

namespace App\Filament\Resources\FailedTransactions\Pages;

use App\Filament\Resources\FailedTransactions\FailedTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListFailedTransactions extends ListRecords
{
    protected static string $resource = FailedTransactionResource::class;
}
