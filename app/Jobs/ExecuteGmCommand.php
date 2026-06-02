<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GmAction;
use App\Models\Server;
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

    public function handle(): void
    {
        $log = GmAction::find($this->logId);

        if (! $log || in_array($log->status, ['executed', 'executing'])) {
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

            $response = $this->handleGameCommand($log);

            $duration = (microtime(true) - $start) * 1000;

            $log->update([
                'status' => 'executed',
                'executed_at' => now(),
                'response' => is_array($response) ? $response : ['raw' => $response],
                'duration_ms' => $duration,
            ]);

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
            $lock?->release();
        }
    }

    /**
     * @return array<string, mixed>|bool
     */
    private function handleGameCommand(GmAction $log): array|bool
    {
        $server = Server::find($log->server_id);

        if (! $server) {
            throw new \Exception("Server ID {$log->server_id} not found.");
        }

        $gmService = app(\App\Services\Game\GmApiService::class);

        return $gmService->executeCommand(
            $log->action_type,
            $server,
            $log->payload ?? []
        );
    }
}
