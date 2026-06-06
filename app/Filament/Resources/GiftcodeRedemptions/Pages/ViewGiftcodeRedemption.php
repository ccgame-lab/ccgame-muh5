<?php

declare(strict_types=1);

namespace App\Filament\Resources\GiftcodeRedemptions\Pages;

use App\Filament\Resources\GiftcodeRedemptions\GiftcodeRedemptionResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewGiftcodeRedemption extends ViewRecord
{
    protected static string $resource = GiftcodeRedemptionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('giftcode.code')
                ->label('Mã Giftcode'),
            TextEntry::make('user.username')
                ->label('Tài khoản'),
            TextEntry::make('user.portal_uid')
                ->label('Portal UID')
                ->copyable(),
            TextEntry::make('ip_address')
                ->label('Địa chỉ IP'),
            TextEntry::make('created_at')
                ->label('Thời gian')
                ->dateTime(),
        ]);
    }
}
