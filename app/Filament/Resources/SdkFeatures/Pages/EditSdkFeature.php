<?php

namespace App\Filament\Resources\SdkFeatures\Pages;

use App\Filament\Resources\SdkFeatures\SdkFeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSdkFeature extends EditRecord
{
    protected static string $resource = SdkFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
