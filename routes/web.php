<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Unified Game entrance with signed token query support
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// Auth-Protected / Session Middleware Group
Route::middleware([
    'web',
    'auth',
])->group(function () {
    // Play Controller Auth-Protected game launchers
    Route::get('/playgame/{server}', [PlayController::class, 'game'])->name('play.game');
});
