<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGiftcodes extends ListRecords
{
    protected static string $resource = GiftcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
