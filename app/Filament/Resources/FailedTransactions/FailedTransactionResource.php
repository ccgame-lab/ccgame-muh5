<?php

declare(strict_types=1);

namespace App\Filament\Resources\FailedTransactions;

use App\Filament\Resources\FailedTransactions\Pages\ListFailedTransactions;
use App\Filament\Resources\FailedTransactions\Pages\ViewFailedTransaction;
use App\Models\FailedTransaction;
use BackedEnum;
use Filament\Actions\Action;
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

class FailedTransactionResource extends Resource
{
    protected static ?string $model = FailedTransaction::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Giao dịch';

    protected static ?string $navigationLabel = 'Giao dịch lỗi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

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
                TextEntry::make('user.username')
                    ->label('Username'),
                TextEntry::make('type')
                    ->label('Loại')
                    ->badge(),
                TextEntry::make('reference')
                    ->label('Mã tham chiếu')
                    ->copyable(),
                TextEntry::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' POINT'),
                TextEntry::make('error_message')
                    ->label('Lỗi'),
                TextEntry::make('refund_error_message')
                    ->label('Lỗi hoàn tiền')
                    ->placeholder('—'),
                TextEntry::make('meta')
                    ->label('Meta')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—')
                    ->markdown(),
                TextEntry::make('resolved_at')
                    ->label('Thời gian xử lý')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Thời gian tạo')
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
                    ->label('Username')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(fn ($state) => number_format($state)),
                TextColumn::make('error_message')
                    ->label('Lỗi')
                    ->limit(40)
                    ->tooltip(fn (FailedTransaction $record) => $record->error_message),
                TextColumn::make('resolved_at')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Đã xử lý' : 'Chưa xử lý')
                    ->color(fn (FailedTransaction $record): string => $record->resolved_at ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('resolved')
                    ->label('Trạng thái xử lý')
                    ->options([
                        'unresolved' => 'Chưa xử lý',
                        'resolved' => 'Đã xử lý',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'unresolved') {
                            return $query->whereNull('resolved_at');
                        }
                        if ($value === 'resolved') {
                            return $query->whereNotNull('resolved_at');
                        }

                        return $query;
                    }),
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
                Action::make('resolve')
                    ->label('Đánh dấu đã xử lý')
                    ->requiresConfirmation()
                    ->visible(fn (FailedTransaction $record): bool => $record->resolved_at === null)
                    ->action(fn (FailedTransaction $record) => $record->update(['resolved_at' => now()]))
                    ->icon('heroicon-m-check')
                    ->color('success'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedTransactions::route('/'),
            'view'  => ViewFailedTransaction::route('/{record}'),
        ];
    }
}
