<?php

declare(strict_types=1);

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\PlayController;
use App\Jobs\SendGameMailJob;
use App\Models\Giftcode;
use App\Models\GiftcodeRedemption;
use App\Models\SdkDailyCheckin;
use App\Models\SdkFeature;
use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use App\Services\GameRankingService;
use App\Services\LegacyMiningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', fn () => redirect('/play'));
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// SDK bootstrap — lightweight, no ranking
Route::get('/api/sdk/bootstrap', function (Request $request) {
    $announcements = [];
    try {
        $announcements = app(AnnouncementController::class)->sdkPayload();
    } catch (Throwable $e) {
        report($e);
    }

    $serverName = 'CCGame';
    $serverId = '1';
    $s = null;
    try {
        $s = Server::find(1);
        if ($s) {
            $serverName = $s->name;
            $serverId = (string) $s->id;
        }
    } catch (Throwable) {
    }

    $username = (string) $request->query('u', '');
    $user = null;
    $player = ['id' => 0, 'name' => 'Khách', 'level' => 0, 'vip' => 0];
    $wallet = ['points' => 0];

    if ($username !== '') {
        $user = User::where('username', $username)->first();
        if ($user) {
            $level = 0;
            $vip = 0;
            try {
                $actor = app(GmApiService::class)->findActor($s ?? Server::find(1), $username);
                $level = (int) ($actor['level'] ?? 0);
                $vip = (int) ($actor['vip_level'] ?? 0);
            } catch (Throwable) {
            }

            $player = [
                'id' => $user->id,
                'name' => $user->name ?: $user->username,
                'level' => $level,
                'vip' => $vip,
            ];
            $wallet = [
                'points' => (int) $user->points,
                'coin' => (int) ($user->webWallet?->balance ?? 0),
            ];
        }
    }

    $features = [];
    try {
        $features = SdkFeature::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['key', 'label', 'status', 'url', 'note'])
            ->map(fn ($f) => [
                'key' => $f->key,
                'label' => $f->label,
                'active' => $f->status === 'active',
                'href' => $f->url ?? '',
                'note' => $f->note ?? '',
            ])->values()->toArray();
    } catch (Throwable $e) {
        report($e);
    }

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

            $today = SdkDailyCheckin::todayFor($user->id)->exists();
            $streak = SdkDailyCheckin::where('user_id', $user->id)->latest()->value('streak') ?? 0;
            $weekRecords = SdkDailyCheckin::where('user_id', $user->id)
                ->whereBetween('checked_at', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])
                ->pluck('checked_at')
                ->map(fn (Illuminate\Support\Carbon $d) => $d->format('Y-m-d'))
                ->toArray();
            $dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $week = [];
            foreach (range(0, 6) as $i) {
                $date = now()->startOfWeek(Carbon::MONDAY)->addDays($i)->format('Y-m-d');
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
        $rankings = app(HallOfFameController::class)->sdkPayload(app(GameRankingService::class));
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
Route::get('/api/mining/quote', function (Request $request) {
    $user = User::where('username', (string) $request->query('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    return response()->json(app(LegacyMiningService::class)->quote($user));
})->name('mining.quote');

// Maintain — reset efficiency to 100%
Route::post('/api/mining/maintain', function (Request $request) {
    $user = User::where('username', (string) $request->input('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    try {
        $result = app(LegacyMiningService::class)->maintain($user);

        return response()->json($result);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.maintain');

// Claim — collect accumulated KC
Route::post('/api/mining/claim', function (Request $request) {
    $username = (string) $request->input('u', '');
    $user = User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $serverId = (int) ($request->input('server_id', 1));

    try {
        $result = app(LegacyMiningService::class)->claim($user, $serverId);

        return response()->json($result);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.claim');

// Modules
Route::get('/api/mining/modules', function (Request $request) {
    $user = User::where('username', (string) $request->query('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực.'], 401);
    }

    return response()->json(app(LegacyMiningService::class)->getAllModules($user->id));
})->name('mining.modules');

Route::post('/api/mining/equip-module', function (Request $request) {
    $user = User::where('username', (string) $request->input('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực.'], 401);
    }

    $moduleId = (int) $request->input('module_id');
    $slotIndex = (int) $request->input('slot_index');

    try {
        app(LegacyMiningService::class)->equipModule($user, $moduleId, $slotIndex);

        return response()->json(['success' => true]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.equip_module');

Route::post('/api/mining/unequip-module', function (Request $request) {
    $user = User::where('username', (string) $request->input('u', ''))->first();
    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực.'], 401);
    }

    $moduleId = (int) $request->input('module_id');

    try {
        app(LegacyMiningService::class)->unequipModule($user, $moduleId);

        return response()->json(['success' => true]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('mining.unequip_module');

// ─── SDK Daily Check-in ────────────────────────────────────────────────────
Route::post('/api/sdk/checkin', function (Request $request) {
    $username = (string) $request->input('u', '');
    $user = User::where('username', $username)->first();

    if (! $user) {
        return response()->json(['error' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $today = now()->startOfDay();

    if (SdkDailyCheckin::todayFor($user->id)->exists()) {
        $streak = SdkDailyCheckin::where('user_id', $user->id)->latest()->value('streak') ?? 0;

        return response()->json([
            'status' => 'already',
            'streak' => $streak,
        ]);
    }

    $yesterday = $today->copy()->subDay()->format('Y-m-d');
    $yesterdayRecord = SdkDailyCheckin::where('user_id', $user->id)
        ->whereDate('checked_at', $yesterday)
        ->first();

    $streak = $yesterdayRecord ? $yesterdayRecord->streak + 1 : 1;
    $tomAmount = $streak >= 7 ? 200 : 50;

    $userId = $user->id;

    DB::transaction(function () use ($userId, $today, $streak, $tomAmount) {
        $lockedUser = User::where('id', $userId)->lockForUpdate()->firstOrFail();
        $lockedUser->increment('points', $tomAmount);

        SdkDailyCheckin::create([
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
Route::get('/api/sdk/giftcode/validate', function (Request $request) {
    $code = (string) $request->query('code', '');
    $username = (string) $request->query('u', '');

    if ($code === '') {
        return response()->json(['valid' => false, 'message' => 'Thiếu mã giftcode.', 'reward' => null]);
    }

    $user = User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['valid' => false, 'message' => 'Phiên chơi chưa xác thực, hãy tải lại trang.', 'reward' => null]);
    }

    $giftcode = Giftcode::where('code', $code)->first();

    if (! $giftcode) {
        return response()->json(['valid' => false, 'message' => 'Mã giftcode không tồn tại.', 'reward' => null]);
    }

    if (! $giftcode->isUsable()) {
        if ($giftcode->expires_at && $giftcode->expires_at->isPast()) {
            return response()->json(['valid' => false, 'message' => 'Mã giftcode đã hết hạn.', 'reward' => null]);
        }

        return response()->json(['valid' => false, 'message' => 'Mã giftcode đã hết lượt sử dụng.', 'reward' => null]);
    }

    $alreadyRedeemed = GiftcodeRedemption::where('giftcode_id', $giftcode->id)
        ->where('user_id', $user->id)
        ->exists();

    if ($alreadyRedeemed) {
        return response()->json(['valid' => false, 'message' => 'Bạn đã sử dụng mã này rồi.', 'reward' => null]);
    }

    $rewardData = $giftcode->reward_data ?? [];
    $rewardAmount = (int) ($rewardData['reward_amount'] ?? $rewardData['amount'] ?? 0);

    return response()->json([
        'valid' => true,
        'message' => 'Mã giftcode hợp lệ!',
        'reward' => [
            'type' => $giftcode->reward_type,
            'amount' => $rewardAmount,
        ],
    ]);
})->name('sdk.giftcode.validate');

// ─── SDK Giftcode Redeem ─────────────────────────────────────────────────
Route::post('/api/sdk/giftcode/redeem', function (Request $request) {
    $code = (string) $request->input('code', '');
    $username = (string) $request->input('u', $request->input('token', ''));

    if ($code === '') {
        return response()->json(['success' => false, 'message' => 'Thiếu mã giftcode.'], 400);
    }

    $user = User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['success' => false, 'message' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $giftcode = Giftcode::where('code', $code)->first();

    if (! $giftcode) {
        return response()->json(['success' => false, 'message' => 'Mã giftcode không tồn tại.'], 404);
    }

    if (! $giftcode->isUsable()) {
        if ($giftcode->expires_at && $giftcode->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Mã giftcode đã hết hạn.'], 410);
        }

        return response()->json(['success' => false, 'message' => 'Mã giftcode đã hết lượt sử dụng.'], 409);
    }

    $alreadyRedeemed = GiftcodeRedemption::where('giftcode_id', $giftcode->id)
        ->where('user_id', $user->id)
        ->exists();

    if ($alreadyRedeemed) {
        return response()->json(['success' => false, 'message' => 'Bạn đã sử dụng mã này rồi.'], 409);
    }

    // Handle game_mail reward type
    if ($giftcode->reward_type === 'game_mail') {
        $rewardData = $giftcode->reward_data ?? [];
        $mailTitle = (string) ($rewardData['title'] ?? 'Quà tặng từ GM');
        $mailContent = (string) ($rewardData['content'] ?? 'Chúc mừng bạn nhận được quà tặng!');
        $items = $rewardData['items'] ?? [];

        // Resolve server: use giftcode.server_id if set, else server 1
        $serverId = $giftcode->server_id ?? 1;
        $server = Server::find($serverId);
        if (! $server) {
            return response()->json(['success' => false, 'message' => 'Máy chủ game không tồn tại.'], 500);
        }

        // Lookup player on game server
        try {
            $actor = app(GmApiService::class)->findActor($server, $user->username);
            $playerId = (string) $actor['actorid'];
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân vật của bạn trên máy chủ. Hãy đăng nhập vào game ít nhất một lần.'], 400);
        }

        // Build itemPayload: "1,{id},{count};..."
        $parts = [];
        foreach ($items as $item) {
            $itemId = (string) ($item['id'] ?? '');
            $itemCount = (int) ($item['count'] ?? 1);
            if ($itemId !== '') {
                $parts[] = "1,{$itemId},{$itemCount}";
            }
        }
        $itemPayload = implode(';', $parts);

        $actionUuid = (string) Str::uuid();

        DB::transaction(function () use ($user, $giftcode) {
            $lockedGiftcode = Giftcode::where('id', $giftcode->id)->lockForUpdate()->firstOrFail();

            if (! $lockedGiftcode->isUsable()) {
                if ($lockedGiftcode->expires_at && $lockedGiftcode->expires_at->isPast()) {
                    throw new Exception('Mã giftcode đã hết hạn.');
                }
                throw new Exception('Mã giftcode đã hết lượt sử dụng.');
            }

            $alreadyRedeemed = GiftcodeRedemption::where('giftcode_id', $lockedGiftcode->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyRedeemed) {
                throw new Exception('Bạn đã sử dụng mã này rồi.');
            }

            $lockedGiftcode->increment('used_count');

            GiftcodeRedemption::create([
                'giftcode_id' => $lockedGiftcode->id,
                'user_id' => $user->id,
            ]);
        });

        SendGameMailJob::dispatch($server, $playerId, $mailTitle, $mailContent, $actionUuid, $itemPayload);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi quà qua mail game! Kiểm tra hộp thư trong game để nhận vật phẩm.',
            'reward' => ['type' => 'game_mail'],
        ]);
    }

    // Only allow portal_credit reward type otherwise
    if ($giftcode->reward_type !== 'portal_credit') {
        return response()->json(['success' => false, 'message' => 'Loại giftcode này không hỗ trợ đổi qua SDK.'], 400);
    }

    $rewardData = $giftcode->reward_data ?? [];
    $rewardAmount = (int) ($rewardData['reward_amount'] ?? $rewardData['amount'] ?? 0);

    if ($rewardAmount <= 0) {
        return response()->json(['success' => false, 'message' => 'Mã giftcode không có phần thưởng hợp lệ.'], 400);
    }

    DB::transaction(function () use ($user, $giftcode, $rewardAmount) {
        // Lock user and giftcode to prevent race conditions
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
        $lockedGiftcode = Giftcode::where('id', $giftcode->id)->lockForUpdate()->firstOrFail();

        // Double-check usability after locking
        if (! $lockedGiftcode->isUsable()) {
            if ($lockedGiftcode->expires_at && $lockedGiftcode->expires_at->isPast()) {
                throw new Exception('Mã giftcode đã hết hạn.');
            }

            throw new Exception('Mã giftcode đã hết lượt sử dụng.');
        }

        // Double-check if already redeemed
        $alreadyRedeemed = GiftcodeRedemption::where('giftcode_id', $lockedGiftcode->id)
            ->where('user_id', $lockedUser->id)
            ->exists();

        if ($alreadyRedeemed) {
            throw new Exception('Bạn đã sử dụng mã này rồi.');
        }

        // Award points to user
        $lockedUser->increment('points', $rewardAmount);

        // Update giftcode usage
        $lockedGiftcode->increment('used_count');

        // Create redemption record
        GiftcodeRedemption::create([
            'giftcode_id' => $lockedGiftcode->id,
            'user_id' => $lockedUser->id,
        ]);
    });

    return response()->json([
        'success' => true,
        'message' => 'Đã sử dụng mã giftcode thành công!',
        'reward' => [
            'type' => $giftcode->reward_type,
            'amount' => $rewardAmount,
        ],
        'new_points' => $user->fresh()->points,
    ]);
})->name('sdk.giftcode.redeem');
