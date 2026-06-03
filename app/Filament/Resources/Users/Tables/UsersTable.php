<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\AddPointSilentAction;
use App\Filament\Resources\Users\Actions\GmBanAction;
use App\Filament\Resources\Users\Actions\GmKickAction;
use App\Filament\Resources\Users\Actions\GmLookupAction;
use App\Filament\Resources\Users\Actions\SendItemMailAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
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
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Đã copy portal_uid')
                    ->copyMessageDuration(1500),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vip' => 'warning',
                        default => 'gray',
                    })
                    ->action(
                        Action::make('toggleTier')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => "Chuyển tier: {$record->username}?")
                            ->modalDescription(fn ($record) => 'Từ '.strtoupper($record->tier).' sang '.($record->tier === 'vip' ? 'FREE' : 'VIP'))
                            ->modalSubmitActionLabel('Xác nhận')
                            ->modalCancelActionLabel('Hủy')
                            ->action(function ($record): void {
                                $newTier = $record->tier === 'vip' ? 'free' : 'vip';
                                $record->update(['tier' => $newTier]);
                                Notification::make()
                                    ->title('Đã đổi tier')
                                    ->body(strtoupper($newTier))
                                    ->success()
                                    ->send();
                            })
                    ),
                TextColumn::make('points')
                    ->formatStateUsing(fn (int $state): string => number_format($state))
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checkin_boost_expires_at')
                    ->label('Boost hết hạn')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_ip')
                    ->searchable()
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
                ViewAction::make()
                    ->label('Xem'),
                GmLookupAction::make(),
                SendItemMailAction::make(),
                AddPointSilentAction::make(),
                GmKickAction::make(),
                GmBanAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
