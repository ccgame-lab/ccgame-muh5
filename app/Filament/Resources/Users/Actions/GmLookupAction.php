<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class GmLookupAction
{
    public static function make(): Action
    {
        return Action::make('gmLookup')
            ->label('Tra cứu')
            ->icon('heroicon-o-magnifying-glass')
            ->color('info')
            ->form([
                Select::make('server_id')
                    ->label('Máy chủ')
                    ->options(Server::pluck('name', 'id'))
                    ->required()
                    ->native(false),
            ])
            ->action(function (User $record, array $data): void {
                $server = Server::findOrFail($data['server_id']);
                $gmService = app(GmApiService::class);

                try {
                    $actor = $gmService->findActor($server, $record->username);

                    gm_log([
                        'action' => 'lookup',
                        'server_id' => $server->id,
                        'target' => $record->username,
                        'payload' => ['server_name' => $server->name],
                    ]);

                    $info = implode("\n", [
                        "Tên: {$actor['actorname']}",
                        "ID: {$actor['actorid']}",
                        "Level: {$actor['level']}",
                        "VIP: {$actor['vip_level']}",
                        'Gold: '.number_format((int) $actor['gold']),
                        'Yuanbao: '.number_format((int) $actor['yuanbao']),
                        'Power: '.number_format((int) $actor['totalpower']),
                    ]);

                    Notification::make()
                        ->title("Thông tin nhân vật — {$actor['actorname']}")
                        ->body($info)
                        ->info()
                        ->persistent()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Không tìm thấy')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
