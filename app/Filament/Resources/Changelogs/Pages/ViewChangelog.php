<?php

declare(strict_types=1);

namespace App\Filament\Resources\Changelogs\Pages;

use App\Filament\Resources\Changelogs\ChangelogResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewChangelog extends ViewRecord
{
    protected static string $resource = ChangelogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('server.name')->label('Server'),
            TextEntry::make('version_date')->label('Ngày')->date('d/m/Y'),
            TextEntry::make('title')->label('Tiêu đề')->columnSpanFull(),
            IconEntry::make('is_published')->label('Đã đăng')->boolean(),
            TextEntry::make('dev_notes')
                ->label('DEV Notes')
                ->markdown()
                ->columnSpanFull()
                ->placeholder('—'),
            TextEntry::make('player_notes')
                ->label('PLAYER Notes')
                ->markdown()
                ->columnSpanFull()
                ->placeholder('—'),
        ]);
    }
}
