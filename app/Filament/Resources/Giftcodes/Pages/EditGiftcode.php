<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGiftcode extends EditRecord
{
    protected static string $resource = GiftcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
