<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\GmAction;
use App\Models\User;
use App\Services\PointService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ViewUser extends Page
{
    protected static string $resource = UserResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    protected string $view = 'filament.resources.users.pages.view-user';

    public User $record;

    /** @var array<string, mixed> */
    public array $pointForm = [
        'amount' => 0,
        'reason' => '',
    ];

    public function mount(User|int|string $record): void
    {
        $this->record = $record instanceof User ? $record : User::findOrFail((int) $record);
    }

    public function getPointTransactions(): Collection
    {
        return $this->record->pointTransactions()
            ->latest()
            ->take(50)
            ->get();
    }

    public function getGmActions(): Collection
    {
        return GmAction::query()
            ->where('target_user', $this->record->username)
            ->latest()
            ->take(20)
            ->get();
    }

    public function updateTier(string $tier): void
    {
        $this->record->update(['tier' => $tier]);
        $this->record->refresh();

        Notification::make()
            ->title('Đã cập nhật tier')
            ->body('Tier hiện tại: '.strtoupper($tier))
            ->success()
            ->send();
    }

    public function updateCheckinBoost(?string $value): void
    {
        $this->record->update([
            'checkin_boost_expires_at' => $value ?: null,
        ]);
        $this->record->refresh();

        Notification::make()
            ->title('Đã cập nhật')
            ->body('Check-in boost đã được cập nhật.')
            ->success()
            ->send();
    }

    public function creditPoints(): void
    {
        $amount = (int) ($this->pointForm['amount'] ?? 0);
        $reason = trim($this->pointForm['reason'] ?? '');

        if ($amount <= 0) {
            Notification::make()
                ->title('Lỗi')
                ->body('Số lượng phải lớn hơn 0.')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(PointService::class);
            $newBalance = $service->credit(
                $this->record,
                $amount,
                'gm_credit',
                $reason ?: 'GM cộng điểm',
                ['gm_reason' => $reason, 'actor_id' => auth()->id()]
            );

            $this->record->refresh();
            $this->pointForm = ['amount' => 0, 'reason' => ''];

            Notification::make()
                ->title('Đã cộng điểm')
                ->body('+'.number_format($amount).' POINT — Số dư mới: '.number_format($newBalance))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function debitPoints(): void
    {
        $amount = (int) ($this->pointForm['amount'] ?? 0);
        $reason = trim($this->pointForm['reason'] ?? '');

        if ($amount <= 0) {
            Notification::make()
                ->title('Lỗi')
                ->body('Số lượng phải lớn hơn 0.')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(PointService::class);
            $newBalance = $service->debit(
                $this->record,
                $amount,
                'gm_debit',
                ['gm_reason' => $reason, 'actor_id' => auth()->id()]
            );

            $this->record->refresh();
            $this->pointForm = ['amount' => 0, 'reason' => ''];

            Notification::make()
                ->title('Đã trừ điểm')
                ->body('-'.number_format($amount).' POINT — Số dư mới: '.number_format($newBalance))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Sửa thông tin')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
