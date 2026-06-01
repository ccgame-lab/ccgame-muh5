<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Server;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GmApiService
{
    /**
     * Unified pipeline entrypoint for ExecuteGmCommand job.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|bool
     */
    public function executeCommand(string $cmd, Server $server, array $payload): array|bool
    {
        return match ($cmd) {
            'kick' => $this->kickPlayer($server, (string) $payload['player_id']),
            'ban' => $this->banPlayer($server, (string) $payload['player_id'], (string) $payload['account_name']),
            'mute' => $this->mutePlayer($server, (string) $payload['player_id'], (int) ($payload['duration_hours'] ?? 100000)),
            'unmute' => $this->unmutePlayer($server, (string) $payload['player_id']),
            'send_mail' => $this->sendItemMail($server, (string) $payload['player_id'], (string) ($payload['title'] ?? ''), (string) ($payload['body'] ?? ''), (string) ($payload['item_payload'] ?? '')),
            'charge_currency', 'charge_yuanbao' => $this->chargeCurrency($server, (string) $payload['account_name'], (int) ($payload['amount'] ?? 0)),
            'send_global_mail' => $this->sendGlobalMail($server, (string) ($payload['title'] ?? ''), (string) ($payload['body'] ?? ''), (string) ($payload['item_payload'] ?? '')),
            default => throw new Exception("Unknown GM command: {$cmd}"),
        };
    }

    /**
     * Send items or mail to a player via game DB injection.
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
     * Send a global mail (to all players) on a server.
     *
     * Game engine doesn't support global mail natively. We loop through
     * all actors and send individual mails via gmcmd.
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
            usleep(50000);
        }

        return ['sent' => $sent, 'total' => $actors->count()];
    }

    /**
     * Charge custom currency (Yuanbao/WCoin) to a player's first character.
     */
    public function chargeCurrency(Server $server, string $accountName, int $amount, bool $isRawItemId = false): bool
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
            'itemid' => $isRawItemId ? $amount : $amount * 1000,
            'actor_id' => $actor->actorid,
        ]);

        return true;
    }

    /**
     * Kick a player from the game server immediately.
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
     * Mute a player for a given duration.
     */
    public function mutePlayer(Server $server, string $playerId, int $durationHours = 100000): bool
    {
        $connectionName = $this->resolveConnection($server);

        $silentUntil = time() + ($durationHours * 3600);

        DB::connection($connectionName)->table('gmcmd')->insert([
            'serverid' => $server->id,
            'cmdid' => 1,
            'cmd' => 'shutup',
            'param1' => $playerId,
            'param2' => (string) $silentUntil,
            'param3' => '',
            'param4' => '',
            'param5' => '',
        ]);

        return true;
    }

    /**
     * Unmute a player (set silent time to 0).
     */
    public function unmutePlayer(Server $server, string $playerId): bool
    {
        $connectionName = $this->resolveConnection($server);

        DB::connection($connectionName)->table('gmcmd')->insert([
            'serverid' => $server->id,
            'cmdid' => 1,
            'cmd' => 'shutup',
            'param1' => $playerId,
            'param2' => '0',
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
     * Lookup actor information from the game database.
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
            ->first(['actorid', 'actorname', 'accountname', 'level', 'job', 'sex', 'serverindex', 'gold', 'yuanbao', 'vip_level', 'totalpower']);

        if (! $actor) {
            throw new Exception("No character found for account {$username} on server {$server->name}.");
        }

        return (array) $actor;
    }

    /**
     * Deduct diamond (in-game Kim Cương, column 'yuanbao') using GM command.
     */
    public function deductDiamond(Server $server, string $username, int $amount): bool
    {
        $connectionName = $this->resolveConnection($server);

        $actor = DB::connection($connectionName)
            ->table('actors')
            ->where('accountname', $username)
            ->orderByDesc('totalpower')
            ->orderByDesc('level')
            ->orderBy('actorid')
            ->first(['actorid', 'yuanbao']);

        if (! $actor) {
            throw new Exception("Không tìm thấy nhân vật cho tài khoản {$username} trên server {$server->name}.");
        }

        if ($actor->yuanbao < $amount) {
            throw new Exception("Không đủ Kim Cương trong game. Cần $amount, hiện có {$actor->yuanbao}.");
        }

        // Secure deduct for offline players (if online, this is safely overwritten by engine cache).
        DB::connection($connectionName)->table('actors')
            ->where('actorid', $actor->actorid)
            ->update(['yuanbao' => DB::raw("yuanbao - $amount")]);

        DB::connection($connectionName)->table('gmcmd')->insert([
            'serverid' => $server->id,
            'cmdid' => 0,
            'cmd' => 'deductdiamond',
            'param1' => (string) $actor->actorid,
            'param2' => (string) $amount,
            'param3' => '',
            'param4' => '',
            'param5' => '',
        ]);

        return true;
    }

    /**
     * Set title_slots for a player (1–5).
     */
    public function setTitleSlots(Server $server, int $actorId, int $count): bool
    {
        $count = max(1, min($count, 5));
        $connectionName = $this->resolveConnection($server);

        $updated = DB::connection($connectionName)
            ->table('actors')
            ->where('actorid', $actorId)
            ->update(['title_slots' => $count]);

        if (! $updated) {
            throw new Exception("Actor {$actorId} not found on server {$server->name}.");
        }

        return true;
    }

    /**
     * Get title_slots for a player.
     */
    public function getTitleSlots(Server $server, int $actorId): int
    {
        $connectionName = $this->resolveConnection($server);

        $actor = DB::connection($connectionName)
            ->table('actors')
            ->where('actorid', $actorId)
            ->first(['title_slots']);

        if (! $actor) {
            throw new Exception("Actor {$actorId} not found.");
        }

        return (int) ($actor->title_slots ?? 1);
    }

    /**
     * Fetch Top Player Visual details via HTTP API (Game Server).
     *
     * @param array<int, int> $userIds
     * @return array<int, mixed>
     */
    public function getTopPlayerVisuals(Server $server, array $userIds): array
    {
        $apiUrl = env('GAME_API_URL', 'http://127.0.0.1:8080').'/gm/top_player_visuals';

        $response = Http::timeout(3)->post($apiUrl, [
            'user_ids' => $userIds,
            'server' => $server->id,
        ]);

        if ($response->failed()) {
            throw new Exception('Lỗi gọi API Game Server lấy Visuals: '.$response->body());
        }

        $result = $response->json();

        if (($result['code'] ?? -1) !== 0) {
            throw new Exception('Game Server trả về lỗi Visuals: '.($result['message'] ?? 'Unknown Error'));
        }

        return $result['data'] ?? [];
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
