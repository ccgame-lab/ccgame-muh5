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
    $s = null;
    try {
        $s = \App\Models\Server::find(1);
        if ($s) { $serverName = $s->name; $serverId = (string) $s->id; }
    } catch (Throwable) {}

    $username = (string) $request->query('u', '');
    $user = null;
    $player = ['id' => 0, 'name' => 'Khách', 'level' => 0, 'vip' => 0];
    $wallet = ['points' => 0];

    if ($username !== '') {
        $user = \App\Models\User::where('username', $username)->first();
        if ($user) {
            $level = 0;
            $vip = 0;
            try {
                $actor = app(\App\Services\Game\GmApiService::class)->findActor($s ?? \App\Models\Server::find(1), $username);
                $level = (int) ($actor['level'] ?? 0);
                $vip = (int) ($actor['vip_level'] ?? 0);
            } catch (\Throwable) {}

            $player = [
                'id' => $user->id,
                'name' => $user->name ?: $user->username,
                'level' => $level,
                'vip' => $vip,
            ];
            $wallet = [
                'points' => (int) $user->points,
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
        'checkin' => (function () use ($user) {
            if (! $user) {
                return ['checked_today' => false, 'streak' => 0, 'week' => array_map(fn (string $d) => ['day' => $d, 'done' => false], ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'])];
            }

            $today = \App\Models\SdkDailyCheckin::todayFor($user->id)->exists();
            $streak = \App\Models\SdkDailyCheckin::where('user_id', $user->id)->latest()->value('streak') ?? 0;
            $weekRecords = \App\Models\SdkDailyCheckin::where('user_id', $user->id)
                ->whereBetween('checked_at', [now()->startOfWeek(\Carbon\Carbon::MONDAY), now()->endOfWeek(\Carbon\Carbon::SUNDAY)])
                ->pluck('checked_at')
                ->map(fn (\Illuminate\Support\Carbon $d) => $d->format('Y-m-d'))
                ->toArray();
            $dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $week = [];
            foreach (range(0, 6) as $i) {
                $date = now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays($i)->format('Y-m-d');
                $week[] = ['day' => $dayNames[$i], 'done' => in_array($date, $weekRecords, true)];
            }

            return ['checked_today' => $today, 'streak' => $streak, 'week' => $week];
        })(),
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

// ─── SDK Daily Check-in ────────────────────────────────────────────────────
Route::post('/api/sdk/checkin', function (\Illuminate\Http\Request $request) {
    $username = (string) $request->input('u', '');
    $user = \App\Models\User::where('username', $username)->first();

    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $today = now()->startOfDay();

    if (\App\Models\SdkDailyCheckin::todayFor($user->id)->exists()) {
        $streak = \App\Models\SdkDailyCheckin::where('user_id', $user->id)->latest()->value('streak') ?? 0;

        return response()->json([
            'status' => 'already',
            'streak' => $streak,
        ]);
    }

    $yesterday = $today->copy()->subDay()->format('Y-m-d');
    $yesterdayRecord = \App\Models\SdkDailyCheckin::where('user_id', $user->id)
        ->whereDate('checked_at', $yesterday)
        ->first();

    $streak = $yesterdayRecord ? $yesterdayRecord->streak + 1 : 1;
    $tomAmount = $streak >= 7 ? 200 : 50;

    $userId = $user->id;

    \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $today, $streak, $tomAmount) {
        $lockedUser = \App\Models\User::where('id', $userId)->lockForUpdate()->firstOrFail();
        $lockedUser->increment('points', $tomAmount);

        \App\Models\SdkDailyCheckin::create([
            'user_id' => $userId,
            'checked_at' => $today,
            'streak' => $streak,
            'reward_given' => true,
        ]);
    });

    return response()->json([
        'status' => 'ok',
        'streak' => $streak,
        'reward' => ['tom' => $tomAmount],
        'message' => "Điểm danh thành công! +{$tomAmount} TOM",
    ]);
})->name('sdk.checkin');

// ─── SDK Giftcode Validate ─────────────────────────────────────────────────
Route::get('/api/sdk/giftcode/validate', function (\Illuminate\Http\Request $request) {
    $code = (string) $request->query('code', '');
    $username = (string) $request->query('u', '');

    if ($code === '') {
        return response()->json(['valid' => false, 'message' => 'Thiếu mã giftcode.', 'reward' => null]);
    }

    $user = \App\Models\User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['valid' => false, 'message' => 'Phiên chơi chưa xác thực, hãy tải lại trang.', 'reward' => null]);
    }

    $giftcode = \App\Models\Giftcode::where('code', $code)->first();

    if (! $giftcode) {
        return response()->json(['valid' => false, 'message' => 'Mã giftcode không tồn tại.', 'reward' => null]);
    }

    if (! $giftcode->isUsable()) {
        if ($giftcode->expires_at && $giftcode->expires_at->isPast()) {
            return response()->json(['valid' => false, 'message' => 'Mã giftcode đã hết hạn.', 'reward' => null]);
        }

        return response()->json(['valid' => false, 'message' => 'Mã giftcode đã hết lượt sử dụng.', 'reward' => null]);
    }

    $alreadyRedeemed = \App\Models\GiftcodeRedemption::where('giftcode_id', $giftcode->id)
        ->where('user_id', $user->id)
        ->exists();

    if ($alreadyRedeemed) {
        return response()->json(['valid' => false, 'message' => 'Bạn đã sử dụng mã này rồi.', 'reward' => null]);
    }

    $rewardAmount = (int) ($giftcode->reward_data['reward_amount'] ?? $giftcode->reward_data['amount'] ?? 0);

    return response()->json([
        'valid' => true,
        'message' => 'Mã giftcode hợp lệ!',
        'reward' => [
            'type' => $giftcode->reward_type,
            'amount' => $rewardAmount,
        ],
    ]);
})->name('sdk.giftcode.validate');
