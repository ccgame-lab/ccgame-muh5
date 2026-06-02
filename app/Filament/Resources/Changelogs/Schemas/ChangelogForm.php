<?php

declare(strict_types=1);

namespace App\Filament\Resources\Changelogs\Schemas;

use App\Models\Server;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChangelogForm
{
    public static function configure(Schema $schema): Schema
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
}
