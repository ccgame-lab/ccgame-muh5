<?php

namespace App\Filament\Resources\HallOfFameLegends;

use App\Filament\Resources\HallOfFameLegends\Pages\CreateHallOfFameLegend;
use App\Filament\Resources\HallOfFameLegends\Pages\EditHallOfFameLegend;
use App\Filament\Resources\HallOfFameLegends\Pages\ListHallOfFameLegends;
use App\Filament\Resources\HallOfFameLegends\Schemas\HallOfFameLegendForm;
use App\Filament\Resources\HallOfFameLegends\Tables\HallOfFameLegendsTable;
use App\Models\HallOfFameLegend;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HallOfFameLegendResource extends Resource
{
    protected static ?string $model = HallOfFameLegend::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return HallOfFameLegendForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HallOfFameLegendsTable::configure($table);
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
            'index' => ListHallOfFameLegends::route('/'),
            'create' => CreateHallOfFameLegend::route('/create'),
            'edit' => EditHallOfFameLegend::route('/{record}/edit'),
        ];
    }
}
