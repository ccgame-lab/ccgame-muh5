<?php

declare(strict_types=1);

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PointShopController;
use App\Jobs\SendGameMailJob;
use App\Models\DiamondClaimLog;
use App\Models\Giftcode;
use App\Models\GiftcodeRedemption;
use App\Models\PointTransaction;
use App\Models\SdkDailyCheckin;
use App\Models\SdkFeature;
use App\Models\Server;
use App\Models\SocialEvent;
use App\Models\SpinLog;
use App\Models\User;
use App\Services\DonateRankingService;
use App\Services\Game\GmApiService;
use App\Services\GameRankingService;
use App\Services\GreenJadeClient;
use App\Services\LegacyMiningService;
use App\Services\SocialEventService;
use App\Services\SpinService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', fn () => redirect('/play'));
Route::get('/play', [PlayController::class, 'entry'])->name('play.index');

// Egret engine analytics no-op — swallow /report?appv=... calls (no 404 noise)
Route::get('/report', fn () => response()->noContent())->name('egret.report');

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
    $player = ['id' => 0, 'name' => 'Khách', 'level' => 0, 'vip' => 0, 'rs' => 0];
    $wallet = ['points' => 0, 'coin' => 0, 'diamond_blocks' => 0, 'tom' => null];

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

            $tomBalance = null;
            if ($user->portal_uid) {
                $tomBalance = Cache::remember("gj_bal_{$user->portal_uid}", 60, function () use ($user) {
                    return app(GreenJadeClient::class)->getBalance((string) $user->portal_uid);
                });
            }

            $rs = (int) ($actor['zhuansheng_lv'] ?? 0);

            $player = [
                'id' => $user->id,
                'name' => $user->name ?: $user->username,
                'level' => $level,
                'vip' => $vip,
                'rs' => $rs,
            ];
            $wallet = [
                'points' => (int) $user->points,
                'coin' => (int) ($user->webWallet?->balance ?? 0),
                'diamond_blocks' => (int) ($user->wallet?->diamond_blocks ?? 0),
                'tom' => $tomBalance,
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

    // Lối tiếp tế Tôm: chỉ là URL điều hướng sang GreenJade ID, SDK không xử lý ví/thanh toán.
    // ?source=<service_code> để GreenJade hiện nút "Về game" + gắn attribution vào intent.
    $gjIdBase = rtrim((string) (config('services.greenjade_id.base_url') ?: 'https://id.greenjade.net'), '/');
    $gjSource = (string) (config('services.greenjade.service_code') ?: 'muh5');
    $suppliesUrl = $gjIdBase.'/supplies?source='.urlencode($gjSource);

    return response()->json([
        'server' => ['id' => $serverId, 'name' => $serverName],
        'player' => $player,
        'wallet' => $wallet,
        'supplies_url' => $suppliesUrl,
        'support_tiers' => config('economy.support_tiers', []),
        'tabs' => [
            ['key' => 'overview',      'label' => 'Tổng quan'],
            ['key' => 'transactions',  'label' => 'Giao dịch'],
            ['key' => 'ranking',       'label' => 'BXH'],
            ['key' => 'notifications', 'label' => 'Thông báo'],
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
        'ranking_popup' => [
            'show' => true,
            'has_donated' => $user ? app(DonateRankingService::class)->hasDonated($user) : false,
        ],
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

// SDK donate ranking — top người tiêu Tôm theo kỳ (week/month/season/all)
Route::get('/api/sdk/donate-ranking', function () {
    $period = (string) request()->query('period', 'week');
    if (! in_array($period, ['week', 'month', 'season', 'all'], true)) {
        $period = 'week';
    }
    try {
        $top = app(DonateRankingService::class)->topDonors($period, 10);
    } catch (Throwable $e) {
        report($e);
        $top = [];
    }

    return response()->json(['period' => $period, 'top' => $top]);
})->name('sdk.donate_ranking');

// ─── Point Shop (Tom / GreenJade wallet) ─────────────────────────────────────

// Items — lazy list of Tom-priced items, fetched when "Đặc quyền" tab opens
Route::get('/api/pshop/items', [PointShopController::class, 'items'])->name('pshop.items');

// Buy — deduct Tom via GreenJade, deliver via GM mail
Route::post('/api/pshop/buy-tom', [PointShopController::class, 'buyWithTom'])->name('pshop.buy_tom');

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
    $base = (int) config('economy.point_checkin_amount', 3);
    $weekBonus = $streak >= (int) config('economy.point_streak_threshold', 7) ? (int) config('economy.point_streak_bonus', 10) : 0;
    $monthBonus = $streak >= (int) config('economy.point_streak_monthly_threshold', 30) ? (int) config('economy.point_streak_monthly_bonus', 30) : 0;
    $pointAmount = $base + $weekBonus + $monthBonus;

    $userId = $user->id;

    DB::transaction(function () use ($userId, $today, $streak, $pointAmount) {
        $lockedUser = User::where('id', $userId)->lockForUpdate()->firstOrFail();
        $lockedUser->increment('points', $pointAmount);

        PointTransaction::create([
            'user_id' => $userId,
            'type' => 'checkin',
            'amount' => $pointAmount,
            'balance_after' => $lockedUser->points,
            'meta' => ['streak' => $streak],
        ]);

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
        'reward' => ['points' => $pointAmount],
        'message' => "Điểm danh thành công! +{$pointAmount} POINT",
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

        PointTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => 'giftcode',
            'amount' => $rewardAmount,
            'balance_after' => $lockedUser->points,
            'reference' => $lockedGiftcode->code,
            'meta' => ['giftcode_id' => $lockedGiftcode->id],
        ]);

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

// Spin status — returns daily spin counters without mutating state
Route::get('/api/sdk/spin/status', function (Request $request) {
    $defaults = fn () => response()->json([
        'spins_today' => 0,
        'spins_remaining' => (int) config('economy.spin_daily_limit', 20),
        'next_cost' => (int) config('economy.spin_cost', 10),
        'daily_limit' => (int) config('economy.spin_daily_limit', 20),
        'has_free_spin' => false,
    ]);

    $username = (string) $request->query('u', '');
    if ($username === '') {
        return $defaults();
    }

    $user = User::where('username', $username)->first();
    if (! $user) {
        return $defaults();
    }

    $today = now()->toDateString();
    $baseCost = (int) config('economy.spin_cost', 10);
    $dailyLimit = (int) config('economy.spin_daily_limit', 20);
    $threshold = (int) config('economy.spin_diminish_after', 10);
    $multiplier = (float) config('economy.spin_diminish_multiplier', 1.3);

    $spinsToday = SpinLog::where('user_id', $user->id)->whereDate('created_at', $today)->count();
    $hasFree = Cache::has("spin_free_{$user->id}");

    $nextCost = $spinsToday >= $threshold
        ? (int) ceil($baseCost * pow($multiplier, $spinsToday - $threshold))
        : $baseCost;

    return response()->json([
        'spins_today' => $spinsToday,
        'spins_remaining' => max(0, $dailyLimit - $spinsToday),
        'next_cost' => $hasFree ? 0 : $nextCost,
        'daily_limit' => $dailyLimit,
        'has_free_spin' => $hasFree,
        'prizes' => config('economy.spin_prizes', []),
    ]);
})->name('sdk.spin.status');

// Spin — execute one spin, deduct POINT, deliver prize
Route::post('/api/sdk/spin', function (Request $request) {
    $username = (string) $request->input('u', '');
    if ($username === '') {
        return response()->json(['success' => false, 'message' => 'Chưa xác thực.'], 401);
    }

    $user = User::where('username', $username)->first();
    if (! $user) {
        return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại.'], 404);
    }

    $serverId = $request->input('server_id') ? (int) $request->input('server_id') : null;

    try {
        $result = app(SpinService::class)->spin($user, $serverId);

        // Compute next_cost for the upcoming spin
        $baseCost = (int) config('economy.spin_cost', 10);
        $threshold = (int) config('economy.spin_diminish_after', 10);
        $mult = (float) config('economy.spin_diminish_multiplier', 1.3);
        $spinsNow = $result['spins_today'];
        $nextCost = $result['extra_spin'] ? 0 : (
            $spinsNow >= $threshold
                ? (int) ceil($baseCost * pow($mult, $spinsNow - $threshold))
                : $baseCost
        );

        return response()->json(array_merge($result, ['next_cost' => $nextCost]));
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }
})->name('sdk.spin');

// ─── SDK Social Feed ──────────────────────────────────────────────────────────
Route::get('/api/sdk/feed', function () {
    $format = static function (array $item): array {
        $username = $item['username'] ?? 'Người chơi';
        $type = $item['event_type'] ?? '';
        $meta = is_array($item['metadata']) ? $item['metadata'] : [];
        $amount = number_format((float) ($meta['amount'] ?? 0));
        $message = match (true) {
            in_array($type, ['recharge', 'user_recharge'], true) => "{$username} vừa nạp {$amount} Tôm",
            $type === 'spin_win' => sprintf('%s trúng %s từ vòng quay', $username, $meta['prize'] ?? 'giải'),
            $type === 'purchase_item' => sprintf('%s vừa mua %s', $username, $meta['item_name'] ?? 'vật phẩm'),
            default => "{$username} vừa thực hiện giao dịch",
        };

        return ['username' => $username, 'event_type' => $type, 'message' => $message, 'created_at' => $item['created_at'] ?? ''];
    };

    try {
        $raw = Redis::lrange(SocialEventService::REDIS_KEY, 0, 9);
        if (! empty($raw)) {
            return response()->json([
                'events' => array_map(fn (string $j) => $format(json_decode($j, true) ?? []), $raw),
            ]);
        }
    } catch (Throwable) {
        // Redis unavailable (e.g. phpredis ext missing → Error not Exception) — fall through to DB
    }

    $events = SocialEvent::latest()->limit(10)->get()
        ->map(fn ($e) => $format([
            'username' => $e->username,
            'event_type' => $e->event_type,
            'metadata' => $e->metadata ?? [],
            'created_at' => $e->created_at?->toIso8601String() ?? '',
        ]))->values()->toArray();

    return response()->json(['events' => $events]);
})->name('sdk.feed');

// ─── SDK Daily Missions ───────────────────────────────────────────────────────

Route::get('/api/sdk/missions', function (Request $request) {
    $username = (string) $request->query('u', '');
    $user = $username !== '' ? User::where('username', $username)->first() : null;
    $today = now()->toDateString();
    $bonusPoints = (int) config('economy.missions_completion_bonus', 5);

    if (! $user) {
        return response()->json([
            'missions' => [
                ['key' => 'checkin', 'label' => 'Điểm danh hôm nay', 'done' => false],
                ['key' => 'spin5',   'label' => 'Quay 5 lần',         'done' => false, 'progress' => 0, 'target' => 5],
                ['key' => 'mining',  'label' => 'Đào KC 1 lần',       'done' => false],
            ],
            'all_done' => false,
            'bonus_claimed' => false,
            'bonus_points' => $bonusPoints,
        ]);
    }

    $checkinDone = SdkDailyCheckin::todayFor($user->id)->exists();
    $spinsToday = SpinLog::where('user_id', $user->id)->whereDate('created_at', $today)->count();
    $miningDone = DiamondClaimLog::where('user_id', $user->id)->whereDate('created_at', $today)->exists();
    $allDone = $checkinDone && $spinsToday >= 5 && $miningDone;
    $bonusClaimed = Cache::has("missions_bonus_{$user->id}_{$today}");

    return response()->json([
        'missions' => [
            ['key' => 'checkin', 'label' => 'Điểm danh hôm nay', 'done' => $checkinDone],
            ['key' => 'spin5',   'label' => 'Quay 5 lần',         'done' => $spinsToday >= 5, 'progress' => min($spinsToday, 5), 'target' => 5],
            ['key' => 'mining',  'label' => 'Đào KC 1 lần',       'done' => $miningDone],
        ],
        'all_done' => $allDone,
        'bonus_claimed' => $bonusClaimed,
        'bonus_points' => $bonusPoints,
    ]);
})->name('sdk.missions');

Route::post('/api/sdk/missions/claim-bonus', function (Request $request) {
    $username = (string) $request->input('u', '');
    $user = $username !== '' ? User::where('username', $username)->first() : null;
    if (! $user) {
        return response()->json(['success' => false, 'message' => 'Phiên chơi chưa xác thực, hãy tải lại trang.'], 401);
    }

    $today = now()->toDateString();
    $cacheKey = "missions_bonus_{$user->id}_{$today}";

    if (Cache::has($cacheKey)) {
        return response()->json(['success' => false, 'message' => 'Đã nhận thưởng hôm nay rồi.']);
    }

    $spinsToday = SpinLog::where('user_id', $user->id)->whereDate('created_at', $today)->count();
    $checkinDone = SdkDailyCheckin::todayFor($user->id)->exists();
    $miningDone = DiamondClaimLog::where('user_id', $user->id)->whereDate('created_at', $today)->exists();

    if (! ($checkinDone && $spinsToday >= 5 && $miningDone)) {
        return response()->json(['success' => false, 'message' => 'Chưa hoàn thành tất cả nhiệm vụ.']);
    }

    $bonusPoints = (int) config('economy.missions_completion_bonus', 5);

    DB::transaction(function () use ($user, $bonusPoints) {
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
        $lockedUser->increment('points', $bonusPoints);

        PointTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => 'missions_bonus',
            'amount' => $bonusPoints,
            'balance_after' => $lockedUser->points,
            'meta' => [],
        ]);
    });

    Cache::put($cacheKey, true, now()->endOfDay());

    return response()->json([
        'success' => true,
        'bonus_points' => $bonusPoints,
        'new_points' => $user->fresh()->points,
        'message' => "+{$bonusPoints} POINT, hoàn thành nhiệm vụ ngày!",
    ]);
})->name('sdk.missions.claim_bonus');

// ─── SDK Transactions (Giao dịch tab) ────────────────────────────────────────
Route::get('/api/sdk/transactions', function (Request $request) {
    $username = (string) $request->query('u', '');
    $user = $username !== '' ? User::where('username', $username)->first() : null;
    if (! $user) {
        return response()->json(['transactions' => []]);
    }

    $ptxns = DB::table('point_transactions')
        ->where('user_id', $user->id)
        ->latest()
        ->limit(15)
        ->get()
        ->map(fn ($r) => [
            'source' => 'point',
            'type' => $r->type,
            'label' => match ($r->type) {
                'checkin' => 'Điểm danh hàng ngày',
                'giftcode' => 'Đổi giftcode',
                'spin_win' => 'Vòng quay thắng',
                'missions' => 'Nhiệm vụ ngày',
                'buy_tom' => 'Mua bằng Tôm',
                default => $r->type,
            },
            'amount' => (int) $r->amount,
            'balance_after' => (int) $r->balance_after,
            'created_at' => $r->created_at,
        ]);

    $spinLogs = SpinLog::where('user_id', $user->id)
        ->latest()
        ->limit(10)
        ->get()
        ->map(fn ($r) => [
            'source' => 'spin',
            'type' => $r->prize_type,
            'label' => match ($r->prize_type) {
                'wcoin', 'wcoin_prize' => "+{$r->prize_value} POINT từ vòng quay",
                'yuanbao' => '+'.number_format($r->prize_value).'K KC từ vòng quay',
                'extra_turn' => 'Vòng quay: +1 lượt thêm',
                'lose_turn' => 'Vòng quay: Trật',
                default => 'Vòng quay',
            },
            'amount' => in_array($r->prize_type, ['wcoin', 'wcoin_prize']) ? (int) $r->prize_value : null,
            'cost' => (int) $r->wcoin_cost,
            'created_at' => $r->created_at,
        ]);

    $transactions = $ptxns->concat($spinLogs)
        ->sortByDesc('created_at')
        ->take(20)
        ->values()
        ->toArray();

    return response()->json(['transactions' => $transactions]);
})->name('sdk.transactions');
