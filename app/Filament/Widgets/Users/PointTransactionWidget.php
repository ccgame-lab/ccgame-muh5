<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Users;

use App\Models\PointTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class PointTransactionWidget extends TableWidget
{
    #[Locked]
    public int $userId = 0;

    protected function getTableQuery(): Builder
    {
        return PointTransaction::query()
            ->where('user_id', $this->userId)
            ->latest()
            ->limit(50);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lich su giao dich Point')
            ->description('50 gan nhat')
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thoi gian')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('So luong')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').number_format($state))
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Loai')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reference')
                    ->label('Ly do')
                    ->limit(30)
                    ->placeholder('---'),
                TextColumn::make('balance_after')
                    ->label('So du sau')
                    ->numeric()
                    ->sortable(),
            ]);
    }
}