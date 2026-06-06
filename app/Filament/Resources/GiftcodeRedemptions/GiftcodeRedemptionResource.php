<?php

declare(strict_types=1);

namespace App\Filament\Resources\GiftcodeRedemptions;

use App\Filament\Resources\GiftcodeRedemptions\Pages\ListGiftcodeRedemptions;
use App\Filament\Resources\GiftcodeRedemptions\Pages\ViewGiftcodeRedemption;
use App\Models\GiftcodeRedemption;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GiftcodeRedemptionResource extends Resource
{
    protected static ?string $model = GiftcodeRedemption::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Game';

    protected static ?string $navigationLabel = 'Lượt dùng Giftcode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
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
                TextColumn::make('giftcode.code')
                    ->label('Mã Giftcode')
                    ->searchable(),
                TextColumn::make('user.username')
                    ->label('Tài khoản')
                    ->searchable(),
                TextColumn::make('user.portal_uid')
                    ->label('Portal UID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('ip_address')
                    ->label('Địa chỉ IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->label('Ngày tạo')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiftcodeRedemptions::route('/'),
            'view'  => ViewGiftcodeRedemption::route('/{record}'),
        ];
    }
}
