<?php

declare(strict_types=1);

namespace App\Filament\Resources\Servers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServerForm
{
    public static function configure(Schema $schema): Schema
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
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('opened_at'),
            ]);
    }
}
