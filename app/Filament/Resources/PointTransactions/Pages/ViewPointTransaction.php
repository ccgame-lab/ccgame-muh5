<?php

declare(strict_types=1);

namespace App\Filament\Resources\PointTransactions\Pages;

use App\Filament\Resources\PointTransactions\PointTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPointTransaction extends ViewRecord
{
    protected static string $resource = PointTransactionResource::class;
}
