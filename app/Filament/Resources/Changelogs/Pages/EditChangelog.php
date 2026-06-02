<?php

declare(strict_types=1);

namespace App\Filament\Resources\Changelogs\Pages;

use App\Filament\Resources\Changelogs\ChangelogResource;
use Filament\Resources\Pages\EditRecord;

class EditChangelog extends EditRecord
{
    protected static string $resource = ChangelogResource::class;
}
