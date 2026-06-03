<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Users;

use App\Models\PointTransaction;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PointTransactionWidget extends TableWidget
{
    public ?User $record = null;

    protected function getTableQuery(): Builder
    {
        return PointTransaction::query()
            ->where('user_id', $this->record->id)
            ->latest()
            ->limit(50);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lịch sử giao dịch Point')
            ->description('50 gần nhất')
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Số lượng')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').number_format($state))
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reference')
                    ->label('Lý do')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('balance_after')
                    ->label('Số dư sau')
                    ->numeric()
                    ->sortable(),
            ]);
    }
}
