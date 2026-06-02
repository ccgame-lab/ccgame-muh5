<?php

declare(strict_types=1);

namespace App\Filament\Resources\GmActions;

use App\Filament\Resources\GmActions\Pages\ListGmActions;
use App\Filament\Resources\GmActions\Pages\ViewGmAction;
use App\Filament\Resources\GmActions\Schemas\GmActionForm;
use App\Filament\Resources\GmActions\Schemas\GmActionInfolist;
use App\Filament\Resources\GmActions\Tables\GmActionsTable;
use App\Models\GmAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GmActionResource extends Resource
{
    protected static ?string $model = GmAction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'GM Audit Log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return GmActionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GmActionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GmActionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGmActions::route('/'),
            'view' => ViewGmAction::route('/{record}'),
        ];
    }
}
