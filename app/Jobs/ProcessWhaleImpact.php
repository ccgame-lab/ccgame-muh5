<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessWhaleImpact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $type,
        public int|string $userId,
        public int $serverId,
        public array $payload = []
    ) {}

    public function handle(GmApiService $gmService): void
    {
        // Global Impact Limiter (Anti-Spam GS)
        $limitKey = "impact_rate:s{$this->serverId}";
        $count = (int) Cache::get($limitKey, 0);
        if ($count >= 20) {
            return;
        }
        Cache::put($limitKey, $count + 1, 60);

        // Per-User Impact Limiter (Anti-Spam Whale)
        if (Cache::has("impact_user_cd:{$this->userId}")) {
            return;
        }
        Cache::put("impact_user_cd:{$this->userId}", true, 30); // 30s individual cooldown

        /** @var Server|null $server */
        $server = Server::find($this->serverId);
        if (! $server || empty($server->db_connection_name)) {
            return;
        }

        /** @var User|null $user */
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        try {
            if ($this->type === 'burst') {
                // Rule 1: World Buff (High Impact, O(1) Cache, Single Source of Truth)
                Cache::put('world_buff:global', [
                    'bonus' => 0.2, // 20% buff
                    'source' => $user->username,
                    'server' => $this->serverId,
                    'decay' => time() + 900, // 15 mins timestamp
                ], 900);
                Log::info('whale_burst_triggered', ['user' => $user->username, 'server' => $this->serverId]);

            } elseif ($this->type === 'streak') {
                // Rule 3: Whale Reward (10 min CD)
                $streak = $this->payload['streak'] ?? 3;
                $amount = $this->payload['amount'] ?? 0;
                // Require at least 500 topup value to prevent micro-transaction exploits
                if ($streak >= 3 && $amount >= 500 && ! Cache::has("reward_cd:{$user->id}")) {
                    $gmService->chargeCurrency($server, $user->username, 500, false); // Nạp nóng 500 Kim Cương (Yuanbao)
                    Cache::put("reward_cd:{$user->id}", true, 600);
                    Log::info('whale_reward_given', ['user' => $user->username, 'streak' => $streak]);
                }

            } elseif ($this->type === 'rivalry') {
                // Rule 2: Rivalry Mail (5 min CD per loser)
                $loserId = $this->payload['loser_id'] ?? null;
                if ($loserId && ! Cache::has("mail_cd:{$loserId}")) {
                    $loserUser = User::find($loserId);
                    if ($loserUser) {
                        $actor = $gmService->findActor($server, $loserUser->username);
                        $gmService->sendItemMail(
                            $server,
                            $actor['actorid'],
                            'Cảnh báo Bảng Xếp Hạng',
                            "Kẻ thách thức [{$user->username}] vừa dội bom và cướp mất vị trí của bạn trên BXH. Hãy cẩn thận!",
                            '' // No item payload, just provocation
                        );
                        Cache::put("mail_cd:{$loserId}", true, 300);
                        Cache::put("rivalry_rage_watch:{$loserId}", $user->username, 300);
                        Log::info('rivalry_mail_sent', ['winner' => $user->username, 'loser_id' => $loserId]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors to not block queue
        }
    }
}
