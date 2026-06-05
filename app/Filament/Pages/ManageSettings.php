<?php

namespace App\Filament\Pages;

use App\Settings\GameSettings;
use App\Settings\GeneralSettings;
use App\Settings\NotificationSettings;
use App\Settings\PaymentSettings;
use App\Settings\SecuritySettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * @property Form $form
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Cài đặt';

    protected static ?string $title = 'Cài đặt hệ thống';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // General
            'server_name' => app(GeneralSettings::class)->server_name,
            'server_open' => app(GeneralSettings::class)->server_open,
            'maintenance_mode' => app(GeneralSettings::class)->maintenance_mode,

            // Game
            'rate_per_hour' => app(GameSettings::class)->rate_per_hour,
            'daily_cap' => app(GameSettings::class)->daily_cap,
            'maintenance_cooldown_hours' => app(GameSettings::class)->maintenance_cooldown_hours,

            // Payment
            'exchange_rate' => app(PaymentSettings::class)->exchange_rate,
            'max_exchange_per_request' => app(PaymentSettings::class)->max_exchange_per_request,
            'spin_cost' => app(PaymentSettings::class)->spin_cost,
            'spin_daily_limit' => app(PaymentSettings::class)->spin_daily_limit,

            // Security
            'allowed_usernames' => app(SecuritySettings::class)->allowed_usernames,
            'gm_alert_threshold' => app(SecuritySettings::class)->gm_alert_threshold,

            // Notification
            'discord_webhook' => app(NotificationSettings::class)->discord_webhook,
            'admin_email' => app(NotificationSettings::class)->admin_email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Chung')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make()->schema([
                                    TextInput::make('server_name')
                                        ->label('Tên server')
                                        ->required(),
                                    Toggle::make('server_open')
                                        ->label('Server mở cửa'),
                                    Toggle::make('maintenance_mode')
                                        ->label('Chế độ bảo trì'),
                                ]),
                            ]),

                        Tab::make('Game')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Section::make('Legacy Mining')->schema([
                                    TextInput::make('rate_per_hour')
                                        ->label('Rate đào / giờ')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix('Legacy'),
                                    TextInput::make('daily_cap')
                                        ->label('Giới hạn đào / ngày')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix('Legacy'),
                                    TextInput::make('maintenance_cooldown_hours')
                                        ->label('Cooldown bảo trì')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix('giờ'),
                                ]),
                            ]),

                        Tab::make('Nạp tiền')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Quy đổi')->schema([
                                    TextInput::make('exchange_rate')
                                        ->label('Tỷ lệ quy đổi')
                                        ->numeric()
                                        ->minValue(1)
                                        ->helperText('1 VND = X coin'),
                                    TextInput::make('max_exchange_per_request')
                                        ->label('Quy đổi tối đa / lần')
                                        ->numeric()
                                        ->minValue(1),
                                ]),
                                Section::make('Spin')->schema([
                                    TextInput::make('spin_cost')
                                        ->label('Chi phí mỗi lần spin')
                                        ->numeric()
                                        ->minValue(1),
                                    TextInput::make('spin_daily_limit')
                                        ->label('Giới hạn spin / ngày')
                                        ->numeric()
                                        ->minValue(1),
                                ]),
                            ]),

                        Tab::make('Bảo mật')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make()->schema([
                                    TagsInput::make('allowed_usernames')
                                        ->label('Username được phép khi server đóng')
                                        ->placeholder('Nhập username rồi Enter...'),
                                    TextInput::make('gm_alert_threshold')
                                        ->label('Ngưỡng cảnh báo GM')
                                        ->numeric()
                                        ->minValue(0)
                                        ->suffix('Legacy'),
                                ]),
                            ]),

                        Tab::make('Thông báo')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Section::make()->schema([
                                    TextInput::make('discord_webhook')
                                        ->label('Discord Webhook URL')
                                        ->url()
                                        ->nullable(),
                                    TextInput::make('admin_email')
                                        ->label('Email quản trị')
                                        ->email()
                                        ->nullable(),
                                ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu cài đặt')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $general = app(GeneralSettings::class);
        $general->server_name = $data['server_name'];
        $general->server_open = (bool) $data['server_open'];
        $general->maintenance_mode = (bool) $data['maintenance_mode'];
        $general->save();

        $game = app(GameSettings::class);
        $game->rate_per_hour = (int) $data['rate_per_hour'];
        $game->daily_cap = (int) $data['daily_cap'];
        $game->maintenance_cooldown_hours = (int) $data['maintenance_cooldown_hours'];
        $game->save();

        $payment = app(PaymentSettings::class);
        $payment->exchange_rate = (int) $data['exchange_rate'];
        $payment->max_exchange_per_request = (int) $data['max_exchange_per_request'];
        $payment->spin_cost = (int) $data['spin_cost'];
        $payment->spin_daily_limit = (int) $data['spin_daily_limit'];
        $payment->save();

        $security = app(SecuritySettings::class);
        $security->allowed_usernames = $data['allowed_usernames'] ?? [];
        $security->gm_alert_threshold = (int) $data['gm_alert_threshold'];
        $security->save();

        $notification = app(NotificationSettings::class);
        $notification->discord_webhook = $data['discord_webhook'] ?: null;
        $notification->admin_email = $data['admin_email'] ?: null;
        $notification->save();

        Notification::make()
            ->title('Đã lưu cài đặt')
            ->success()
            ->send();
    }
}
