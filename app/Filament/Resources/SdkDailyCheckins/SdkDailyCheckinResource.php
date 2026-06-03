<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins;

use App\Filament\Resources\SdkDailyCheckins\Pages\ListSdkDailyCheckins;
use App\Models\SdkDailyCheckin;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SdkDailyCheckinResource extends Resource
{
    protected static ?string $model = SdkDailyCheckin::class;

    protected static string|\UnitEnum|null $navigationGroup = 'SDK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable(),
                TextColumn::make('checked_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('streak')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('reward_given')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('checked_today')
                    ->label('Hôm nay')
                    ->query(fn ($query) => $query->whereDate('checked_at', today())),

                Filter::make('checked_at')
                    ->label('Khoảng ngày')
                    ->form([
                        DatePicker::make('checked_from')
                            ->label('Từ ngày')
                            ->native(false),
                        DatePicker::make('checked_until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['checked_from'] ?? null, fn ($q, $date) => $q->whereDate('checked_at', '>=', $date))
                            ->when($data['checked_until'] ?? null, fn ($q, $date) => $q->whereDate('checked_at', '<=', $date));
                    }),

                Filter::make('user_id')
                    ->label('User ID')
                    ->form([
                        TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric(),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['user_id'] ?? null,
                        fn ($q, $id) => $q->where('user_id', $id),
                    )),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('checked_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkDailyCheckins::route('/'),
        ];
    }
}
