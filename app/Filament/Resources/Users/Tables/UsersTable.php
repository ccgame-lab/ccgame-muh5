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
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
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
            ->filters([])
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
