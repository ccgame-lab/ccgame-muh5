<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSpendersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Người Chi Nhiều Nhất (7 ngày) — chưa nối data';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),

                Tables\Columns\TextColumn::make('wpoint')
                    ->label('WPoint')
                    ->numeric(),
            ])
            ->paginated(false);
    }

    public function getTableQuery(): Builder
    {
        return User::query()->whereRaw('0 = 1');
    }
}
