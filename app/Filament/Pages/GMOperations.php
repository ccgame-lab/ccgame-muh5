<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Jobs\ExecuteGmCommand;
use App\Models\Server;
use App\Models\User;
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
                            ->badge('Nguy hiểm')
                            ->badgeColor('danger')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->schema([
                                Select::make('batch_type')
                                    ->label('Đối tượng')
                                    ->options([
                                        'all' => 'Tất cả người chơi',
                                        'vip' => 'Người chơi VIP',
                                    ])
                                    ->required()
                                    ->reactive(),
                                TextInput::make('batch_amount')
                                    ->label('Số lượng WCoin')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('batch_reason')
                                    ->label('Lý do')
                                    ->required(),
                                Actions::make([
                                    Action::make('send_batch')
                                        ->label('Thực hiện cộng WCoin hàng loạt')
                                        ->icon('heroicon-m-bolt')
                                        ->color('warning')
                                        ->requiresConfirmation()
                                        ->modalHeading('Cộng WCoin hàng loạt')
                                        ->modalDescription('Hệ thống sẽ cộng WCoin cho tất cả người chơi thuộc nhóm đã chọn.')
                                        ->modalSubmitActionLabel('Xác nhận')
                                        ->modalCancelActionLabel('Hủy bỏ')
                                        ->action(function (Action $action): void {
                                            $this->processBatchWCoin($action);
                                        })
                                        ->successNotificationTitle('Đã cộng WCoin thành công.'),
                                ]),
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
                            ->schema([
                                TextInput::make('topup_wcoin_amount')
                                    ->label('Số lượng WCoin (VND)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('topup_wpoint_amount')
                                    ->label('Số lượng WPoint (Donate)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Textarea::make('topup_player_list')
                                    ->label('Danh sách Username')
                                    ->placeholder("user1\nuser2\nuser3")
                                    ->helperText('Mỗi dòng một username')
                                    ->required(),
                                TextInput::make('topup_reason')
                                    ->label('Lý do')
                                    ->required(),
                                Actions::make([
                                    Action::make('send_topup_list')
                                        ->label('Thực hiện cộng tiền hàng loạt')
                                        ->icon('heroicon-m-banknotes')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->modalHeading('Cộng tiền theo danh sách')
                                        ->modalDescription('Hệ thống sẽ cộng tiền cho danh sách người chơi đã nhập.')
                                        ->modalSubmitActionLabel('Xác nhận')
                                        ->modalCancelActionLabel('Hủy bỏ')
                                        ->action(function (Action $action): void {
                                            $this->processTopupList($action);
                                        })
                                        ->successNotificationTitle('Đã gửi yêu cầu cộng tiền thành công.'),
                                ]),
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

    public function processBatchWCoin(Action $action): void
    {
        Log::info('GMOperations: Starting processBatchWCoin');
        $formData = $this->data['data'] ?? $this->data ?? [];

        $query = User::query();
        if (($formData['batch_type'] ?? '') === 'vip') {
            $query->where('tier', 'vip');
        }

        $users = $query->get(['id', 'username']);
        $count = $users->count();

        if ($count === 0) {
            Notification::make()->title('Không tìm thấy người chơi nào.')->warning()->send();
            $action->cancel();

            return;
        }

        $amount = (int) ($formData['batch_amount'] ?? 0);
        if ($amount <= 0) {
            Notification::make()->title('Số lượng WCoin phải lớn hơn 0.')->danger()->send();
            $action->cancel();

            return;
        }

        foreach ($users as $user) {
            $log = gm_log([
                'action' => 'charge_wcoin',
                'target' => $user->username,
                'payload' => [
                    'target_user_id' => $user->id,
                    'amount' => $amount,
                    'reason' => $formData['batch_reason'] ?? '',
                ],
            ]);

            ExecuteGmCommand::dispatch($log->id);
        }

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

    public function processTopupList(Action $action): void
    {
        Log::info('GMOperations: Starting processTopupList');
        $formData = $this->data['data'] ?? $this->data ?? [];

        $usernames = array_filter(array_map('trim', explode("\n", $formData['topup_player_list'] ?? '')));

        if (empty($usernames)) {
            Notification::make()->title('Danh sách người chơi trống.')->danger()->send();
            $action->halt();

            return;
        }

        $wcoinAmount = (int) ($formData['topup_wcoin_amount'] ?? 0);
        $wpointAmount = (int) ($formData['topup_wpoint_amount'] ?? 0);

        if ($wcoinAmount <= 0 && $wpointAmount <= 0) {
            Notification::make()->title('Phải nhập số lượng cho ít nhất 1 loại tiền lớn hơn 0.')->danger()->send();
            $action->halt();

            return;
        }

        $reason = $formData['topup_reason'] ?? '';

        foreach ($usernames as $username) {
            $user = User::where('username', $username)->first(['id', 'username']);
            if (! $user) {
                Log::warning("Topup List: User not found for account {$username}. Skipping.");

                continue;
            }

            if ($wcoinAmount > 0) {
                $log = gm_log([
                    'action' => 'charge_wcoin',
                    'target' => $user->username,
                    'payload' => [
                        'target_user_id' => $user->id,
                        'amount' => $wcoinAmount,
                        'reason' => $reason,
                    ],
                ]);
                ExecuteGmCommand::dispatch($log->id);
            }

            if ($wpointAmount > 0) {
                $log2 = gm_log([
                    'action' => 'charge_wpoint',
                    'target' => $user->username,
                    'payload' => [
                        'target_user_id' => $user->id,
                        'amount' => $wpointAmount,
                        'reason' => $reason,
                    ],
                ]);
                ExecuteGmCommand::dispatch($log2->id);
            }
        }

        $action->success();
    }
}
