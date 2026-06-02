<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GameRankingService;

class HallOfFameController extends Controller
{
    /**
     * SDK ranking payload.
     *
     * Mỗi game clone chỉ cần sửa array này + đổi extra_columns nếu DB khác.
     * Output fields đã normalize: name, level, zs, power, vip.
     */
    public function sdkPayload(GameRankingService $service): array
    {
        return [
            [
                'key' => 'zs',
                'label' => 'Chuyển Sinh',
                'metric' => 'zs',
                'secondary_metric' => 'level',
                'secondary_label' => 'Cấp',
                'players' => $service->topActors([
                    'sort' => 'zs',
                    'sort_secondary' => 'level',
                    'limit' => 10,
                ]),
            ],
            [
                'key' => 'power',
                'label' => 'Lực Chiến',
                'metric' => 'power',
                'secondary_metric' => 'zs',
                'secondary_label' => 'ZS',
                'players' => $service->topActors([
                    'sort' => 'power',
                    'sort_secondary' => 'zs',
                    'limit' => 10,
                ]),
            ],
        ];
    }
}
