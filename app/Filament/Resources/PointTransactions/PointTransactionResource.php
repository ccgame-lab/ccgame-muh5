<?php

declare(strict_types=1);

namespace App\Filament\Resources\PointTransactions;

use App\Filament\Resources\PointTransactions\Pages\ListPointTransactions;
use App\Filament\Resources\PointTransactions\Pages\ViewPointTransaction;
use App\Models\PointTransaction;
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

class PointTransactionResource extends Resource
{
    protected static ?string $model = PointTransaction::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Giao dịch';

    protected static ?string $navigationLabel = 'Lịch sử POINT';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

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
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('user.username')
                    ->label('Người dùng'),
                TextEntry::make('type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit'    => 'success',
                        'debit'     => 'danger',
                        'gm_credit' => 'warning',
                        'gm_debit'  => 'warning',
                        default     => 'gray',
                    }),
                TextEntry::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(function (int $state): string {
                        $sign = $state >= 0 ? '+' : '';
                        return $sign . number_format($state) . ' POINT';
                    })
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                TextEntry::make('balance_after')
                    ->label('Số dư sau')
                    ->formatStateUsing(fn (int $state): string => number_format($state) . ' POINT'),
                TextEntry::make('reference')
                    ->label('Tham chiếu')
                    ->copyable()
                    ->placeholder('—'),
                TextEntry::make('meta')
                    ->label('Meta')
                    ->formatStateUsing(fn ($state): string => $state
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : '—')
                    ->markdown(),
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
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Người dùng')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit'    => 'success',
                        'debit'     => 'danger',
                        'gm_credit' => 'warning',
                        'gm_debit'  => 'warning',
                        default     => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(function (int $state): string {
                        $sign = $state >= 0 ? '+' : '';
                        return $sign . number_format($state);
                    })
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('balance_after')
                    ->label('Số dư sau')
                    ->formatStateUsing(fn (int $state): string => number_format($state)),
                TextColumn::make('reference')
                    ->label('Tham chiếu')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại giao dịch')
                    ->options([
                        'credit'   => 'Credit',
                        'debit'    => 'Debit',
                        'gm_credit'=> 'GM Credit',
                        'gm_debit' => 'GM Debit',
                        'checkin'  => 'Check-in',
                        'purchase' => 'Purchase',
                        'refund'   => 'Refund',
                    ]),
                Filter::make('created_at')
                    ->label('Thời gian')
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
            'index' => ListPointTransactions::route('/'),
            'view'  => ViewPointTransaction::route('/{record}'),
        ];
    }
}
