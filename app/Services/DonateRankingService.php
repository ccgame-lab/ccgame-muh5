<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TomPurchaseLog;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * BXH donate: top người tiêu Tôm theo kỳ (tuần/tháng/mùa/all).
 * Nguồn: tom_purchase_logs (portal DB). KHÔNG đụng ví GreenJade.
 */
class DonateRankingService
{
    /** Trạng thái đã trừ Tôm (tính vào donate). */
    private const COUNTED_STATUSES = ['spent', 'dispatched', 'delivered'];

    /**
     * Top người tiêu Tôm theo kỳ.
     *
     * @param  string  $period  week|month|season|all
     * @return array<int, array{rank:int,name:string,tom:int,count:int}>
     */
    public function topDonors(string $period = 'week', int $limit = 10): array
    {
        $query = TomPurchaseLog::query()->whereIn('status', self::COUNTED_STATUSES);

        [$start, $end] = $this->periodRange($period);
        if ($start) {
            $query->where('created_at', '>=', $start);
        }
        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        $rows = $query->selectRaw('user_id, SUM(tom_spent) as total_tom, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->orderByDesc('total_tom')
            ->limit($limit)
            ->get();

        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('username', 'id');

        return $rows->values()->map(fn ($r, int $i) => [
            'rank' => $i + 1,
            'name' => (string) ($names[$r->user_id] ?? ('Người chơi #'.$r->user_id)),
            'tom' => (int) $r->total_tom,
            'count' => (int) $r->cnt,
        ])->all();
    }

    /** Đã từng chi Tôm chưa (gate nút tắt popup cả ngày = đặc quyền người đã nạp). */
    public function hasDonated(User $user): bool
    {
        return TomPurchaseLog::where('user_id', $user->id)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->exists();
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function periodRange(string $period): array
    {
        return match ($period) {
            'week' => [now()->startOfWeek(Carbon::MONDAY), null],
            'month' => [now()->startOfMonth(), null],
            'season' => $this->seasonRange(),
            default => [null, null], // all-time
        };
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function seasonRange(): array
    {
        try {
            $season = app(SeasonService::class)->getCurrentSeason();
            if ($season && $season->start_time) {
                return [
                    Carbon::parse($season->start_time),
                    $season->end_time ? Carbon::parse($season->end_time) : null,
                ];
            }
        } catch (\Throwable) {
            // Season optional: fallback all-time.
        }

        return [null, null];
    }
}
