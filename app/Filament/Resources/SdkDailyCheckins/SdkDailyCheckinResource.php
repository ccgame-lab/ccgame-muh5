<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkDailyCheckins;

use App\Filament\Resources\SdkDailyCheckins\Pages\ListSdkDailyCheckins;
use App\Filament\Resources\SdkDailyCheckins\Tables\SdkDailyCheckinsTable;
use App\Models\SdkDailyCheckin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SdkDailyCheckinResource extends Resource
{
    protected static ?string $model = SdkDailyCheckin::class;

    protected static string|\UnitEnum|null $navigationGroup = 'SDK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function table(Table $table): Table
    {
        return SdkDailyCheckinsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkDailyCheckins::route('/'),
        ];
    }
}
