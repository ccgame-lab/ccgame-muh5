<?php

declare(strict_types=1);

namespace App\Filament\Resources\FailedTransactions\Pages;

use App\Filament\Resources\FailedTransactions\FailedTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFailedTransaction extends ViewRecord
{
    protected static string $resource = FailedTransactionResource::class;
}
