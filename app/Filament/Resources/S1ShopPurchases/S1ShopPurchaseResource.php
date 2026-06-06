<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopPurchases;

use App\Filament\Resources\S1ShopPurchases\Pages\ListS1ShopPurchases;
use App\Filament\Resources\S1ShopPurchases\Pages\ViewS1ShopPurchase;
use App\Models\S1ShopPurchase;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class S1ShopPurchaseResource extends Resource
{
    protected static ?string $model = S1ShopPurchase::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop & Mùa';

    protected static ?string $navigationLabel = 'Lịch sử mua hàng (S1)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('item_slug')
                    ->label('Item')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('server_id')
                    ->label('Server'),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'points'   => 'success',
                        'diamonds' => 'info',
                        default    => 'gray',
                    }),
                TextColumn::make('amount_spent')
                    ->label('Số tiền')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('period_key')
                    ->label('Period')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('currency')
                    ->label('Loại tiền')
                    ->options([
                        'points'   => 'POINT',
                        'diamonds' => 'Diamond',
                    ]),
                Filter::make('created_at')
                    ->label('Thời gian')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày')->native(false),
                        DatePicker::make('until')->label('Đến ngày')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.username')
                    ->label('User'),
                TextEntry::make('item_slug')
                    ->label('Item')
                    ->copyable(),
                TextEntry::make('server_id')
                    ->label('Server'),
                TextEntry::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'points'   => 'success',
                        'diamonds' => 'info',
                        default    => 'gray',
                    }),
                TextEntry::make('amount_spent')
                    ->label('Số tiền')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextEntry::make('period_key')
                    ->label('Period')
                    ->placeholder('—'),
                TextEntry::make('gm_action_id')
                    ->label('GM Action ID')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Thời gian')
                    ->dateTime(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListS1ShopPurchases::route('/'),
            'view'  => ViewS1ShopPurchase::route('/{record}'),
        ];
    }
}
