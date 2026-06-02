<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Server;

class GmApiService
{
    /**
     * @return array{actorname: string, actorid: int, level: int, vip_level: int, gold: int, yuanbao: int, totalpower: int}
     */
    public function findActor(Server $server, string $username): array
    {
        // TODO: Implement via game server DB connection or HTTP API
        throw new \RuntimeException('GmApiService::findActor not yet implemented');
    }

    public function kickPlayer(Server $server, string $actorId): bool
    {
        // TODO: Implement via game server DB connection
        throw new \RuntimeException('GmApiService::kickPlayer not yet implemented');
    }

    public function banPlayer(Server $server, string $actorId, string $username): bool
    {
        // TODO: Implement via game server DB connection
        throw new \RuntimeException('GmApiService::banPlayer not yet implemented');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|bool
     */
    public function executeCommand(string $actionType, Server $server, array $payload): array|bool
    {
        // TODO: Implement via game server DB connection
        throw new \RuntimeException("GmApiService::executeCommand for '{$actionType}' not yet implemented");
    }

    /**
     * @param  string  $playerId
     * @param  string  $title
     * @param  string  $content
     * @param  string  $itemPayload
     */
    public function sendGameMail(Server $server, $playerId, $title, $content, $itemPayload = ''): bool
    {
        // TODO: Implement via gmcmd sendmail
        throw new \RuntimeException('GmApiService::sendGameMail not yet implemented');
    }
}
