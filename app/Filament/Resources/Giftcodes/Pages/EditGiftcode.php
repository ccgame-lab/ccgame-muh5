<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGiftcode extends EditRecord
{
    protected static string $resource = GiftcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['reward_data']) && is_array($data['reward_data'])) {
            if (($data['reward_type'] ?? '') === 'portal_credit') {
                $data['reward_currency'] = $data['reward_data']['currency'] ?? 'points';
                $data['reward_amount'] = $data['reward_data']['amount'] ?? 0;
            } elseif (($data['reward_type'] ?? '') === 'game_mail') {
                $data['mail_title'] = $data['reward_data']['title'] ?? '';
                $data['mail_body'] = $data['reward_data']['body'] ?? '';
                $data['items_list'] = $data['reward_data']['items'] ?? [];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['reward_type'] === 'portal_credit') {
            $data['reward_data'] = [
                'currency' => $data['reward_currency'] ?? 'points',
                'amount' => (int) ($data['reward_amount'] ?? 0),
            ];
        } elseif ($data['reward_type'] === 'game_mail') {
            $data['reward_data'] = [
                'title' => $data['mail_title'] ?? '',
                'body' => $data['mail_body'] ?? '',
                'items' => $data['items_list'] ?? [],
            ];
        }

        unset(
            $data['reward_currency'],
            $data['reward_amount'],
            $data['mail_title'],
            $data['mail_body'],
            $data['items_list']
        );

        return $data;
    }
}
