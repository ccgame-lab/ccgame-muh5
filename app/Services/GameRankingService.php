<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameRankingService
{
    private const DEFAULT_LIMIT = 50;

    /**
     * Lấy top actors từ game DB cho một danh mục ranking.
     *
     * Mỗi game engine có thể định nghĩa config riêng:
     * [
     *   'columns' => ['actorname', 'level', 'totalpower'],
     *   'sort' => 'totalpower',
     *   'label' => 'Lực Chiến',
     * ]
     */
    public function topActors(array $config): array
    {
        $servers = Server::query()
            ->where('status', '!=', Server::STATUS_MAINTENANCE)
            ->get();

        $all = collect();
        foreach ($servers as $server) {
            $conn = $server->db_connection_name;
            if (! $conn) {
                continue;
            }
            try {
                $rows = DB::connection($conn)
                    ->table('actors')
                    ->select(array_merge(['actorname', 'accountname', 'level', 'job', 'serverindex'], $config['extra_columns'] ?? []))
                    ->where('level', '>', 1)
                    ->get()
                    ->map(fn ($a) => $this->format($a, $config, $server->name));

                $all = $all->merge($rows);
            } catch (\Throwable) {
                continue;
            }
        }

        return $all
            ->sortByDesc($config['sort'] ?? 'level')
            ->take($config['limit'] ?? self::DEFAULT_LIMIT)
            ->values()
            ->all();
    }

    private function format(object $actor, array $config, string $serverName): array
    {
        $entry = [
            'name' => $actor->actorname,
            'level' => $actor->level,
            'job' => $actor->job,
            'server' => $serverName,
        ];

        foreach ($config['extra_columns'] ?? [] as $col) {
            $entry[$col] = $actor->$col ?? null;
        }

        return $entry;
    }
}
