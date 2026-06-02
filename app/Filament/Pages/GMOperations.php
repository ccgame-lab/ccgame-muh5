<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Jobs\ExecuteGmCommand;
use App\Models\Server;
use App\Services\Game\GmApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;

class GMOperations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'GM Operations';

    protected static ?string $title = 'GM Operations (Global & Batch)';

    protected string $view = 'filament.pages.g-m-operations';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Tabs::make('Operations')
                    ->tabs([
                        Tab::make('Global Mail')
                            ->badge('Nguy hiểm')
                            ->badgeColor('danger')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->schema([
                                Select::make('global_server_id')
                                    ->label('Máy chủ')
                                    ->options(Server::pluck('name', 'id'))
                                    ->required(),
                                TextInput::make('global_title')
                                    ->label('Tiêu đề')
                                    ->required(),
                                Textarea::make('global_content')
                                    ->label('Nội dung')
                                    ->required(),

                                Repeater::make('global_items_list')
                                    ->label('Danh sách vật phẩm')
                                    ->schema([
                                        Select::make('id')
                                            ->label('Vật phẩm')
                                            ->options($this->getItemsOptions())
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
                                    ->maxItems(5),
                                Actions::make([
                                    Action::make('send_global')
                                        ->label('Gửi thư toàn máy chủ')
                                        ->icon('heroicon-m-paper-airplane')
                                        ->color('primary')
                                        ->requiresConfirmation()
                                        ->modalHeading('Gửi thư toàn máy chủ')
                                        ->modalDescription('Thư này sẽ được gửi tới TẤT CẢ người chơi trong máy chủ. Bạn có chắc chắn?')
                                        ->modalSubmitActionLabel('Xác nhận')
                                        ->modalCancelActionLabel('Hủy bỏ')
                                        ->action(function (Action $action): void {
                                            $this->sendGlobalMail($action);
                                        })
                                        ->successNotificationTitle('Đã gửi lệnh Global Mail thành công.'),
                                ]),
                            ]),

                        Tab::make('Batch WCoin')
                            ->badge('Đã khóa')
                            ->badgeColor('gray')
                            ->icon('heroicon-m-lock-closed')
                            ->schema([
                                \Filament\Schemas\Components\Placeholder::make('sealed_batch')
                                    ->content('Currency operations sealed until settlement flow is implemented.')
                                    ->extraAttributes(['class' => 'fi-ta-empty-state-description']),
                            ]),

                        Tab::make('Event Reward')
                            ->schema([
                                Select::make('event_server_id')
                                    ->label('Máy chủ')
                                    ->options(Server::pluck('name', 'id'))
                                    ->required(),
                                Textarea::make('player_list')
                                    ->label('Danh sách Username')
                                    ->placeholder("user1\nuser2\nuser3")
                                    ->helperText('Mỗi dòng một username')
                                    ->required(),
                                TextInput::make('event_title')
                                    ->label('Tiêu đề thư')
                                    ->required(),
                                Textarea::make('event_content')
                                    ->label('Nội dung thư'),

                                Repeater::make('event_items_list')
                                    ->label('Danh sách vật phẩm')
                                    ->schema([
                                        Select::make('id')
                                            ->label('Vật phẩm')
                                            ->options($this->getItemsOptions())
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
                                    ->maxItems(5)
                                    ->required(),
                                Actions::make([
                                    Action::make('send_event')
                                        ->label('Phát thưởng danh sách')
                                        ->icon('heroicon-m-gift')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->modalHeading('Phát thưởng cho danh sách')
                                        ->modalDescription('Hệ thống sẽ gửi thư vật phẩm cho danh sách người chơi đã nhập.')
                                        ->modalSubmitActionLabel('Xác nhận')
                                        ->modalCancelActionLabel('Hủy bỏ')
                                        ->action(function (Action $action): void {
                                            $this->processEventReward($action);
                                        })
                                        ->successNotificationTitle('Đã gửi yêu cầu phát thưởng thành công.'),
                                ]),
                            ]),
                        Tab::make('Topup List')
                            ->badge('Đã khóa')
                            ->badgeColor('gray')
                            ->icon('heroicon-m-lock-closed')
                            ->schema([
                                \Filament\Schemas\Components\Placeholder::make('sealed_topup')
                                    ->content('Currency operations sealed until settlement flow is implemented.')
                                    ->extraAttributes(['class' => 'fi-ta-empty-state-description']),
                            ]),
                    ])
                    ->statePath('data'),
            ]);
    }

    /** @return array<string, string> */
    protected function getItemsOptions(): array
    {
        $items = config('game_items', []);

        return collect($items)
            ->mapWithKeys(fn (array $item, string $key): array => [$key => "{$key} - {$item['name']}"])
            ->toArray();
    }

    public function sendGlobalMail(Action $action): void
    {
        Log::info('GMOperations: Starting sendGlobalMail');
        $formData = $this->data['data'] ?? $this->data ?? [];

        $serverId = (int) ($formData['global_server_id'] ?? 0);
        $server = Server::find($serverId);
        if (! $server) {
            Notification::make()->title('Server không tồn tại.')->danger()->send();
            $action->cancel();

            return;
        }

        $items = collect($formData['global_items_list'] ?? [])
            ->map(fn ($item) => "1,{$item['id']},{$item['count']}")
            ->implode(';');

        $log = gm_log([
            'action' => 'send_global_mail',
            'server_id' => $server->id,
            'target' => 'all',
            'payload' => [
                'server_id' => $server->id,
                'title' => $formData['global_title'] ?? '',
                'body' => $formData['global_content'] ?? '',
                'item_payload' => $items,
            ],
        ]);

        ExecuteGmCommand::dispatch($log->id);

        $action->success();
    }

    public function processEventReward(Action $action): void
    {
        Log::info('GMOperations: Starting processEventReward');
        $formData = $this->data['data'] ?? $this->data ?? [];

        $usernames = array_filter(array_map('trim', explode("\n", $formData['player_list'] ?? '')));

        if (empty($usernames)) {
            Notification::make()->title('Danh sách người chơi trống.')->danger()->send();
            $action->halt();

            return;
        }

        $items = collect($formData['event_items_list'] ?? [])
            ->map(fn ($item) => "1,{$item['id']},{$item['count']}")
            ->implode(';');

        if (empty($items)) {
            Notification::make()->title('Chưa chọn vật phẩm thưởng.')->danger()->send();
            $action->halt();

            return;
        }

        $serverId = (int) ($formData['event_server_id'] ?? 0);
        $server = Server::find($serverId);
        if (! $server) {
            Notification::make()->title('Server không tồn tại.')->danger()->send();
            $action->cancel();

            return;
        }

        $gmService = app(GmApiService::class);

        foreach ($usernames as $username) {
            try {
                $actor = $gmService->findActor($server, $username);
                $actorId = (string) $actor['actorid'];
            } catch (\Exception) {
                Log::warning("Event Reward: Character not found for account {$username} on server {$server->name}. Skipping.");

                continue;
            }

            $log = gm_log([
                'action' => 'send_mail',
                'server_id' => $server->id,
                'target' => $username,
                'payload' => [
                    'server_id' => $server->id,
                    'player_id' => $actorId,
                    'title' => $formData['event_title'] ?? '',
                    'body' => $formData['event_content'] ?? '',
                    'item_payload' => $items,
                ],
            ]);

            ExecuteGmCommand::dispatch($log->id);
        }

        $action->success();
    }
}
