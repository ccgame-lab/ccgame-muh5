<?php

declare(strict_types=1);

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\PlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/play'));
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// SDK bootstrap — lightweight, no ranking
Route::get('/api/sdk/bootstrap', function (\Illuminate\Http\Request $request) {
    $announcements = [];
    try { $announcements = app(AnnouncementController::class)->sdkPayload(); } catch (Throwable $e) { report($e); }

    $serverName = 'CCGame';
    $serverId = '1';
    try {
        $s = \App\Models\Server::find(1);
        if ($s) { $serverName = $s->name; $serverId = (string) $s->id; }
    } catch (Throwable) {}

    $username = (string) $request->query('u', '');
    $player = ['id' => 0, 'name' => 'Khách', 'level' => 0, 'vip' => 0];
    $wallet = ['tom' => 0, 'wcoin' => 0, 'wpoint' => 0];

    if ($username !== '') {
        $user = \App\Models\User::where('username', $username)->first();
        if ($user) {
            $player = [
                'id' => $user->id,
                'name' => $user->name ?: $user->username,
                'level' => 0,
                'vip' => 0,
            ];
            $wallet = [
                'tom' => (int) $user->wcoin,
                'wcoin' => (int) $user->wcoin,
                'wpoint' => (int) $user->wpoint,
            ];
        }
    }

    $features = [];
    try {
        $features = \App\Models\SdkFeature::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['key', 'label', 'status', 'url', 'note'])
            ->map(fn ($f) => [
                'key' => $f->key,
                'label' => $f->label,
                'active' => $f->status === 'active',
                'href' => $f->url ?? '',
                'note' => $f->note ?? '',
            ])->values()->toArray();
    } catch (Throwable $e) { report($e); }

    return response()->json([
        'server' => ['id' => $serverId, 'name' => $serverName],
        'player' => $player,
        'wallet' => $wallet,
        'tabs' => [
            ['key' => 'overview', 'label' => 'Tổng quan'],
            ['key' => 'donate', 'label' => 'Đặc quyền'],
            ['key' => 'ranking', 'label' => 'BXH'],
            ['key' => 'changelog', 'label' => 'Cập nhật'],
        ],
        'features' => $features,
        'changelog' => $announcements,
    ]);
})->name('sdk.bootstrap');

// SDK ranking — lazy, fetched only when BXH tab opened
Route::get('/api/sdk/ranking', function () {
    try {
        $rankings = app(HallOfFameController::class)->sdkPayload(app(\App\Services\GameRankingService::class));
    } catch (Throwable $e) {
        report($e);

        return response()->json(['types' => [], 'items' => []]);
    }

    $types = [];
    $items = [];
    foreach ($rankings as $r) {
        $types[] = [
            'key' => $r['key'],
            'label' => $r['label'],
            'metric' => $r['metric'],
            'secondary_metric' => $r['secondary_metric'] ?? '',
            'secondary_label' => $r['secondary_label'] ?? '',
        ];
        $items[$r['key']] = $r['players'];
    }

    return response()->json(['types' => $types, 'items' => $items]);
});

// ─── Legacy Mining API (simplified maintenance-based idle faucet) ─────────────

// Quote — read-only mining state for UI
Route::get('/api/mining/quote', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('username', (string) $request->query('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    return response()->json(app(\App\Services\LegacyMiningService::class)->quote($user));
})->name('mining.quote');

// Maintain — reset efficiency to 100%
Route::post('/api/mining/maintain', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('username', (string) $request->input('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    try {
        $result = app(\App\Services\LegacyMiningService::class)->maintain($user);

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.maintain');

// Claim — collect accumulated KC
Route::post('/api/mining/claim', function (\Illuminate\Http\Request $request) {
    $username = (string) $request->input('u', '');
    $user = \App\Models\User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $serverId = (int) ($request->input('server_id', 1));

    try {
        $result = app(\App\Services\LegacyMiningService::class)->claim($user, $serverId);

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.claim');
