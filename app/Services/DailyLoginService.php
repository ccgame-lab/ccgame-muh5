<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckinLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Carbon;

class DailyLoginService
{
    public function __construct(
        protected PointService $pointService,
    ) {}

    /**
     * Claim daily login reward.
     *
     * @return array{success: bool, day_index: int, amount: int, balance: int, cycle: array<int, int>}
     */
    public function claim(User $user, int $multiplier = 1): array
    {
        $today = now()->toDateString();
        $rewards = $this->getRewardSchedule();

        $alreadyClaimed = CheckinLog::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

        if ($alreadyClaimed) {
            throw new Exception('Bạn đã nhận thưởng hôm nay rồi.');
        }

        $dayIndex = $this->resolveCurrentDay($user);
        $baseAmount = $rewards[$dayIndex] ?? $rewards[1];
        $amount = $baseAmount * $multiplier;

        CheckinLog::create([
            'user_id' => $user->id,
            'date' => $today,
            'day_index' => $dayIndex,
        ]);

        $newBalance = $this->pointService->credit($user, $amount, 'point_checkin', null, [
            'date' => $today,
            'day_index' => $dayIndex,
            'checkin_boost' => $multiplier > 1,
        ]);

        return [
            'success' => true,
            'day_index' => $dayIndex,
            'amount' => $amount,
            'balance' => $newBalance,
            'cycle' => $rewards,
        ];
    }

    /**
     * Get current login status without claiming.
     *
     * @return array{current_day: int, claimed_today: bool, today_reward: int, cycle: array<int, int>}
     */
    public function getStatus(User $user): array
    {
        $today = now()->toDateString();
        $rewards = $this->getRewardSchedule();

        $claimedToday = CheckinLog::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

        $dayIndex = $claimedToday
            ? $this->getTodayDayIndex($user)
            : $this->resolveCurrentDay($user);

        return [
            'current_day' => $dayIndex,
            'claimed_today' => $claimedToday,
            'today_reward' => $rewards[$dayIndex] ?? $rewards[1],
            'cycle' => $rewards,
        ];
    }

    /**
     * Resolve which day in the 7-day cycle the user is on.
     *
     * - Never claimed → day 1
     * - Last claim was yesterday → next day (wraps 7→1)
     * - Last claim was today → already claimed (should not reach here)
     * - Missed a day (gap > 1) → reset to day 1
     */
    public function resolveCurrentDay(User $user): int
    {
        $lastLog = CheckinLog::where('user_id', $user->id)
            ->orderByDesc('date')
            ->first();

        if (! $lastLog) {
            return 1;
        }

        /** @var Carbon $lastDate */
        $lastDate = $lastLog->date;
        $lastDateStr = $lastDate->toDateString();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($lastDateStr === $today) {
            return (int) $lastLog->day_index;
        }

        if ($lastDateStr === $yesterday) {
            $nextDay = ($lastLog->day_index % 7) + 1;

            return $nextDay;
        }

        return 1;
    }

    /**
     * Get day_index from today's claim log.
     */
    private function getTodayDayIndex(User $user): int
    {
        $log = CheckinLog::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return $log ? (int) $log->day_index : 1;
    }

    /**
     * @return array<int, int>
     */
    private function getRewardSchedule(): array
    {
        return (array) config('economy.wcoin_login_rewards', [
            1 => 30, 2 => 35, 3 => 35,
            4 => 40, 5 => 40, 6 => 45,
            7 => 55,
        ]);
    }
}
