<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TopDonateRankChanged;
use App\Jobs\RefreshTopDonateVisuals;
use App\Models\TopSpendLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TopDonateService
{
    public const RANKING_KEY_ALL_TIME = 'top_spend_ranking';

    public const RANKING_KEY_14D = 'top_spend_ranking:14d';

    public function __construct(protected SeasonService $seasonService) {}

    public function getActiveRankingKey(): string
    {
        $race = app(PShopEventService::class)->getActiveRace();
        if ($race) {
            return "top_spend_ranking:event:{$race->id}";
        }

        $season = $this->seasonService->getCurrentSeason();

        if ($season) {
            return "top_spend_ranking:season:{$season->id}";
        }

        return self::RANKING_KEY_14D;
    }

    /**
     * Record a new spend amount
     *
     * @return void
     */
    public function recordSpend(int $userId, string $username, int $amountSpent): void
    {
        if ($amountSpent <= 0) {
            return;
        }

        $season = $this->seasonService->getCurrentSeason();
        $race = app(PShopEventService::class)->getActiveRace();

        $seasonKey = $season ? "top_spend_ranking:season:{$season->id}" : null;
        $raceKey = $race ? "top_spend_ranking:event:{$race->id}" : null;
        $focusKey = $raceKey ?: ($seasonKey ?: self::RANKING_KEY_ALL_TIME);

        TopSpendLog::create([
            'user_id' => $userId,
            'season_id' => $season ? $season->id : null,
            'event_id' => $race ? $race->id : null,
            'amount' => $amountSpent,
            'ip_address' => request()->ip(),
            'meta' => [
                'user_agent' => request()->userAgent(),
                'route' => request()->path(),
            ],
        ]);

        // Always record to all-time prestige ranking
        Redis::zincrby(self::RANKING_KEY_ALL_TIME, (float) $amountSpent, (string) $userId);

        // Always record to the 14-day rolling ranking (rebuilt nightly, but incremented live)
        Redis::zincrby(self::RANKING_KEY_14D, (float) $amountSpent, (string) $userId);

        if ($seasonKey) {
            Redis::zincrby($seasonKey, (float) $amountSpent, (string) $userId);
        }

        // Snapshot before insertion (Top 5 for overtake detection)
        $top5Before = Redis::zrevrange($focusKey, 0, 4, ['WITHSCORES' => true]) ?: [];

        if ($raceKey) {
            $newScore = (float) Redis::zincrby($raceKey, (float) $amountSpent, (string) $userId);
        } elseif ($seasonKey) {
            $newScore = (float) Redis::zscore($seasonKey, (string) $userId);
        } else {
            $newScore = (float) Redis::zscore(self::RANKING_KEY_ALL_TIME, (string) $userId);
        }

        // Cache user progress for overlay checks
        Redis::set("user:{$userId}:recharge_total", (string) $newScore);

        $newRank = Redis::zrevrank($focusKey, (string) $userId);

        // Snapshot after insertion
        $top5After = Redis::zrevrange($focusKey, 0, 4, ['WITHSCORES' => true]) ?: [];

        $differs = ($top5Before !== $top5After);

        // Overtake Detection
        $rankBefore = null;
        foreach (array_keys($top5Before) as $index => $id) {
            if ((string) $id === (string) $userId) {
                $rankBefore = $index;
                break;
            }
        }

        $rankAfter = null;
        foreach (array_keys($top5After) as $index => $id) {
            if ((string) $id === (string) $userId) {
                $rankAfter = $index;
                break;
            }
        }

        // Broadcaster Logic
        if ($rankAfter !== null && ($rankBefore === null || $rankAfter < $rankBefore)) {
            // Triggers if you enter Top 5, or overtake someone in Top 5
            if ($raceKey) {
                Cache::put(
                    'top_donate_recent_overtake',
                    json_encode([
                        'type' => 'race_overtake',
                        'user' => $username,
                        'rank' => $rankAfter + 1,
                        'timestamp' => time(),
                    ], JSON_THROW_ON_ERROR),
                    30
                );
            } else {
                // Season fallback: Only broadcast if taking #1
                if ($rankAfter === 0) {
                    Cache::put(
                        'top_donate_recent_overtake',
                        json_encode([
                            'type' => 'overtake',
                            'user' => $username,
                            'rank' => 1,
                            'timestamp' => time(),
                        ], JSON_THROW_ON_ERROR),
                        30
                    );
                }
            }
        }

        // --- MILESTONE EVENT TRACKING ---
        $milestone = app(PShopEventService::class)->getActiveMilestone();
        if ($milestone) {
            $msKey = "pshop_event:milestone:{$milestone->id}:user:{$userId}:spent";
            $newSpent = (int) Redis::incrby($msKey, $amountSpent);

            // Broadcast on crossing high thresholds (150k or 500k)
            $thresholds = [10000, 50000, 150000, 500000];
            $crossed = null;
            foreach ($thresholds as $t) {
                if (($newSpent - $amountSpent) < $t && $newSpent >= $t) {
                    $crossed = $t;
                }
            }

            if ($crossed && $crossed >= 150000) {
                Cache::put(
                    'top_donate_recent_overtake',
                    json_encode([
                        'type' => 'milestone',
                        'user' => $username,
                        'threshold' => $crossed,
                        'timestamp' => time(),
                    ], JSON_THROW_ON_ERROR),
                    15
                );
            }
        }

        if ($newRank !== null && $newRank <= 9) {
            event(new TopDonateRankChanged($userId, $newRank + 1, (int) $newScore));
            if ($differs) {
                RefreshTopDonateVisuals::dispatch();
            }
        }
    }

    /**
     * Deduct from the spending rank when an order is refunded or canceled
     *
     * @return void
     */
    public function recordRefund(int $userId, int $refundAmount): void
    {
        if ($refundAmount <= 0) {
            return;
        }

        // Deduct from ALL TIME
        $allTimeScore = (float) Redis::zincrby(self::RANKING_KEY_ALL_TIME, (float) -$refundAmount, (string) $userId);
        if ($allTimeScore < 0) {
            Redis::zadd(self::RANKING_KEY_ALL_TIME, 0, (string) $userId);
            $allTimeScore = 0.0;
        }

        $activeKey = $this->getActiveRankingKey();
        if ($activeKey !== self::RANKING_KEY_ALL_TIME) {
            $newScore = (float) Redis::zincrby($activeKey, (float) -$refundAmount, (string) $userId);
            if ($newScore < 0) {
                Redis::zadd($activeKey, 0, (string) $userId);
                $newScore = 0.0;
            }
        } else {
            $newScore = $allTimeScore;
        }

        // Cache user progress for overlay checks
        Redis::set("user:{$userId}:recharge_total", (string) $newScore);

        // Force a UI refresh because rank structures likely changed
        event(new TopDonateRankChanged($userId, 0, (int) $newScore));
        RefreshTopDonateVisuals::dispatch();
    }

    /**
     * Get the current Top N from ZSET
     *
     * @return array<int, string>
     */
    public function getTopIds(int $limit = 3): array
    {
        return (array) (Redis::zrevrange($this->getActiveRankingKey(), 0, $limit - 1) ?: []);
    }
}
