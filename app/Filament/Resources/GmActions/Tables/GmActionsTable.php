<?php

declare(strict_types=1);

namespace App\Filament\Resources\GmActions\Tables;

use App\Jobs\ExecuteGmCommand;
use App\Models\GmAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GmActionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('action_uuid')
                    ->label('UUID')
                    ->searchable()
                    ->limit(12)
                    ->tooltip(fn (GmAction $record) => $record->action_uuid),
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable(),
                TextColumn::make('action_type')
                    ->label('Action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('target_user')
                    ->label('Target')
                    ->searchable(),
                TextColumn::make('server_id')
                    ->label('Server'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'executing' => 'info',
                        'executed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'ms' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (GmAction $record): bool => $record->status === 'failed')
                    ->action(function (GmAction $record): void {
                        $record->update(['status' => 'pending']);
                        dispatch(new ExecuteGmCommand($record->id));
                        Notification::make()
                            ->title('Job Requeued')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
