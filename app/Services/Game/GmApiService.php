<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Server;
use Exception;
use Illuminate\Support\Facades\DB;

class GmApiService
{
    /**
     * Unified pipeline entrypoint for ExecuteGmCommand job.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|bool
     */
    public function executeCommand(string $cmd, Server $server, array $payload): array|bool
    {
        return match ($cmd) {
            'kick' => $this->kickPlayer($server, $payload['player_id']),
            'ban' => $this->banPlayer($server, $payload['player_id'], $payload['account_name']),
            'send_mail' => $this->sendItemMail($server, $payload['player_id'], $payload['title'] ?? '', $payload['body'] ?? '', $payload['item_payload'] ?? ''),
            'send_global_mail' => $this->sendGlobalMail($server, $payload['title'] ?? '', $payload['body'] ?? '', $payload['item_payload'] ?? ''),
            'charge_yuanbao' => $this->chargeCurrency($server, (string) $payload['account_name'], (int) $payload['amount']),
            default => throw new Exception("Unknown GM command: {$cmd}"),
        };
    }

    /**
     * Send items or mail to a player via game DB gmcmd table.
     *
     * @param  string  $itemPayload  Format: '1,[ITEM_ID],[AMOUNT]'. Leave empty for just mail.
     */
    public function sendItemMail(Server $server, string $playerId, string $title, string $body, string $itemPayload = ''): bool
    {
        $connectionName = $this->resolveConnection($server);

        DB::connection($connectionName)->table('gmcmd')->insert([
            'serverid' => $server->id,
            'cmdid' => 1,
            'cmd' => 'sendMail',
            'param1' => $title,
            'param2' => $body,
            'param3' => $playerId,
            'param4' => $itemPayload,
            'param5' => '',
        ]);

        return true;
    }

    /**
     * Send a global mail to all players on a server by looping actors.
     *
     * @return array{sent: int, total: int}
     */
    public function sendGlobalMail(Server $server, string $title, string $body, string $itemPayload = ''): array
    {
        $connectionName = $this->resolveConnection($server);

        $actors = DB::connection($connectionName)
            ->table('actors')
            ->where('serverindex', $server->id)
            ->pluck('actorid');

        $sent = 0;
        foreach ($actors as $actorId) {
            DB::connection($connectionName)->table('gmcmd')->insert([
                'serverid' => $server->id,
                'cmdid' => 1,
                'cmd' => 'sendMail',
                'param1' => $title,
                'param2' => $body,
                'param3' => (string) $actorId,
                'param4' => $itemPayload,
                'param5' => '',
            ]);
            $sent++;
            usleep(50000); // 50ms delay to avoid choking game server
        }

        return ['sent' => $sent, 'total' => $actors->count()];
    }

    /**
     * Charge currency (Yuanbao) to a player's first character via feecallback.
     */
    public function chargeCurrency(Server $server, string $accountName, int $amount): bool
    {
        $connectionName = $this->resolveConnection($server);

        $actor = DB::connection($connectionName)
            ->table('actors')
            ->where('accountname', $accountName)
            ->orderByDesc('totalpower')
            ->orderByDesc('level')
            ->orderBy('actorid')
            ->first(['actorid', 'actorname', 'serverindex']);

        if (! $actor) {
            throw new Exception("No character found for account {$accountName} on server {$server->name}.");
        }

        DB::connection($connectionName)->table('feecallback')->insert([
            'serverid' => $actor->serverindex,
            'openid' => $accountName,
            'itemid' => $amount * 1000,
            'actor_id' => $actor->actorid,
        ]);

        return true;
    }

    /**
     * Kick a player from game server via gmcmd.
     */
    public function kickPlayer(Server $server, string $playerId): bool
    {
        $connectionName = $this->resolveConnection($server);

        DB::connection($connectionName)->table('gmcmd')->insert([
            'serverid' => $server->id,
            'cmdid' => 0,
            'cmd' => 'kick',
            'param1' => $playerId,
            'param2' => '',
            'param3' => '',
            'param4' => '',
            'param5' => '',
        ]);

        return true;
    }

    /**
     * Ban a player: kick + rename accountname to prevent re-login.
     */
    public function banPlayer(Server $server, string $playerId, string $accountName): bool
    {
        $connectionName = $this->resolveConnection($server);

        $this->kickPlayer($server, $playerId);

        $sealed = time().'_feng_'.$accountName;

        DB::connection($connectionName)
            ->table('actors')
            ->where('actorid', $playerId)
            ->where('serverindex', $server->id)
            ->update(['accountname' => $sealed]);

        return true;
    }

    /**
     * Lookup actor info from game database.
     *
     * @return array<string, mixed>
     */
    public function findActor(Server $server, string $username): array
    {
        $connectionName = $this->resolveConnection($server);

        $actor = DB::connection($connectionName)
            ->table('actors')
            ->where('accountname', $username)
            ->orderByDesc('totalpower')
            ->orderByDesc('level')
            ->orderBy('actorid')
            ->first([
                'actorid', 'actorname', 'accountname', 'level',
                'job', 'sex', 'serverindex', 'gold', 'yuanbao',
                'vip_level', 'totalpower',
            ]);

        if (! $actor) {
            throw new Exception("No character found for account {$username} on server {$server->name}.");
        }

        return (array) $actor;
    }

    /**
     * Resolve and validate the database connection for a server.
     */
    public function resolveConnection(Server $server): string
    {
        $connectionName = $server->db_connection_name;

        if (empty($connectionName)) {
            throw new Exception("Server {$server->name} does not have a database connection configured.");
        }

        return $connectionName;
    }
}
