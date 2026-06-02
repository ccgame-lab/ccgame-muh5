<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GameRankingService;

class HallOfFameController extends Controller
{
    /**
     * SDK bootstrap payload: lightweight snapshot of key rankings.
     *
     * Mỗi category định nghĩa display_metric để SDK JS biết nên highlight cột nào.
     * Game khác chỉ cần copy array này + đổi extra_columns + sort.
     */
    public function sdkPayload(GameRankingService $service): array
    {
        return [
            [
                'key' => 'reborn',
                'label' => 'Chuyển Sinh',
                'display_metric' => 'zhuansheng_lv',
                'display_label' => 'ZS',
                'secondary_label' => 'Cấp',
                'secondary_metric' => 'level',
                'players' => $service->topActors([
                    'extra_columns' => ['zhuansheng_lv', 'level', 'totalpower'],
                    'sort' => 'zhuansheng_lv',
                    'limit' => 10,
                ]),
            ],
            [
                'key' => 'power',
                'label' => 'Lực Chiến',
                'display_metric' => 'totalpower',
                'display_label' => 'Power',
                'secondary_label' => 'Cấp',
                'secondary_metric' => 'level',
                'players' => $service->topActors([
                    'extra_columns' => ['totalpower', 'level', 'zhuansheng_lv'],
                    'sort' => 'totalpower',
                    'limit' => 10,
                ]),
            ],
        ];
    }
}
