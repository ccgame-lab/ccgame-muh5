<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiamondWallets;

use App\Filament\Resources\DiamondWallets\Pages\ListDiamondWallets;
use App\Filament\Resources\DiamondWallets\Pages\ViewDiamondWallet;
use App\Models\DiamondWallet;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DiamondWalletResource extends Resource
{
    protected static ?string $model = DiamondWallet::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Diamond';

    protected static ?string $navigationLabel = 'Diamond Wallet';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('user.username')
                    ->label('Username'),
                TextEntry::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextEntry::make('diamond_blocks')
                    ->label('Blocks')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextEntry::make('ascension_level')
                    ->label('Ascension Level'),
                TextEntry::make('boost_multiplier')
                    ->label('Boost')
                    ->formatStateUsing(fn ($state) => $state.'x'),
                TextEntry::make('boost_until')
                    ->label('Boost hết hạn')
                    ->dateTime(),
                TextEntry::make('cap_multiplier')
                    ->label('Cap Multiplier')
                    ->formatStateUsing(fn ($state) => $state.'x'),
                TextEntry::make('cap_until')
                    ->label('Cap hết hạn')
                    ->dateTime(),
                TextEntry::make('last_maintained_at')
                    ->label('Bảo trì cuối')
                    ->dateTime(),
                TextEntry::make('last_claimed_at')
                    ->label('Nhận thưởng cuối')
                    ->dateTime(),
                TextEntry::make('lifetime_mined')
                    ->label('Tổng đào')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextEntry::make('lifetime_spent')
                    ->label('Tổng dùng')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextEntry::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('diamond_blocks')
                    ->label('Blocks')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('ascension_level')
                    ->label('Ascension')
                    ->sortable(),
                TextColumn::make('boost_multiplier')
                    ->label('Boost')
                    ->formatStateUsing(fn ($state) => $state.'x'),
                TextColumn::make('boost_until')
                    ->label('Boost hết hạn')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_maintained_at')
                    ->label('Bảo trì cuối')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lifetime_mined')
                    ->label('Tổng đào')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lifetime_spent')
                    ->label('Tổng dùng')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('last_maintained_at')
                    ->label('Bảo trì cuối (khoảng ngày)')
                    ->form([
                        DatePicker::make('maintained_from')
                            ->label('Từ ngày')
                            ->native(false),
                        DatePicker::make('maintained_until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['maintained_from'] ?? null,
                                fn ($q, $date) => $q->whereDate('last_maintained_at', '>=', $date),
                            )
                            ->when(
                                $data['maintained_until'] ?? null,
                                fn ($q, $date) => $q->whereDate('last_maintained_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('balance', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiamondWallets::route('/'),
            'view' => ViewDiamondWallet::route('/{record}'),
        ];
    }
}
