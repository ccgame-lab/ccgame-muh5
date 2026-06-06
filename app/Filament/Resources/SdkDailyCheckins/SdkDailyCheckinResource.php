<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins;

use App\Filament\Resources\SdkDailyCheckins\Pages\ListSdkDailyCheckins;
use App\Filament\Resources\SdkDailyCheckins\Pages\ViewSdkDailyCheckin;
use App\Models\SdkDailyCheckin;
use BackedEnum;
use Filament\Actions\ViewAction;
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

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'SDK';

    protected static ?string $navigationLabel = 'Điểm danh hàng ngày';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

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
                TextColumn::make('checked_at')
                    ->label('Ngày điểm danh')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('streak')
                    ->label('Chuỗi liên tiếp')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('reward_given')
                    ->label('Đã nhận thưởng')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('checked_today')
                    ->label('Hôm nay')
                    ->query(fn ($query) => $query->whereDate('checked_at', today())),

                Filter::make('reward_given')
                    ->label('Đã nhận thưởng')
                    ->query(fn ($query) => $query->where('reward_given', true)),

                Filter::make('checked_at_range')
                    ->label('Khoảng ngày điểm danh')
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

                Filter::make('username')
                    ->label('Username')
                    ->form([
                        TextInput::make('username')
                            ->label('Username người dùng')
                            ->placeholder('Nhập username...'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['username'] ?? null,
                        fn ($q, $username) => $q->whereHas('user', fn ($uq) => $uq->where('username', 'like', "%{$username}%")),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkDailyCheckins::route('/'),
            'view'  => ViewSdkDailyCheckin::route('/{record}'),
        ];
    }
}
