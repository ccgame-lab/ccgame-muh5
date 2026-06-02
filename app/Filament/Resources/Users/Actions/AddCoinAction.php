<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Jobs\DiamondMiningJob;
use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class AddCoinAction
{
    public static function make(): Action
    {
        return Action::make('addCoin')
            ->label('Add Diamond')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->form([
                Select::make('server_id')
                    ->label('Máy chủ')
                    ->options(Server::pluck('name', 'id'))
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, User $record, ?string $state): void {
                        if (! $state) {
                            $set('character_info', null);

                            return;
                        }

                        $server = Server::find($state);
                        if (! $server) {
                            return;
                        }

                        try {
                            $gmService = app(GmApiService::class);
                            $actor = $gmService->findActor($server, $record->username);
                            $set('character_info', "{$actor['actorname']} (ID: {$actor['actorid']})");
                        } catch (\Exception) {
                            $set('character_info', '⚠️ KHÔNG TÌM THẤY NHÂN VẬT');
                        }
                    }),
                TextInput::make('character_info')
                    ->label('Nhân vật')
                    ->placeholder('Chọn máy chủ để kiểm tra...')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('amount')
                    ->label('Số lượng Yuanbao')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('reason')
                    ->label('Lý do')
                    ->placeholder('Ví dụ: Quà sự kiện, đền bù bảo trì')
                    ->maxLength(255),
            ])
            ->action(function (User $record, array $data): void {
                $server = Server::findOrFail($data['server_id']);

                try {
                    $actionUuid = (string) Str::uuid();

                    gm_log([
                        'action_uuid' => $actionUuid,
                        'action' => 'add_coin',
                        'server_id' => $server->id,
                        'target' => $record->username,
                        'payload' => $data,
                    ]);

                    DiamondMiningJob::dispatch(
                        $server,
                        $record->username,
                        (int) $data['amount'],
                        $actionUuid
                    );

                    Notification::make()
                        ->title('Đã gửi vào queue')
                        ->body("Lệnh cộng {$data['amount']} Yuanbao đã được dispatch.")
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
