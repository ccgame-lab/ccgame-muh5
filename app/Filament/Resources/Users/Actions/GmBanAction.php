<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class GmBanAction
{
    public static function make(): Action
    {
        return Action::make('gmBan')
            ->label('Ban')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => "Ban tài khoản {$record->username}")
            ->modalDescription('Hành động này sẽ kick nhân vật và đổi tên tài khoản để ngăn đăng nhập lại.')
            ->form([
                Select::make('server_id')
                    ->label('Máy chủ')
                    ->options(Server::pluck('name', 'id'))
                    ->required()
                    ->native(false),
                TextInput::make('reason')
                    ->label('Lý do')
                    ->maxLength(255),
            ])
            ->action(function (User $record, array $data): void {
                $server = Server::findOrFail($data['server_id']);
                $gmService = app(GmApiService::class);

                try {
                    $actor = $gmService->findActor($server, $record->username);

                    $gmService->banPlayer(
                        $server,
                        (string) $actor['actorid'],
                        $record->username
                    );

                    gm_log([
                        'action' => 'ban',
                        'server_id' => $server->id,
                        'target' => $record->username,
                        'payload' => [
                            'server_name' => $server->name,
                            'player_id' => $actor['actorid'],
                            'reason' => $data['reason'] ?? '',
                        ],
                    ]);

                    Notification::make()
                        ->title('Đã ban người chơi')
                        ->body("Tài khoản {$record->username} đã bị ban trên {$server->name}.")
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
