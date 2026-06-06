<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $label = 'Lượt dùng';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('Tài khoản')
                    ->searchable(),
                TextColumn::make('user.portal_uid')
                    ->label('Portal UID')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('Địa chỉ IP'),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
