<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Play Controller
use App\Http\Controllers\PlayController;

// Public Read Controllers
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;

/*
|--------------------------------------------------------------------------
| Web Routes — Launch-token-only baseline
|--------------------------------------------------------------------------
| ccgame-web owns GreenJade OAuth and signs launch tokens.
| Laravel MUH5 only verifies launch tokens and renders the game iframe.
| No direct OAuth to id.greenjade.net from this app.
|--------------------------------------------------------------------------
*/

// Home Entrance
Route::get('/', function () {
    return view('welcome');
});

// Login stub — hands off to ccgame-web (the correct OAuth owner)
Route::get('/login', function () {
    return redirect('https://ccgame.org');
})->name('login');

// Unified Game entrance — accepts signed launch token via ?launch=<token>
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// Public Routes
Route::middleware(['web'])->group(function () {
    Route::get('/hall-of-fame', [HallOfFameController::class, 'index'])->name('hall-of-fame');
    Route::get('/hall-of-fame/rankings', [HallOfFameController::class, 'rankings'])->name('hall-of-fame.rankings');

    // Announcements — auth-optional: works without session, user() returns null safely
    Route::get('/announcements/latest', [AnnouncementController::class, 'latest'])->name('announcements.latest');
    Route::post('/announcements/ack', [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');
});
