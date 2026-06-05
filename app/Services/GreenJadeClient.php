<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GreenJadeClient
{
    private readonly string $baseUrl;

    private readonly string $serviceCode;

    private readonly string $serviceSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.greenjade.base_url'), '/');
        $this->serviceCode = (string) config('services.greenjade.service_code');
        $this->serviceSecret = (string) config('services.greenjade.service_secret');
    }

    /**
     * Read Tom balance from GreenJade wallet. Returns null on any failure.
     */
    public function getBalance(string $portalUid): ?int
    {
        try {
            $response = Http::withHeaders([
                'X-Service-Secret' => $this->serviceSecret,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/api/internal/services/{$this->serviceCode}/wallet-balance", [
                'user_id' => $portalUid,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->json() ?? [];

            return isset($body['data']['balance']) ? (int) $body['data']['balance'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Deduct Tom from user's GreenJade wallet.
     *
     * @return array{exchange_id: string, tom_spent: int, remaining_tom: int}
     *
     * @throws RuntimeException on INSUFFICIENT_BALANCE, network error, or unexpected response
     */
    public function spend(string $portalUid, int $tomAmount, string $idempotencyKey, ?string $reason = null, array $metadata = []): array
    {
        try {
            $response = Http::withHeaders([
                'X-Service-Secret' => $this->serviceSecret,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/api/internal/services/{$this->serviceCode}/wallet-spend", array_filter([
                'user_id' => $portalUid,
                'tom_amount' => $tomAmount,
                'idempotency_key' => $idempotencyKey,
                'reason' => $reason,
                'metadata' => $metadata ?: null,
            ]));
        } catch (ConnectionException $e) {
            throw new RuntimeException('GreenJade API không phản hồi: '.$e->getMessage());
        }

        $body = $response->json() ?? [];

        if ($response->status() === 422 && ($body['error_code'] ?? null) === 'INSUFFICIENT_BALANCE') {
            throw new InsufficientTomException('Số Tôm không đủ để thực hiện giao dịch.');
        }

        if (! $response->successful() || empty($body['ok'])) {
            throw new RuntimeException('GreenJade spend thất bại: '.($body['message'] ?? $response->status()));
        }

        return $body['data'];
    }
}
