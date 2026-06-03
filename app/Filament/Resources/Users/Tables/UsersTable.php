<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\AddCoinAction;
use App\Filament\Resources\Users\Actions\AddWPointSilentAction;
use App\Filament\Resources\Users\Actions\GmBanAction;
use App\Filament\Resources\Users\Actions\GmKickAction;
use App\Filament\Resources\Users\Actions\GmLookupAction;
use App\Filament\Resources\Users\Actions\SendItemMailAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('portal_uid')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vip' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('wcoin')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('wpoint')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checkin_boost_expires_at')
                    ->label('Boost hết hạn')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_ip')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tier')
                    ->options(['free' => 'Free', 'vip' => 'VIP']),
                Filter::make('last_login_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('Đăng nhập từ'),
                        DatePicker::make('until')
                            ->label('Đăng nhập đến'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('last_login_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('last_login_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                GmLookupAction::make(),
                SendItemMailAction::make(),
                AddCoinAction::make(),
                AddWPointSilentAction::make(),
                GmKickAction::make(),
                GmBanAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
