<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewGiftcode extends ViewRecord
{
    protected static string $resource = GiftcodeResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('code')->copyable(),
            TextEntry::make('reward_type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'portal_credit' => 'success',
                    'game_mail' => 'info',
                    default => 'gray',
                }),
            TextEntry::make('limit_usage')->label('Giới hạn'),
            TextEntry::make('used_count')->label('Đã dùng'),
            TextEntry::make('expires_at')->label('Hết hạn')->dateTime()->placeholder('—'),
            TextEntry::make('reward_currency')->label('Loại tiền')->placeholder('—'),
            TextEntry::make('reward_amount')->label('Số lượng thưởng')->placeholder('—'),
            TextEntry::make('mail_title')->label('Tiêu đề thư')->placeholder('—'),
            TextEntry::make('mail_body')->label('Nội dung thư')->placeholder('—'),
            TextEntry::make('items_list')
                ->label('Vật phẩm')
                ->formatStateUsing(fn ($state) => $state
                    ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    : '—')
                ->placeholder('—'),
        ]);
    }
}
