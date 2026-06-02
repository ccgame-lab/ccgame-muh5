<?php

namespace App\Filament\Resources\SdkFeatures\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SdkFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->options([
                        'wallet' => 'Wallet',
                        'giftcode' => 'Giftcode',
                        'shop' => 'Shop',
                        'spin' => 'Spin',
                        'mining' => 'Mining',
                        'support' => 'Support',
                    ])
                    ->required(),
                TextInput::make('label')->required(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'soon' => 'Soon',
                        'maintenance' => 'Maintenance',
                        'hidden' => 'Hidden',
                    ])
                    ->required(),
                TextInput::make('url')->nullable(),
                TextInput::make('note')->nullable(),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
