<?php

namespace App\Filament\Resources\SdkFeatures;

use App\Filament\Resources\SdkFeatures\Pages\CreateSdkFeature;
use App\Filament\Resources\SdkFeatures\Pages\EditSdkFeature;
use App\Filament\Resources\SdkFeatures\Pages\ListSdkFeatures;
use App\Filament\Resources\SdkFeatures\Schemas\SdkFeatureForm;
use App\Filament\Resources\SdkFeatures\Tables\SdkFeaturesTable;
use App\Models\SdkFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SdkFeatureResource extends Resource
{
    protected static ?string $model = SdkFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SdkFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SdkFeaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkFeatures::route('/'),
            'create' => CreateSdkFeature::route('/create'),
            'edit' => EditSdkFeature::route('/{record}/edit'),
        ];
    }
}
