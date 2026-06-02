<?php

declare(strict_types=1);

namespace App\Filament\Resources\Changelogs;

use App\Filament\Resources\Changelogs\Pages\CreateChangelog;
use App\Filament\Resources\Changelogs\Pages\EditChangelog;
use App\Filament\Resources\Changelogs\Pages\ListChangelogs;
use App\Filament\Resources\Changelogs\Schemas\ChangelogForm;
use App\Filament\Resources\Changelogs\Tables\ChangelogsTable;
use App\Models\Changelog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChangelogResource extends Resource
{
    protected static ?string $model = Changelog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Changelogs';

    protected static ?string $label = 'Changelog';

    protected static ?string $pluralLabel = 'Changelogs';

    public static function form(Schema $schema): Schema
    {
        return ChangelogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChangelogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangelogs::route('/'),
            'create' => CreateChangelog::route('/create'),
            'edit' => EditChangelog::route('/{record}/edit'),
        ];
    }
}
