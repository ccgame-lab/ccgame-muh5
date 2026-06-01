<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GmAction;
use App\Models\Server;
use App\Services\Game\GmApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiamondMiningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    /**
     * @param  string  $actionUuid  Idempotency key — prevents duplicate currency charges on retry
     */
    public function __construct(
        protected Server $server,
        protected string $accountName,
        protected int $amount,
        protected string $actionUuid,
        protected bool $isRawItemId = false,
    ) {
        $this->onQueue('economy');
    }

    /**
     * Atomic state machine: pending → executing → executed.
     *
     * Also reclaims stuck `executing` jobs older than 5 minutes
     * (worker crash recovery).
     */
    public function handle(GmApiService $apiService): void
    {
        $this->reclaimStuckJobs();

        $claimed = GmAction::where('action_uuid', $this->actionUuid)
            ->where('status', 'pending')
            ->update(['status' => 'executing', 'executing_started_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $apiService->chargeCurrency(
            $this->server,
            $this->accountName,
            $this->amount,
            $this->isRawItemId
        );

        GmAction::where('action_uuid', $this->actionUuid)
            ->update(['status' => 'executed', 'executed_at' => now()]);
    }

    /**
     * Reset stuck `executing` jobs back to `pending` after 5 minutes.
     */
    private function reclaimStuckJobs(): void
    {
        GmAction::where('status', 'executing')
            ->where('executing_started_at', '<', now()->subMinutes(5))
            ->update(['status' => 'pending', 'executing_started_at' => null]);
    }
}
