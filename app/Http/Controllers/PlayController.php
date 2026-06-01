<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlayController extends Controller
{
    /**
     * Decode and safely verify a launch token using HMAC-SHA256.
     * Matches the legacy Nuxt/reference verifyLaunchToken logic.
     *
     * @param string $token
     * @return array<string, mixed>|null
     */
    private function verifyLaunchToken(string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $secret = env('MUH5_LAUNCH_SECRET') ?: env('CCGAME_LAUNCH_SECRET') ?: config('portal.game_secret');
        if (empty($secret) && app()->environment('local', 'testing', 'development')) {
            $secret = 'ccgame-dev-muh5-launch-secret-local-only';
        }

        if (empty($secret)) {
            logger()->warning('MUH5_LAUNCH_SECRET is not configured; rejecting launch token.');
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            logger()->warning('Invalid launch token format: missing parts');
            return null;
        }

        $payloadBase64 = $parts[0];
        $signature = $parts[1];
        if (empty($payloadBase64) || empty($signature)) {
            return null;
        }

        // Expected signature format: base64url(hash_hmac('sha256', payloadBase64, secret, true))
        $rawHmac = hash_hmac('sha256', $payloadBase64, $secret, true);
        $expectedSignature = rtrim(strtr(base64_encode($rawHmac), '+/', '-_'), '=');

        if (!hash_equals($signature, $expectedSignature)) {
            logger()->warning('Invalid launch token signature');
            return null;
        }

        try {
            $jsonStr = base64_decode(strtr($payloadBase64, '-_', '+/'), true);
            if ($jsonStr === false) {
                return null;
            }

            $payload = json_decode($jsonStr, true);
            if (!is_array($payload)) {
                return null;
            }

            if (($payload['gameId'] ?? '') !== 'muh5') {
                logger()->warning('Invalid launch token: incorrect gameId');
                return null;
            }

            $now = time();
            if (($payload['expiresAt'] ?? 0) < $now) {
                logger()->warning('Launch token has expired');
                return null;
            }

            if (($payload['server']['key'] ?? '') !== 's1') {
                logger()->warning('Invalid launch token: incorrect server key');
                return null;
            }

            return $payload;
        } catch (\Throwable $e) {
            logger()->warning('Failed to parse launch token payload: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Derive guest Suggested Character name hint.
     * Max 6 UTF-8 characters matching ccgame-web/reference.
     *
     * @param string $playerId
     * @return string
     */
    private function deriveGuestCharacterNick(string $playerId): string
    {
        $hex = preg_replace('/[^a-f0-9]/i', '', $playerId) ?? '';
        $last5 = substr($hex, -5);
        $padded = str_pad($last5 ?: '00000', 5, '0', STR_PAD_LEFT);
        return 'g' . substr($padded, 0, 5);
    }

    /**
     * Normalize WebSocket host (stripping protocols and paths).
     *
     * @param string $addr
     * @return string
     */
    private function normalizeSrvAddr(string $addr): string
    {
        if (empty($addr)) {
            return '';
        }

        $host = urldecode($addr);
        $host = preg_replace('/^(wss?|https?):\/\//i', '', $host) ?? '';

        $slashIdx = strpos($host, '/');
        if ($slashIdx !== false) {
            $host = substr($host, 0, $slashIdx);
        }

        $colonIdx = strpos($host, ':');
        if ($colonIdx !== false) {
            $host = substr($host, 0, $colonIdx);
        }

        return trim($host);
    }

    /**
     * Replicate player identity into game single-source-of-truth database tables.
     * 
     * NOTE: Database Provisioning constraints:
     * - This only syncs game-engine login credentials.
     * - It only writes account/passwd.
     * - It must NEVER touch wallet/payment/balance/economy fields.
     *
     * @param string $uid
     * @param string $spverify
     * @param string|null $dbName
     * @return void
     */
    private function provisionPlayerCredentials(string $uid, string $spverify, ?string $dbName = null): void
    {
        // Update global single source of truth
        try {
            DB::connection('mysql')
                ->table('globaldata_bt.global_user')
                ->updateOrInsert(
                    ['account' => $uid],
                    ['passwd' => $spverify]
                );
        } catch (\Throwable $e) {
            // Ignore missing table or db
        }

        // Update target server database
        if (!empty($dbName)) {
            try {
                DB::connection('mysql')
                    ->table($dbName . '.globaluser')
                    ->updateOrInsert(
                        ['account' => $uid],
                        ['passwd' => $spverify]
                    );
            } catch (\Throwable $e) {
                // Ignore missing connection or database
            }
        }
    }

    /**
     * Unified game entrance supporting standard Laravel sessions and signed launch tokens.
     *
     * @param Request $request
     * @return View
     */
    public function entry(Request $request): View
    {
        $launch = $request->query('launch');

        if (!empty($launch)) {
            $payload = $this->verifyLaunchToken((string) $launch);

            if (!$payload) {
                return view('play', [
                    'playAllowed' => false,
                    'errorReason' => 'invalid_launch',
                    'serverName' => 'S1',
                    'displayName' => 'Khách',
                    'gameUrl' => '',
                ]);
            }

            $authMode = $payload['authMode'] ?? 'guest';
            $player = $payload['player'] ?? [];
            $serverData = $payload['server'] ?? [];

            $username = trim($player['username'] ?? '');
            $spverify = trim($player['spverify'] ?? '');

            if (empty($username) || empty($spverify)) {
                return view('play', [
                    'playAllowed' => false,
                    'errorReason' => 'invalid_launch',
                    'serverName' => 'S1',
                    'displayName' => 'Khách',
                    'gameUrl' => '',
                ]);
            }

            // Lookup server from local database
            $server = Server::where('id', $serverData['id'])->first();

            $srvid = $server ? $server->id : ($serverData['id'] ?? 1);
            $srvaddr = $server ? $this->normalizeSrvAddr($server->host) : $this->normalizeSrvAddr($serverData['srvaddr'] ?? '');
            $srvport = $server ? (string) $server->port : (string) ($serverData['srvport'] ?? '443');
            $serverName = $server ? $server->name : ($serverData['name'] ?? 'S1');
            $dbName = $server ? $server->db_name : null;

            // Provision credentials
            $this->provisionPlayerCredentials($username, $spverify, $dbName);

            // Construct game parameters
            $params = [
                'user' => $username,
                'userId' => $player['id'] ?? $username,
                'spverify' => $spverify,
                'srvid' => (string) $srvid,
                'srvaddr' => $srvaddr,
                'srvport' => $srvport,
            ];

            if ($authMode === 'guest') {
                $nick = !empty($player['suggestedCharacterName'])
                    ? trim($player['suggestedCharacterName'])
                    : $this->deriveGuestCharacterNick($player['id'] ?? $username);
                $params['nickName'] = $nick;
            }

            $gameUrl = url('/muh5-client/index.html') . '?' . http_build_query($params);

            return view('play', [
                'playAllowed' => true,
                'errorReason' => null,
                'serverName' => $serverName,
                'displayName' => $player['displayName'] ?? $username,
                'gameUrl' => $gameUrl,
                'user' => $username,
                'serverId' => $srvid,
                'authMode' => $authMode,
                'expiresAt' => $payload['expiresAt'] ?? null,
            ]);
        }

        // Standard Laravel Web Authentication fallback
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $server = Server::where('visible', true)->orderBy('id')->first();
            if (!$server) {
                abort(503, 'Không có server nào đang mở.');
            }

            $this->provisionPlayerCredentials($user->username, $user->password, $server->db_name);

            $params = [
                'user' => $user->username,
                'userId' => (string) $user->id,
                'spverify' => $user->password,
                'srvid' => (string) $server->id,
                'srvaddr' => $this->normalizeSrvAddr($server->host),
                'srvport' => (string) $server->port,
            ];

            $gameUrl = url('/muh5-client/index.html') . '?' . http_build_query($params);

            return view('play', [
                'playAllowed' => true,
                'errorReason' => null,
                'serverName' => $server->name,
                'displayName' => $user->name,
                'gameUrl' => $gameUrl,
                'user' => $user->username,
                'serverId' => $server->id,
                'authMode' => 'greenjade',
                'expiresAt' => null,
            ]);
        }

        // Not authenticated and no launch token
        return view('play', [
            'playAllowed' => false,
            'errorReason' => 'no_session',
            'serverName' => 'S1',
            'displayName' => 'Khách',
            'gameUrl' => '',
        ]);
    }

    /**
     * Standard game entrance for specific servers (standard logged-in users).
     *
     * @param Server $server
     * @return View
     */
    public function game(Server $server): View
    {
        /** @var User $user */
        $user = Auth::user();

        $this->provisionPlayerCredentials($user->username, $user->password, $server->db_name);

        $params = [
            'user' => $user->username,
            'userId' => (string) $user->id,
            'spverify' => $user->password,
            'srvid' => (string) $server->id,
            'srvaddr' => $this->normalizeSrvAddr($server->host),
            'srvport' => (string) $server->port,
        ];

        $gameUrl = url('/muh5-client/index.html') . '?' . http_build_query($params);

        return view('play', [
            'playAllowed' => true,
            'errorReason' => null,
            'serverName' => $server->name,
            'displayName' => $user->name,
            'gameUrl' => $gameUrl,
            'user' => $user->username,
            'serverId' => $server->id,
            'authMode' => 'greenjade',
            'expiresAt' => null,
        ]);
    }


}
