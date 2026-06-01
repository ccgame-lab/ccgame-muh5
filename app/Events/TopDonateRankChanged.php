<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TopDonateRankChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;

    public int $newRank;

    public int $newScore;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $newRank, int $newScore)
    {
        $this->userId = $userId;
        $this->newRank = $newRank;
        $this->newScore = $newScore;
    }
}
