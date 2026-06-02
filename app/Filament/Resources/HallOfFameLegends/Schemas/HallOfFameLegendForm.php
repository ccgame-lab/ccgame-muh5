<?php

namespace App\Filament\Resources\HallOfFameLegends\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HallOfFameLegendForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('server_name')->required(),
                TextInput::make('server_key')->required(),
                Select::make('category')
                    ->options([
                        'combat' => 'Combat',
                        'donate' => 'Donate',
                    ])
                    ->required(),
                TextInput::make('category_label')->required(),
                TextInput::make('player_name')->nullable(),
                TextInput::make('score_value')->numeric()->nullable(),
                TextInput::make('score_label')->required(),
                TagsInput::make('rewards')->nullable(),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
