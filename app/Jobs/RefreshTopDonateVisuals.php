<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use App\Services\TopDonateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RefreshTopDonateVisuals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(TopDonateService $topDonateService, GmApiService $gmApiService): void
    {
        try {
            $topIds = $topDonateService->getTopIds(3);

            if (empty($topIds)) {
                return;
            }

            $primaryServer = Server::orderBy('id')->first();

            if (! $primaryServer) {
                return;
            }

            $users = User::whereIn('id', $topIds)->get()->keyBy('id');
            $accountNames = $users->pluck('username')->toArray();

            // Direct DB fallback since API is offline/not ready
            $connectionName = $primaryServer->db_connection_name;

            if (empty($connectionName)) {
                return;
            }

            // Get highest actor per account
            $actors = DB::connection($connectionName)
                ->table('actors')
                ->whereIn('accountname', $accountNames)
                ->orderBy('level', 'desc')
                ->get()
                ->keyBy('accountname');

            $visualCache = [];
            foreach ($topIds as $index => $userId) {
                $user = $users->get($userId);

                $actor = null;
                if ($user) {
                    $actor = $actors->get($user->username);
                }

                $job = $actor ? (int) $actor->job : 1;
                $name = $actor ? (string) $actor->actorname : ($user ? $user->username : 'Unknown');

                // Base class grouping: 1=BK, 2=SM, 3=Elf, 4=MG, 5=DL
                $baseJob = ($job - 1) % 16 + 1;
                $bodyModel = 100 + $baseJob;

                if (! in_array($bodyModel, [101, 102, 103, 104, 105, 106], true)) {
                    $bodyModel = 102; // Default DK
                }

                // Default weapons/wings per class to look cool (fallback models)
                $weaponMap = [
                    101 => ['l' => 3102, 'r' => 0], // SM
                    102 => ['l' => 3202, 'r' => 3202], // BK
                    103 => ['l' => 3304, 'r' => 0], // Elf
                    104 => ['l' => 3402, 'r' => 3402], // MG
                    105 => ['l' => 3502, 'r' => 0], // DL
                    106 => ['l' => 3102, 'r' => 0], // SUM
                ];

                // Note: using 'total_spend' key because we pivot to WPoint Spend natively,
                // but the old renderer read 'total_recharge'. The frontend expects whatever I push down but I should update it.
                $totalSpend = (int) Redis::zscore($topDonateService->getActiveRankingKey(), (string) $userId);

                $visualCache[] = [
                    'rank' => $index + 1,
                    'user_id' => $userId,
                    'name' => $name,
                    'class' => $baseJob,
                    'total_recharge' => $totalSpend, // Keep mapping as total_recharge or frontend will fail
                    'model' => [
                        'body' => 'body'.$bodyModel.'_0_0s',
                        'armor' => '', // Using body string instead
                        'weapon_l' => 'weapon'.$weaponMap[$bodyModel]['l'].'_0_0s',
                        'weapon_r' => $weaponMap[$bodyModel]['r'] ? 'weapon'.$weaponMap[$bodyModel]['r'].'_0_0s' : 0,
                        'wing' => 'wing'.(4100 + $baseJob).'_0_0s',
                        'aura' => 'eff5001_0_0s',
                    ],
                ];
            }

            // Version Hash for smart polling
            $versionHash = md5(json_encode($visualCache));

            // Cache for Frontend (TTL 300s)
            Cache::put('top_donate_visuals', $visualCache, 300);
            Cache::put('top_donate_visuals_version', $versionHash, 300);

            Log::info('TopDonateVisuals refreshed gracefully for '.count($visualCache).' players. Version: '.$versionHash);

        } catch (\Exception $e) {
            Log::error('Failed to RefreshTopDonateVisuals DB Fallback: '.$e->getMessage());
        }
    }
}
