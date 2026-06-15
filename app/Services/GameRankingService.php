<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

class GameRankingService
{
    private const DEFAULT_LIMIT = 50;

    /** Map ten field normalize (output) -> cot that trong bang actors, de day orderBy xuong DB. */
    private const COLUMN_MAP = [
        'name' => 'actorname',
        'level' => 'level',
        'zs' => 'zhuansheng_lv',
        'power' => 'totalpower',
        'vip' => 'vip_level',
        'job' => 'job',
    ];

    /**
     * Lấy top actors từ game DB cho một danh mục ranking.
     *
     * Output fields luôn được normalize: name, level, zs, power, vip, job, server.
     * Game engine khác chỉ cần đổi extra_columns và sort trong controller.
     */
    public function topActors(array $config): array
    {
        $servers = Server::query()
            ->where('status', '!=', Server::STATUS_MAINTENANCE)
            ->get();

        $all = collect();
        $sortPrimary = $config['sort'] ?? 'level';
        $sortSecondary = $config['sort_secondary'] ?? null;
        $limit = $config['limit'] ?? self::DEFAULT_LIMIT;

        $primaryCol = self::COLUMN_MAP[$sortPrimary] ?? null;
        $secondaryCol = $sortSecondary ? (self::COLUMN_MAP[$sortSecondary] ?? null) : null;

        foreach ($servers as $server) {
            $conn = $server->db_connection_name;
            if (! $conn) {
                continue;
            }
            try {
                $query = DB::connection($conn)
                    ->table('actors')
                    ->select(array_merge(
                        ['actorname', 'level', 'job', 'vip_level', 'zhuansheng_lv', 'totalpower'],
                        $config['extra_columns'] ?? [],
                    ))
                    ->where('level', '>', 1);

                // Day sort + limit xuong DB khi sort key map duoc cot that, tranh keo
                // toan bo bang actors vao PHP. Khong map duoc thi giu hanh vi cu (lay het).
                if ($primaryCol) {
                    $query->orderByDesc($primaryCol);
                    if ($secondaryCol) {
                        $query->orderByDesc($secondaryCol);
                    }
                    $query->limit($limit);
                }

                $rows = $query->get()->map(fn ($a) => $this->format($a, $server->name));

                $all = $all->merge($rows);
            } catch (\Throwable) {
                continue;
            }
        }

        $sorted = $all->sortByDesc(function ($actor) use ($sortPrimary, $sortSecondary) {
            $primary = $actor[$sortPrimary] ?? 0;

            if ($sortSecondary) {
                $secondary = $actor[$sortSecondary] ?? 0;

                return [$primary, $secondary];
            }

            return $primary;
        });

        return $sorted
            ->take($limit)
            ->values()
            ->map(fn (array $item, int $i) => array_merge(['rank' => $i + 1], $item))
            ->all();
    }

    /**
     * Normalize actor fields về contract chuẩn.
     */
    private function format(object $actor, string $serverName): array
    {
        return [
            'name' => $actor->actorname,
            'level' => (int) $actor->level,
            'zs' => (int) $actor->zhuansheng_lv,
            'power' => (int) $actor->totalpower,
            'vip' => (int) $actor->vip_level,
            'job' => (int) $actor->job,
            'server' => $serverName,
        ];
    }
}
