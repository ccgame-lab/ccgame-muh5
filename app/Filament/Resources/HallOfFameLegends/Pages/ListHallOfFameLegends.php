<?php

namespace App\Filament\Resources\HallOfFameLegends\Pages;

use App\Filament\Resources\HallOfFameLegends\HallOfFameLegendResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHallOfFameLegends extends ListRecords
{
    protected static string $resource = HallOfFameLegendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
