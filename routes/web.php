<?php

declare(strict_types=1);

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\PlayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Launch-token-only baseline
|--------------------------------------------------------------------------
| ccgame-web owns GreenJade OAuth and signs launch tokens.
| Laravel MUH5 only verifies launch tokens and renders the game iframe.
| No direct OAuth to id.greenjade.net from this app.
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/play');
});

Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

Route::get('/api/sdk/bootstrap', function (\Illuminate\Http\Request $request) {
    $hallOfFame = [
        'servers' => [],
        'rankings' => [],
    ];
    $announcements = [];

    try {
        $hallOfFame = app(HallOfFameController::class)->sdkPayload();
    } catch (Throwable $e) {
        report($e);
    }

    try {
        $announcements = app(AnnouncementController::class)->sdkPayload();
    } catch (Throwable $e) {
        report($e);
    }

    $features = [];
    try {
        $features = \App\Models\SdkFeature::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['key', 'label', 'status', 'url', 'note'])
            ->map(fn ($f) => [
                'key'    => $f->key,
                'title'  => $f->label,  // SDK JS reads f.title
                'label'  => $f->label,  // keep for backward compat
                'status' => $f->status,
                'url'    => $f->url ?? '',
                'note'   => $f->note ?? '',
            ])
            ->values()
            ->toArray();
    } catch (Throwable $e) {
        report($e);
    }

    $userArray = [
        'name' => 'Khách',
        'username' => 'guest',
        'wallet' => ['wcoin' => 0, 'wpoint' => 0],
    ];

    $username = $request->query('u');
    if (!empty($username)) {
        $user = \App\Models\User::where('username', $username)->first();
        if ($user) {
            $userArray = [
                'name' => $user->name ?: $user->username,
                'username' => $user->username,
                'wallet' => [
                    'wcoin' => (int) $user->wcoin,
                    'wpoint' => (int) $user->wpoint,
                ],
            ];
        } else {
            // Fallback for missing user in DB but passed in URL
            $userArray['username'] = $username;
        }
    }

    return response()->json([
        'user' => $userArray,
        'announcements' => $announcements,
        'transactions' => ['wcoin' => [], 'wpoint' => []],
        'ranking' => $hallOfFame['legends'] ?? [],
        'hallOfFame' => $hallOfFame,
        'diamond' => ['balance' => 0],
        'features' => $features,
    ]);
})->name('sdk.bootstrap');
