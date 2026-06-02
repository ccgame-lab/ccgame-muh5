<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class GmKickAction
{
    public static function make(): Action
    {
        return Action::make('gmKick')
            ->label('Kick')
            ->icon('heroicon-o-user-minus')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => "Kick người chơi {$record->username}")
            ->modalDescription('Hành động này sẽ ngắt kết nối nhân vật khỏi server ngay lập tức.')
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

                    $gmService->kickPlayer($server, (string) $actor['actorid']);

                    gm_log([
                        'action' => 'kick',
                        'server_id' => $server->id,
                        'target' => $record->username,
                        'payload' => [
                            'server_name' => $server->name,
                            'player_id' => $actor['actorid'],
                        ],
                    ]);

                    Notification::make()
                        ->title('Đã gửi lệnh Kick')
                        ->body("Yêu cầu kick {$record->username} trên {$server->name} đã được gửi.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Lỗi')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
