<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Season;
use Illuminate\Support\Facades\Cache;

class SeasonService
{
    /**
     * Get the currently active season.
     * Cached for performance since it's checked frequently.
     */
    public function getCurrentSeason(): ?Season
    {
        return Cache::remember('current_active_season', 60, function () {
            return Season::active()
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->first();
        });
    }

    /**
     * Get the current active season, or the upcoming one if none active but one is scheduled.
     */
    public function getUpcomingOrCurrentSeason(): ?Season
    {
        $current = $this->getCurrentSeason();
        if ($current) {
            return $current;
        }

        return Season::active()
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->first();
    }

    /**
     * Clear the season cache when a season is updated.
     */
    public function clearSeasonCache(): void
    {
        Cache::forget('current_active_season');
    }
}
