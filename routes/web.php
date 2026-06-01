<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Play Controller
use App\Http\Controllers\PlayController;

// D1/D2 Completed Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\Auth\GreenJadeAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home Entrance
Route::get('/', function () {
    return view('welcome');
});

// GreenJade Auth Routes
Route::middleware(['web'])->group(function () {
    Route::get('/login', [GreenJadeAuthController::class, 'loginBridge'])->name('login');
    Route::get('/auth/redirect', [GreenJadeAuthController::class, 'redirect'])->name('auth.greenjade.login.perform');
    Route::get('/auth/callback', [GreenJadeAuthController::class, 'callback'])->name('greenjade.callback');
    Route::post('/logout', [GreenJadeAuthController::class, 'logout'])->name('logout');
    Route::get('/auth/logout', [GreenJadeAuthController::class, 'performLogout'])->name('auth.greenjade.logout.perform');
});

// Unified Game entrance with signed token query support
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// Public Read-Only Routes (Web middleware applied implicitly or explicitly if needed)
Route::middleware(['web'])->group(function () {
    Route::get('/hall-of-fame', [HallOfFameController::class, 'index'])->name('hall-of-fame');
    Route::get('/hall-of-fame/rankings', [HallOfFameController::class, 'rankings'])->name('hall-of-fame.rankings');
});

// Auth-Protected / Session Middleware Group
Route::middleware([
    'web',
    'auth',
])->group(function () {

    // Dashboard Controller Endpoints
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/checkin-status', [DashboardController::class, 'checkinStatus'])->name('dashboard.checkin-status');

    // Announcement Controller Endpoints
    Route::get('/announcements/latest', [AnnouncementController::class, 'latest'])->name('announcements.latest');
    Route::post('/announcements/ack', [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');

    // Hall Of Fame Controller (Auth required for personal rank)
    Route::get('/hall-of-fame/my-rank', [HallOfFameController::class, 'myRank'])->name('hall-of-fame.my-rank');

    // Transaction History Controller Endpoints
    Route::prefix('history')->name('history.')->group(function () {
        Route::get('/wpoint', [TransactionHistoryController::class, 'wpointHistory'])->name('wpoint');
        Route::get('/wcoin', [TransactionHistoryController::class, 'wcoinHistory'])->name('wcoin');
    });

    // Play Controller Auth-Protected game launchers
    Route::get('/playgame/{server}', [PlayController::class, 'game'])->name('play.game');
});
