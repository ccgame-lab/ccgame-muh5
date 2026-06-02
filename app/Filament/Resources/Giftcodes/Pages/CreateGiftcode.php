<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftcode extends CreateRecord
{
    protected static string $resource = GiftcodeResource::class;
}
