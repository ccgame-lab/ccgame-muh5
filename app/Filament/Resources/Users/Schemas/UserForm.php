<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('portal_uid'),
                TextInput::make('username')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('tier')
                    ->required()
                    ->default('free'),
                TextInput::make('wcoin')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('wpoint')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('last_login_ip'),
                DateTimePicker::make('last_login_at'),
            ]);
    }
}
