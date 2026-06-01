<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GmAction;
use App\Models\Server;
use App\Models\User;
use App\Services\Game\GmApiService;
use App\Services\WCoinService;
use App\Services\WPointService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExecuteGmCommand implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $logId)
    {
        $this->onQueue('gm');
    }

    public function handle(GmApiService $gmService, WCoinService $wcoinService, WPointService $wpointService): void
    {
        $log = GmAction::find($this->logId);

        if (! $log || in_array($log->status, ['executed', 'executing'], true)) {
            return;
        }

        try {
            $lock = Cache::lock('gm_cmd:'.$log->action_uuid, 30)->block(3);
        } catch (LockTimeoutException) {
            return;
        }

        try {
            $log->update([
                'status' => 'executing',
                'executing_started_at' => now(),
            ]);

            $start = microtime(true);

            if ($log->action_type === 'charge_wcoin') {
                $response = $this->handleChargeWCoin($wcoinService, $log);
            } elseif ($log->action_type === 'charge_wpoint') {
                $response = $this->handleChargeWPoint($wpointService, $log);
            } else {
                $response = $this->handleGameCommand($gmService, $log);
            }

            $duration = (microtime(true) - $start) * 1000;

            $log->update([
                'status' => 'executed',
                'executed_at' => now(),
                'response' => is_array($response) ? $response : ['raw' => $response],
                'duration_ms' => $duration,
            ]);

            /** @var array<string, mixed> $logPayload */
            $logPayload = (array) ($log->payload ?? []);
            $amount = (int) ($logPayload['amount'] ?? 0);
            if ($amount > config('economy.gm_alert_threshold', 500000)) {
                Log::warning('GM large action', [
                    'action_uuid' => $log->action_uuid,
                    'type' => $log->action_type,
                    'amount' => $amount,
                    'admin_id' => $log->admin_id,
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'response' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        } finally {
            if ($lock instanceof Lock) {
                $lock->release();
            }
        }
    }

    /**
     * Handle charge_wcoin: credit WCoin to player's web wallet (portal-side, no game server).
     *
     * @return array<string, mixed>
     */
    private function handleChargeWCoin(WCoinService $wcoinService, GmAction $log): array
    {
        /** @var array<string, mixed> $payload */
        $payload = (array) ($log->payload ?? []);
        $targetUser = $payload['target_user_id'] ?? null;
        $amount = (int) ($payload['amount'] ?? 0);
        $reason = $payload['reason'] ?? '';

        if (! $targetUser || $amount <= 0) {
            throw new \Exception('Invalid charge_wcoin payload: target_user_id and amount required.');
        }

        $newBalance = $wcoinService->credit((int) $targetUser, $amount, 'gm_reward', $log->action_uuid, [
            'reason' => $reason,
            'admin_id' => $log->admin_id,
        ]);

        return ['credited' => $amount, 'new_balance' => $newBalance];
    }

    /**
     * Handle charge_wpoint: credit WPoint to player's web wallet (portal-side).
     *
     * @return array<string, mixed>
     */
    private function handleChargeWPoint(WPointService $wpointService, GmAction $log): array
    {
        /** @var array<string, mixed> $payload */
        $payload = (array) ($log->payload ?? []);
        $targetUserId = (int) ($payload['target_user_id'] ?? 0);
        $amount = (int) ($payload['amount'] ?? 0);
        $reason = $payload['reason'] ?? '';

        if (! $targetUserId || $amount <= 0) {
            throw new \Exception('Invalid charge_wpoint payload: target_user_id and amount required.');
        }

        $user = User::find($targetUserId);
        if (! $user) {
            throw new \Exception("User ID {$targetUserId} not found.");
        }

        $newBalance = $wpointService->credit($user, $amount, 'gm_reward', $log->action_uuid, [
            'reason' => $reason,
            'admin_id' => $log->admin_id,
        ]);

        return ['credited' => $amount, 'new_balance' => $newBalance];
    }

    /**
     * Handle game server commands via GmApiService (gmcmd, feecallback).
     *
     * @return array<string, mixed>|bool
     */
    private function handleGameCommand(GmApiService $gmService, GmAction $log): array|bool
    {
        $server = Server::find($log->server_id);

        if (! $server) {
            throw new \Exception("Server ID {$log->server_id} not found.");
        }

        return $gmService->executeCommand(
            $log->action_type,
            $server,
            $log->payload ?? []
        );
    }
}
