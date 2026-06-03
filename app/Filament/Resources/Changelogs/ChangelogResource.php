<?php

declare(strict_types=1);

namespace App\Filament\Resources\Changelogs;

use App\Filament\Resources\Changelogs\Pages\CreateChangelog;
use App\Filament\Resources\Changelogs\Pages\EditChangelog;
use App\Filament\Resources\Changelogs\Pages\ListChangelogs;
use App\Models\Changelog;
use App\Models\Server;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChangelogResource extends Resource
{
    protected static ?string $model = Changelog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Changelogs';

    protected static ?string $label = 'Changelog';

    protected static ?string $pluralLabel = 'Changelogs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('server_id')
                ->label('Server')
                ->options(Server::pluck('name', 'id'))
                ->required()
                ->columnSpan(1),

            DatePicker::make('version_date')
                ->label('Version Date')
                ->required()
                ->columnSpan(1),

            TextInput::make('title')
                ->label('Title')
                ->required()
                ->columnSpanFull(),

            Textarea::make('dev_notes')
                ->label('DEV Notes')
                ->rows(6)
                ->columnSpanFull(),

            Textarea::make('player_notes')
                ->label('PLAYER Notes')
                ->rows(6)
                ->columnSpanFull(),

            Toggle::make('is_published')
                ->label('Published')
                ->default(true)
                ->columnSpan(1),
        ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('version_date')
                    ->label('Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->defaultSort('version_date', 'desc')
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
            'index' => ListChangelogs::route('/'),
            'create' => CreateChangelog::route('/create'),
            'edit' => EditChangelog::route('/{record}/edit'),
        ];
    }
}
