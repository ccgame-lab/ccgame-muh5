<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use App\Services\Game\GmApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGameMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public Server $server,
        public string $playerId,
        public string $title,
        public string $content,
        public string $actionUuid,
        public string $itemPayload = ''
    ) {
        $this->onQueue('gm');
    }

    public function handle(): void
    {
        $gmService = app(GmApiService::class);

        $gmService->sendItemMail(
            $this->server,
            $this->playerId,
            $this->title,
            $this->content,
            $this->itemPayload
        );
    }
}
