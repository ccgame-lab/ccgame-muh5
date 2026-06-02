<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Jobs\SendGameMailJob;
use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class SendItemMailAction
{
    public static function make(): Action
    {
        return Action::make('sendItemMail')
            ->label('Send Mail/Items')
            ->icon('heroicon-o-envelope')
            ->color('warning')
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
                            $set('character_info', "✅ {$actor['actorname']} (Lv.{$actor['level']}) — ID #{$actor['actorid']}");
                            $set('player_id', (string) $actor['actorid']);
                        } catch (\Exception) {
                            $set('character_info', '⚠️ KHÔNG TÌM THẤY NHÂN VẬT');
                            $set('player_id', null);
                        }
                    }),
                TextInput::make('character_info')
                    ->label('Nhân vật')
                    ->placeholder('Chọn máy chủ để kiểm tra...')
                    ->disabled()
                    ->dehydrated(false),
                Hidden::make('player_id')
                    ->required(),
                TextInput::make('title')
                    ->label('Tiêu đề thư')
                    ->required()
                    ->maxLength(50),
                Textarea::make('content')
                    ->label('Nội dung')
                    ->required()
                    ->maxLength(255),
                Repeater::make('items_list')
                    ->label('Vật phẩm đính kèm')
                    ->schema([
                        Select::make('id')
                            ->label('Vật phẩm')
                            ->options(self::getItemsOptions())
                            ->searchable()
                            ->required(),
                        TextInput::make('count')
                            ->label('Số lượng')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->maxItems(5),
            ])
            ->action(function (User $record, array $data): void {
                $server = Server::findOrFail($data['server_id']);

                try {
                    $itemPayload = '';
                    if (! empty($data['items_list'])) {
                        $parts = [];
                        foreach ($data['items_list'] as $item) {
                            $parts[] = "1,{$item['id']},{$item['count']}";
                        }
                        $itemPayload = implode(';', $parts);
                    }

                    $actionUuid = (string) Str::uuid();

                    gm_log([
                        'action_uuid' => $actionUuid,
                        'action' => 'send_mail',
                        'server_id' => $server->id,
                        'target' => $record->username,
                        'payload' => $data,
                    ]);

                    SendGameMailJob::dispatch(
                        $server,
                        $data['player_id'],
                        $data['title'],
                        $data['content'],
                        $actionUuid,
                        $itemPayload
                    );

                    Notification::make()
                        ->title('Đã gửi vào queue')
                        ->body('Mail job đã được dispatch.')
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

    /** @return array<string, string> */
    private static function getItemsOptions(): array
    {
        $items = config('game_items', []);

        return collect($items)
            ->mapWithKeys(fn (array $item, string $key): array => [$key => "{$key} - {$item['name']}"])
            ->toArray();
    }
}
