<?php

declare(strict_types=1);

namespace App\Filament\Resources\GmActions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GmActionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('admin_id')
                    ->relationship('admin', 'name')
                    ->required(),
                TextInput::make('action_type')
                    ->required(),
                TextInput::make('target_user')
                    ->required(),
                TextInput::make('payload')
                    ->required(),
                TextInput::make('status')
                    ->required(),
            ]);
    }
}
