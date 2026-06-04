<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.server_name', config('muh5.game.name', 'MU Archangel H5'));
        $this->migrator->add('general.server_open', (bool) config('muh5.server_open', false));
        $this->migrator->add('general.maintenance_mode', false);
    }
};
