<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PortalAuthService
{
    private readonly string $apiUrl;

    private readonly string $gameCode;

    private readonly string $apiSecret;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('portal.api_url'), '/');
        $this->gameCode = (string) config('portal.game_code');
        $this->apiSecret = (string) config('portal.api_secret');
    }

    /**
     * Attempt login via Portal API.
     *
     * @return array{uid: string, username: string}|null
     */
    public function login(string $username, string $password): ?array
    {
        try {
            $response = Http::asForm()->withHeaders([
                'X-GAME-CODE' => $this->gameCode,
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/game/auth/login", [
                'username' => $username,
                'password' => $password,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (empty($data['uid']) || empty($data['username'])) {
            return null;
        }

        return [
            'uid' => (string) $data['uid'],
            'username' => (string) $data['username'],
        ];
    }

    /**
     * Exchange a Portal GameToken (ULID) for user info.
     *
     * Portal issues these tokens after register/login and redirects
     * the user back to the game with ?token=ULID in the callback URL.
     *
     * @return array{uid: string, username: string}|null
     */
    public function consumeToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-GAME-CODE' => $this->gameCode,
                'X-GAME-SECRET' => $this->apiSecret,
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/game/auth/login", [
                'token' => $token,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (empty($data['uid']) || empty($data['username'])) {
            return null;
        }

        return [
            'uid' => (string) $data['uid'],
            'username' => (string) $data['username'],
        ];
    }

    /**
     * Issue session GameToken từ Portal sau khi login thành công.
     *
     * @return array{token: string, wallet_coin: int, expires_at: string}|null
     */
    public function issueToken(string $uid): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-GAME-CODE' => $this->gameCode,
                'X-GAME-SECRET' => $this->apiSecret,
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/game/auth/token", [
                'uid' => $uid,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (empty($data['token'])) {
            return null;
        }

        return [
            'token' => (string) $data['token'],
            'wallet_coin' => (int) ($data['wallet_coin'] ?? 0),
            'expires_at' => (string) ($data['expires_at'] ?? ''),
        ];
    }

    /**
     * Trừ Portal Coin từ ví người chơi (khi đổi xu).
     *
     * @param  string  $gameToken  ULID token từ Portal
     * @param  int  $amount  Số lượng Portal Coin cần trừ
     * @param  string  $reference  Mã giao dịch duy nhất, format: dolong-{ULID}
     * @return int Số dư mới sau khi trừ
     *
     * @throws \RuntimeException Khi Portal trả lỗi
     */
    public function spend(string $gameToken, int $amount, string $reference, string $description = ''): int
    {
        $response = $this->request('POST', '/game/wallet/spend', [
            'token' => $gameToken,
            'amount' => $amount,
            'reference' => $reference,
            'description' => $description,
        ]);

        return (int) ($response['new_balance'] ?? 0);
    }

    /**
     * Hoàn trả Portal Coin vào ví người chơi (rollback hoặc reward).
     *
     * @param  string  $gameToken  ULID token từ Portal
     * @param  int  $amount  Số lượng Portal Coin cần cộng
     * @param  string  $reference  Mã giao dịch duy nhất, format: dolong-{ULID}
     * @return int Số dư mới sau khi cộng
     *
     * @throws \RuntimeException Khi Portal trả lỗi
     */
    public function reward(string $gameToken, int $amount, string $reference, string $description = ''): int
    {
        $response = $this->request('POST', '/game/wallet/reward', [
            'token' => $gameToken,
            'amount' => $amount,
            'reference' => $reference,
            'description' => $description,
        ]);

        return (int) ($response['new_balance'] ?? 0);
    }

    /**
     * Gửi HTTP request tới Portal API với headers xác thực.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws \RuntimeException Khi Portal không phản hồi hoặc trả lỗi
     */
    private function request(string $method, string $path, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'X-GAME-CODE' => $this->gameCode,
                'X-GAME-SECRET' => $this->apiSecret,
                'Accept' => 'application/json',
            ])->send($method, $this->apiUrl.$path, ['json' => $payload]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Portal API không phản hồi: '.$e->getMessage());
        }

        if ($response->status() === 401) {
            throw new \RuntimeException('Token không hợp lệ hoặc đã hết hạn.');
        }

        if ($response->status() === 422) {
            throw new \RuntimeException('Số dư không đủ.');
        }

        if (! $response->successful()) {
            throw new \RuntimeException('Portal API lỗi: '.$response->status());
        }

        return $response->json() ?? [];
    }
}
