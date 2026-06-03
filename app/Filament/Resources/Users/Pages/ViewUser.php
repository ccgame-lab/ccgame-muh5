<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Users\GmActionLogWidget;
use App\Filament\Widgets\Users\PointTransactionWidget;
use App\Services\PointService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    // ── Section A: User Identity (read-only) ──

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->schema([
                TextEntry::make('username'),
                TextEntry::make('portal_uid')->label('Portal UID'),
                TextEntry::make('email')->placeholder('—'),
                TextEntry::make('tier')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'vip' ? 'warning' : 'gray'),
                TextEntry::make('last_login_at')
                    ->label('Đăng nhập cuối')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('last_login_ip')
                    ->label('IP cuối')
                    ->placeholder('—'),
            ]);
    }

    // ── Layout: infolist + footer widgets ──

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->hasInfolist()
                    ? $this->getInfolistContentComponent()
                    : $this->getFormContentComponent(),
                EmbeddedSchema::make('footerWidgets'),
            ]);
    }

    // ── Header actions: all inline operations ──

    protected function getHeaderActions(): array
    {
        $user = $this->getRecord();

        return [

            Action::make('updateTier')
                ->label('Tier: '.strtoupper((string) $user->tier))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Select::make('tier')
                        ->label('Tier')
                        ->options(['free' => 'Free', 'vip' => 'VIP'])
                        ->default($user->tier)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update(['tier' => $data['tier']]);
                    $this->getRecord()->refresh();
                    Notification::make()->title('Đã cập nhật')->body('Tier: '.strtoupper($data['tier']))->success()->send();
                }),

            Action::make('checkinBoost')
                ->label('Boost')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->form([
                    DateTimePicker::make('checkin_boost_expires_at')
                        ->label('Hết hạn')
                        ->default($user->checkin_boost_expires_at),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update(['checkin_boost_expires_at' => $data['checkin_boost_expires_at'] ?? null]);
                    $this->getRecord()->refresh();
                    Notification::make()->title('Đã cập nhật')->body('Boost updated.')->success()->send();
                }),

            Action::make('creditPoints')
                ->label('Cộng POINT')
                ->color('success')
                ->icon('heroicon-m-plus')
                ->form([
                    TextInput::make('amount')
                        ->label('Số lượng')->numeric()->minValue(1)->required(),
                    TextInput::make('reason')
                        ->label('Lý do')->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $amount = (int) $data['amount'];
                    $reason = trim($data['reason'] ?? '');
                    try {
                        $s = app(PointService::class);
                        $nb = $s->credit($this->getRecord(), $amount, 'gm_credit', $reason ?: 'GM cộng điểm', ['gm_reason' => $reason, 'actor_id' => auth()->id()]);
                        $this->getRecord()->refresh();
                        Notification::make()->title('Đã cộng')->body('+'.number_format($amount).' POINT (SD: '.number_format($nb).')')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Lỗi')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('debitPoints')
                ->label('Trừ POINT')
                ->color('danger')
                ->icon('heroicon-m-minus')
                ->form([
                    TextInput::make('amount')
                        ->label('Số lượng')->numeric()->minValue(1)->required(),
                    TextInput::make('reason')
                        ->label('Lý do')->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $amount = (int) $data['amount'];
                    $reason = trim($data['reason'] ?? '');
                    try {
                        $s = app(PointService::class);
                        $nb = $s->debit($this->getRecord(), $amount, 'gm_debit', ['gm_reason' => $reason, 'actor_id' => auth()->id()]);
                        $this->getRecord()->refresh();
                        Notification::make()->title('Đã trừ')->body('-'.number_format($amount).' POINT (SD: '.number_format($nb).')')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Lỗi')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('edit')
                ->label('Sửa thông tin')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function getWidgetData(): array
    {
        $record = $this->getRecord();

        return [
            'userId'         => $record->id,
            'targetUsername' => $record->username,
        ];
    }

    // ── Footer widgets (tables) ──

    /** @return array<class-string> */
    protected function getFooterWidgets(): array
    {
        return [
            PointTransactionWidget::class,
            GmActionLogWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
