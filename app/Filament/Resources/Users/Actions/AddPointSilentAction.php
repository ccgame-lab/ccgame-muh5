<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class AddPointSilentAction
{
    public static function make(): Action
    {
        return Action::make('addPointSilent')
            ->label('+ POINT (No Log)')
            ->icon('heroicon-o-plus-circle')
            ->color('warning')
            ->form([
                TextInput::make('amount')
                    ->label('Số lượng POINT')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('reason')
                    ->label('Lý do (Lưu db nội bộ)')
                    ->placeholder('Lý do cộng...')
                    ->maxLength(255),
            ])
            ->action(function (User $record, array $data): void {
                try {
                    $amount = (int) $data['amount'];

                    $record->increment('points', $amount);

                    $actionUuid = (string) Str::uuid();
                    if (function_exists('gm_log')) {
                        gm_log([
                            'action_uuid' => $actionUuid,
                            'action' => 'add_point_silent',
                            'target' => $record->username,
                            'payload' => $data,
                        ]);
                    }

                    Notification::make()
                        ->title('Thành công')
                        ->body("Đã cộng {$amount} POINT cho {$record->username} (Ẩn log).")
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
