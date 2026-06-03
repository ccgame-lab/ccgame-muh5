<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('portal_uid')
                    ->readOnly(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->hiddenOn('edit')
                    ->requiredOn('create'),
                Select::make('tier')
                    ->options(['free' => 'Free', 'vip' => 'VIP'])
                    ->required()
                    ->default('free'),
                TextInput::make('points')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
                TextInput::make('last_login_ip')
                    ->readOnly(),
                DateTimePicker::make('last_login_at')
                    ->readOnly(),
                DateTimePicker::make('checkin_boost_expires_at'),
            ]);
        }
}
