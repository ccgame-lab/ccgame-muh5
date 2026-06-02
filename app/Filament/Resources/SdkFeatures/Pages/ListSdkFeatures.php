<?php

namespace App\Filament\Resources\SdkFeatures\Pages;

use App\Filament\Resources\SdkFeatures\SdkFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSdkFeatures extends ListRecords
{
    protected static string $resource = SdkFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
