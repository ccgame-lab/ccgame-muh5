<?php

declare(strict_types=1);

namespace App\Filament\Resources\S1ShopItems;

use App\Filament\Resources\S1ShopItems\Pages\CreateS1ShopItem;
use App\Filament\Resources\S1ShopItems\Pages\EditS1ShopItem;
use App\Filament\Resources\S1ShopItems\Pages\ListS1ShopItems;
use App\Filament\Resources\S1ShopItems\Pages\ViewS1ShopItem;
use App\Models\S1ShopItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class S1ShopItemResource extends Resource
{
    protected static ?string $model = S1ShopItem::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop & Mùa';

    protected static ?string $navigationLabel = 'Shop Items (S1)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(64),
                TextInput::make('name')
                    ->required(),
                Select::make('track')
                    ->options([
                        'free'    => 'Free Track',
                        'premium' => 'Premium Track',
                    ])
                    ->required(),
                Select::make('currency')
                    ->options([
                        'points'   => 'POINT',
                        'diamonds' => 'Diamond',
                    ])
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('unlock_week')
                    ->label('Mở khóa tuần')
                    ->numeric(),
                Select::make('limit_type')
                    ->label('Giới hạn')
                    ->options([
                        'none'       => 'Không giới hạn',
                        'per_user'   => 'Per User',
                        'per_period' => 'Per Period',
                    ])
                    ->default('none')
                    ->required(),
                TextInput::make('limit_count')
                    ->label('Số lần tối đa')
                    ->numeric(),
                Select::make('delivery_type')
                    ->label('Loại giao hàng')
                    ->options([
                        'game_mail'    => 'Game Mail',
                        'point_credit' => 'Point Credit',
                    ])
                    ->required(),
                KeyValue::make('delivery_config')
                    ->label('Delivery Config'),
                Toggle::make('is_active')
                    ->label('Kích hoạt')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('track')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free'    => 'gray',
                        'premium' => 'warning',
                        default   => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('price')
                    ->formatStateUsing(fn (int $state): string => number_format($state))
                    ->sortable(),
                TextColumn::make('unlock_week')
                    ->sortable(),
                TextColumn::make('limit_type')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => $state ? 'Hoạt động' : 'Tắt')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('track')
                    ->options([
                        'free'    => 'Free Track',
                        'premium' => 'Premium Track',
                    ]),
                SelectFilter::make('currency')
                    ->options([
                        'points'   => 'POINT',
                        'diamonds' => 'Diamond',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Hoạt động',
                        '0' => 'Tắt',
                    ]),
            ])
            ->defaultSort('id', 'asc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListS1ShopItems::route('/'),
            'create' => CreateS1ShopItem::route('/create'),
            'view'   => ViewS1ShopItem::route('/{record}'),
            'edit'   => EditS1ShopItem::route('/{record}/edit'),
        ];
    }
}
