<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SocialEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SocialEventService
{
    public const REDIS_KEY = 'social_feed:global';

    private const TEST_USERS = ['quocquoc', 's99', 'admin', 'gm', 'administrator'];

    /**
     * Push a new social event.
     *
     * @param  array{user_id: int|null, username: string, server_id: int|null, event_type: string, template: string, metadata: array<string, mixed>, priority: int}  $data
     */
    public function push(array $data): void
    {
        try {
            // Test user exclusion
            if (in_array(strtolower($data['username'] ?? ''), self::TEST_USERS, true)) {
                return;
            }

            // Flood Protection: 1 user, 1 event per 5 seconds
            if (! empty($data['user_id'])) {
                $cooldownKey = "social_feed:cooldown:{$data['user_id']}";
                if (Cache::has($cooldownKey)) {
                    return;
                }
                Cache::put($cooldownKey, true, 5);
            }

            // 3-tier filter for recharges
            $amount = (float) ($data['metadata']['amount'] ?? 0.0);
            if ($data['event_type'] === 'recharge' && $amount < 50) {
                return;
            }

            // Purchase filter: only pets + lifetime cards
            if ($data['event_type'] === 'purchase_item') {
                $isPet = ! empty($data['metadata']['is_pet']);
                $isLifetime = ! empty($data['metadata']['is_lifetime_card']);
                if (! $isPet && ! $isLifetime) {
                    return;
                }
            }

            // 1. Insert into DB
            $event = SocialEvent::create($data);

            // 2. Invalidate replay pool if significant event
            if ($data['event_type'] === 'recharge' && $amount >= 1000) {
                Cache::forget('sf:replay_pool');
            }

            // 3. Push to single Redis list
            try {
                $redisPayload = json_encode([
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'username' => $event->username,
                    'server_id' => $event->server_id,
                    'event_type' => $event->event_type,
                    'template' => $event->template,
                    'metadata' => $event->metadata,
                    'created_at' => $event->created_at->toIso8601String(),
                ], JSON_THROW_ON_ERROR);

                Redis::lpush(self::REDIS_KEY, $redisPayload);
                Redis::ltrim(self::REDIS_KEY, 0, 29);
            } catch (\Exception $e) {
                Log::error('Redis failed inside SocialEventService::push -> '.$e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('SocialEventService::push failed -> '.$e->getMessage());
        }
    }
}
