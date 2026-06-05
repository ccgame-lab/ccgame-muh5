<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Changelog;
use App\Models\Server;
use Carbon\Carbon;

class AnnouncementController extends Controller
{
    /**
     * @return list<array<string, mixed>>
     */
    public function sdkPayload(): array
    {
        try {
            return Changelog::published()
                ->orderByDesc('version_date')
                ->limit(5)
                ->get()
                ->map(function (Changelog $changelog): array {
                    /** @var Server|null $server */
                    $server = $changelog->server;

                    return [
                        'id' => $changelog->id,
                        'title' => $changelog->title,
                        'content' => $changelog->player_notes,
                        'body' => $changelog->player_notes,
                        'date' => Carbon::parse($changelog->version_date)->format('Y-m-d'),
                        'server' => $server?->name,
                        'created_at' => $changelog->created_at?->toDateTimeString(),
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
