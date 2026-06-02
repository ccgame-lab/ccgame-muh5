<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes;

use App\Filament\Resources\Giftcodes\Pages\CreateGiftcode;
use App\Filament\Resources\Giftcodes\Pages\EditGiftcode;
use App\Filament\Resources\Giftcodes\Pages\ListGiftcodes;
use App\Filament\Resources\Giftcodes\Schemas\GiftcodeForm;
use App\Filament\Resources\Giftcodes\Tables\GiftcodesTable;
use App\Models\Giftcode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GiftcodeResource extends Resource
{
    protected static ?string $model = Giftcode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return GiftcodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GiftcodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiftcodes::route('/'),
            'create' => CreateGiftcode::route('/create'),
            'edit' => EditGiftcode::route('/{record}/edit'),
        ];
    }
}
