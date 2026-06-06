<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiamondTransactions;

use App\Filament\Resources\DiamondTransactions\Pages\ListDiamondTransactions;
use App\Filament\Resources\DiamondTransactions\Pages\ViewDiamondTransaction;
use App\Models\DiamondTransaction;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DiamondTransactionResource extends Resource
{
    protected static ?string $model = DiamondTransaction::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Diamond';

    protected static ?string $navigationLabel = 'Diamond Transactions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.username')
                ->label('Người dùng'),
            TextEntry::make('server_id')
                ->label('Server'),
            TextEntry::make('kc_spent')
                ->label('KC Spent')
                ->formatStateUsing(fn ($state) => number_format((int) $state)),
            TextEntry::make('block_received')
                ->label('Blocks Nhận')
                ->formatStateUsing(fn ($state) => number_format((int) $state)),
            TextEntry::make('status')
                ->label('Trạng thái')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'success' => 'success',
                    'failed'  => 'danger',
                    default   => 'gray',
                }),
            TextEntry::make('created_at')
                ->label('Thời gian')
                ->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Người dùng')
                    ->searchable(),
                TextColumn::make('server_id')
                    ->label('Server'),
                TextColumn::make('kc_spent')
                    ->label('KC Spent')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('block_received')
                    ->label('Blocks')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed'  => 'Failed',
                    ]),
                Filter::make('created_at')
                    ->label('Thời gian tạo')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày')->native(false),
                        DatePicker::make('until')->label('Đến ngày')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiamondTransactions::route('/'),
            'view'  => ViewDiamondTransaction::route('/{record}'),
        ];
    }
}
