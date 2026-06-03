<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SdkDailyCheckinsTable
{
    public static function configure(Table $table): Table
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
}
