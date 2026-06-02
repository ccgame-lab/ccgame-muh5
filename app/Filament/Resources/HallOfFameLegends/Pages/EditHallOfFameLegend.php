<?php

namespace App\Filament\Resources\HallOfFameLegends\Pages;

use App\Filament\Resources\HallOfFameLegends\HallOfFameLegendResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHallOfFameLegend extends EditRecord
{
    protected static string $resource = HallOfFameLegendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
