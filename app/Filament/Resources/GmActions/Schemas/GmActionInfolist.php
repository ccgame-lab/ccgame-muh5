<?php

declare(strict_types=1);

namespace App\Filament\Resources\GmActions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GmActionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('action_uuid')
                    ->label('UUID')
                    ->copyable(),
                TextEntry::make('admin.name')
                    ->label('Admin'),
                TextEntry::make('action_type')
                    ->label('Action')
                    ->badge(),
                TextEntry::make('target_user')
                    ->label('Target'),
                TextEntry::make('server_id')
                    ->label('Server ID'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'executing' => 'info',
                        'executed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('ip_address')
                    ->label('IP Address'),
                TextEntry::make('payload')
                    ->label('Payload')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                    ->markdown(),
                TextEntry::make('response')
                    ->label('Response')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—')
                    ->markdown(),
                TextEntry::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2).' ms' : '—'),
                TextEntry::make('executing_started_at')
                    ->label('Executing Started At')
                    ->dateTime(),
                TextEntry::make('executed_at')
                    ->label('Executed At')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
            ]);
    }
}
