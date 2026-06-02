<?php

declare(strict_types=1);

use App\Models\Changelog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/changelogs', function (Request $request): JsonResponse {
    $serverId = (int) $request->query('server_id', 1);

    $changelogs = Changelog::published()
        ->byServer($serverId)
        ->orderByDesc('version_date')
        ->get(['version_date', 'title', 'player_notes']);

    return response()->json($changelogs->map(fn (Changelog $c) => [
        'date' => $c->version_date->format('Y-m-d'),
        'title' => $c->title,
        'notes' => $c->player_notes,
    ]));
});
