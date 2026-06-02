<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiamondClaimLog;
use App\Models\DiamondWallet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MiningReport extends Command
{
    protected $signature = 'mining:report
                            {--date= : Date in YYYY-MM-DD format (default: today)}';

    protected $description = 'Show legacy mining economy report for a given date';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : now()->toDateString();

        $this->info("Mining Report — {$date}");
        $this->newLine();

        // ─── 1. Total mined today ───
        $totalMined = (int) DiamondClaimLog::whereDate('created_at', $date)
            ->sum('amount_claimed');

        $payerCount = DiamondClaimLog::whereDate('created_at', $date)
            ->distinct('user_id')
            ->count('user_id');

        $this->line("<fg=cyan>Total mined:</> <fg=yellow>" . number_format($totalMined) . " KC</>");
        $this->line("<fg=cyan>Unique claimers:</> {$payerCount}");
        $this->newLine();

        // ─── 2. Top 10 claimed today ───
        $this->info('Top 10 Mined Today');
        $this->table(
            ['#', 'User ID', 'Amount', 'Avg Rate/h', 'Claims'],
            DiamondClaimLog::whereDate('created_at', $date)
                ->selectRaw('user_id, SUM(amount_claimed) as total, AVG(COALESCE(rate_snapshot, 0)) as avg_rate, COUNT(*) as claims')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row, $i) => [
                    $i + 1,
                    $row->user_id,
                    number_format((int) $row->total),
                    number_format((int) $row->avg_rate),
                    $row->claims,
                ])
                ->toArray()
        );

        $this->newLine();

        // ─── 3. Top 10 current rate ───
        $this->info('Top 10 Current Rate (active boosters)');
        $this->table(
            ['#', 'User ID', 'Boost', 'Cap Multi', 'Boost Until', 'Mined Today'],
            DiamondWallet::where('boost_until', '>', now())
                ->orWhere('cap_until', '>', now())
                ->orderByDesc('boost_multiplier')
                ->limit(10)
                ->get()
                ->map(function (DiamondWallet $w) use ($date, &$i) {
                    static $idx = 0; $idx++;

                    $minedToday = (int) DiamondClaimLog::where('user_id', $w->user_id)
                        ->whereDate('created_at', $date)
                        ->sum('amount_claimed');

                    return [
                        $idx,
                        $w->user_id,
                        number_format((float) $w->boost_multiplier, 2) . 'x',
                        number_format((float) $w->cap_multiplier, 2) . 'x',
                        $w->boost_until?->format('H:i'),
                        number_format($minedToday),
                    ];
                })
                ->toArray()
        );

        return self::SUCCESS;
    }
}
