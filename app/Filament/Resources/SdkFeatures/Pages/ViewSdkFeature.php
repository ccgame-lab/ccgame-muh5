<?php

declare(strict_types=1);

namespace App\Filament\Resources\SdkFeatures\Pages;

use App\Filament\Resources\SdkFeatures\SdkFeatureResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSdkFeature extends ViewRecord
{
    protected static string $resource = SdkFeatureResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('key')->label('Feature Key')->badge()->color('gray'),
            TextEntry::make('label')->label('Tên hiển thị'),
            TextEntry::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'soon' => 'warning',
                    'maintenance' => 'danger',
                    'hidden' => 'gray',
                    default => 'gray',
                }),
            TextEntry::make('url')->label('URL')->placeholder('—'),
            TextEntry::make('note')->label('Sublabel')->placeholder('—'),
            IconEntry::make('is_active')->label('Hiển thị trong SDK')->boolean(),
            TextEntry::make('sort_order')->label('Thứ tự'),
        ]);
    }
}
