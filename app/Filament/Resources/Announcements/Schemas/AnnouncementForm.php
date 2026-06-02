<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(255),
                Textarea::make('body')->nullable(),
                Toggle::make('is_active')->default(true),
                DateTimePicker::make('starts_at')->nullable(),
                DateTimePicker::make('expires_at')->nullable(),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
