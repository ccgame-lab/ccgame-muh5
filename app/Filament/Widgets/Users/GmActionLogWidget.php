<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Users;

use App\Models\GmAction;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class GmActionLogWidget extends TableWidget
{
    public ?User $record = null;

    protected function getTableQuery(): Builder
    {
        return GmAction::query()
            ->where('target_user', $this->record->username)
            ->latest()
            ->limit(20);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('GM Action Log')
            ->description('20 gần nhất - target: '.$this->record->username)
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
                TextColumn::make('action_type')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ban' => 'danger',
                        'kick' => 'warning',
                        'lookup' => 'info',
                        'send_mail' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'executing' => 'info',
                        'executed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payload')
                    ->label('Payload')
                    ->formatStateUsing(fn (mixed $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—')
                    ->limit(50)
                    ->placeholder('—'),
            ]);
    }
}
