<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopItems\Pages;

use App\Filament\Resources\S1ShopItems\S1ShopItemResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewS1ShopItem extends ViewRecord
{
    protected static string $resource = S1ShopItemResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('id'),
            TextEntry::make('slug')->copyable(),
            TextEntry::make('name')->label('Tên'),
            TextEntry::make('track')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'free'    => 'gray',
                    'premium' => 'warning',
                    default   => 'gray',
                }),
            TextEntry::make('currency')
                ->badge(),
            TextEntry::make('price')
                ->label('Giá')
                ->formatStateUsing(fn (int $state): string => number_format($state)),
            TextEntry::make('unlock_week')->label('Mở khóa tuần')->placeholder('—'),
            TextEntry::make('limit_type')->label('Giới hạn'),
            TextEntry::make('limit_count')->label('Số lần tối đa')->placeholder('—'),
            TextEntry::make('delivery_type')->label('Loại giao hàng'),
            TextEntry::make('delivery_config')
                ->label('Delivery Config')
                ->formatStateUsing(fn ($state): string => $state
                    ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    : '—')
                ->placeholder('—'),
            TextEntry::make('is_active')
                ->label('Kích hoạt')
                ->badge()
                ->color(fn ($state): string => $state ? 'success' : 'danger')
                ->formatStateUsing(fn ($state): string => $state ? 'Hoạt động' : 'Tắt'),
            TextEntry::make('created_at')->label('Tạo lúc')->dateTime(),
            TextEntry::make('updated_at')->label('Cập nhật')->dateTime(),
        ]);
    }
}
