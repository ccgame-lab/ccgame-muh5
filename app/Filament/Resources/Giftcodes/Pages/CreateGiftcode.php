<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes\Pages;

use App\Filament\Resources\Giftcodes\GiftcodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftcode extends CreateRecord
{
    protected static string $resource = GiftcodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
