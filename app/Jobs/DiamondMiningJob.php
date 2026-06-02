<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DiamondMiningJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public Server $server,
        public string $username,
        public int $amount,
        public string $actionUuid
    ) {
        $this->onQueue('gm');
    }

    public function handle(): void
    {
        $gmService = app(\App\Services\Game\GmApiService::class);

        $gmService->chargeCurrency($this->server, $this->username, $this->amount);
    }
}
