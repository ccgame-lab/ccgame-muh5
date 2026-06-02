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

Route::get('/api/sdk/bootstrap', function () {
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

    return response()->json([
        'user' => [
            'name' => 'Khách',
            'username' => 'guest',
            'wallet' => ['wcoin' => 0, 'wpoint' => 0],
        ],
        'announcements' => $announcements,
        'transactions' => ['wcoin' => [], 'wpoint' => []],
        'ranking' => $hallOfFame['legends'] ?? [],
        'hallOfFame' => $hallOfFame,
        'diamond' => ['balance' => 0],
    ]);
})->name('sdk.bootstrap');
