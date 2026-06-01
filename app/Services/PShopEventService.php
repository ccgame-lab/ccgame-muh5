<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PShopEvent;
use Illuminate\Support\Facades\Cache;

class PShopEventService
{
    /**
     * Get the active boost event for a given target (e.g., 'zen')
     */
    public function getActiveBoost(string $target): ?PShopEvent
    {
        return Cache::remember(
            "pshop_event:boost:{$target}",
            now()->addMinutes(1),
            function () use ($target) {
                return PShopEvent::active()
                    ->where('type', 'boost')
                    ->where('target', $target)
                    ->first();
            }
        );
    }

    /**
     * Get the active race event
     */
    public function getActiveRace(): ?PShopEvent
    {
        return Cache::remember(
            'pshop_event:race:active',
            now()->addMinutes(1),
            function () {
                return PShopEvent::active()
                    ->where('type', 'race')
                    ->first();
            }
        );
    }

    /**
     * Get the active milestone event
     */
    public function getActiveMilestone(): ?PShopEvent
    {
        return Cache::remember(
            'pshop_event:milestone:active',
            now()->addMinutes(1),
            function () {
                return PShopEvent::active()
                    ->where('type', 'milestone')
                    ->first();
            }
        );
    }

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
