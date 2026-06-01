<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DiamondMachine;
use App\Models\DiamondWallet;
use App\Models\Server;
use App\Models\WPointTransaction;
use App\Services\DailyLoginService;
use App\Services\DiamondMiningService;
use App\Services\WCoinService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get player dashboard stats: WPoint, WCoin, Diamond, Rank, rate_per_hour, server_online.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $freshUser = $user->fresh();

        if (! $freshUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $wpointBalance = (int) ($freshUser->wpoint ?? 0);
        $wcoinBalance = app(WCoinService::class)->getBalance($freshUser->id);

        $diamondWallet = DiamondWallet::where('user_id', $freshUser->id)->first();
        $lifetimeMined = $diamondWallet ? (int) $diamondWallet->lifetime_mined : 0;

        $rank = null;
        if ($diamondWallet) {
            $rank = DB::table('diamond_wallets')
                ->where('lifetime_mined', '>', $diamondWallet->lifetime_mined)
                ->count() + 1;
        }

        $ratePerHour = 0;
        $unclaimedDiamond = 0;
        $machines = DiamondMachine::where('user_id', $freshUser->id)->get();
        $miningService = app(DiamondMiningService::class);

        foreach ($machines as $machine) {
            $calc = $miningService->calculateUnclaimedDiamond($machine, $freshUser);
            $ratePerHour += (int) ($calc['rate_per_hour'] ?? 0);
            $unclaimedDiamond += (int) ($calc['produced'] ?? 0);
        }

        $serverOnline = (bool) Cache::remember('dashboard:server_online', 30, function (): bool {
            return Server::query()
                ->where('status', '!=', Server::STATUS_MAINTENANCE)
                ->exists();
        });

        $ascLevel = $diamondWallet ? (int) $diamondWallet->ascension_level : 0;
        $dailyCaps = config('economy.max_diamond_per_day', []);
        $maxDailyCap = is_array($dailyCaps)
            ? ($dailyCaps[$ascLevel] ?? end($dailyCaps) ?: 2_800_000)
            : (int) $dailyCaps;

        $dailyLog = $freshUser->dailyLogs()->whereDate('date', now()->toDateString())->first();
        $minedToday = $dailyLog ? (int) $dailyLog->daily_diamond_mined : 0;

        return response()->json([
            'wpoint_balance' => $wpointBalance,
            'wcoin_balance' => $wcoinBalance,
            'lifetime_diamond_mined' => $lifetimeMined,
            'unclaimed_diamond' => $unclaimedDiamond,
            'rank' => $rank,
            'rate_per_hour' => $ratePerHour,
            'server_online' => $serverOnline,
            'mined_today' => $minedToday,
            'max_daily_cap' => $maxDailyCap,
        ]);
    }

    /**
     * Get daily check-in status for dashboard widget.
     */
    public function checkinStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $today = now()->toDateString();

        $checkedInToday = WPointTransaction::where('user_id', $user->id)
            ->where('type', 'checkin')
            ->whereDate('created_at', $today)
            ->exists();

        $claimDates = $user->dailyLogs()
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        $dailyLog = $user->dailyLogs()->whereDate('date', $today)->first();

        $streak = 0;
        $checkDate = $dailyLog ? $today : now()->subDay()->toDateString();

        foreach ($claimDates as $date) {
            if ($date === $checkDate) {
                $streak++;
                $checkDate = Carbon::parse($checkDate)->subDay()->toDateString();
            } elseif ($date < $checkDate) {
                break;
            }
        }

        $dayInCycle = $checkedInToday ? (($streak - 1) % 7) + 1 : ($streak % 7) + 1;

        $rewards = config('economy.wpoint_checkin_amount', 100);
        $streakBonus = config('economy.wpoint_streak_bonus', 50);
        $streakThreshold = config('economy.wpoint_streak_threshold', 7);

        $loginStatus = app(DailyLoginService::class)->getStatus($user);

        return response()->json([
            'checked_in_today' => $checkedInToday,
            'streak' => $streak,
            'day_in_cycle' => $dayInCycle,
            'reward_amount' => $rewards,
            'streak_bonus' => $streakBonus,
            'streak_threshold' => $streakThreshold,
            'wcoin_cycle' => $loginStatus['cycle'],
            'wcoin_day' => $loginStatus['current_day'],
            'checkin_boost_active' => $user->hasActiveCheckinBoost(),
            'checkin_boost_expires_at' => $user->checkin_boost_expires_at?->toIso8601String(),
        ]);
    }
}
