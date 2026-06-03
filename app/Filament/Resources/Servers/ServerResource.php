<?php

declare(strict_types=1);

namespace App\Filament\Resources\Servers;

use App\Filament\Resources\Servers\Pages\CreateServer;
use App\Filament\Resources\Servers\Pages\EditServer;
use App\Filament\Resources\Servers\Pages\ListServers;
use App\Models\Server;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Cấu hình';

    protected static ?string $navigationLabel = 'Máy chủ';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('host')
                    ->required(),
                TextInput::make('port')
                    ->required()
                    ->numeric(),
                TextInput::make('db_name')
                    ->required(),
                TextInput::make('db_connection_name'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(Server::STATUS_LABELS)
                    ->required()
                    ->default(0),
                TextInput::make('priority')
                    ->label('Độ ưu tiên (cao hơn = hiển thị trước)')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('opened_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('host')
                    ->searchable(),
                TextColumn::make('port')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('db_name')
                    ->searchable(),
                TextColumn::make('db_connection_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => Server::STATUS_LABELS[$state] ?? 'N/A')
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'info',
                        4 => 'danger',
                        5 => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServers::route('/'),
            'create' => CreateServer::route('/create'),
            'edit' => EditServer::route('/{record}/edit'),
        ];
    }
}
