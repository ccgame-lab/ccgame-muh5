<?php

declare(strict_types=1);

namespace App\Filament\Resources\GiftcodeRedemptions\Pages;

use App\Filament\Resources\GiftcodeRedemptions\GiftcodeRedemptionResource;
use Filament\Resources\Pages\ListRecords;

class ListGiftcodeRedemptions extends ListRecords
{
    protected static string $resource = GiftcodeRedemptionResource::class;
}
