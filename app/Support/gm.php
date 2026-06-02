<?php

declare(strict_types=1);

use App\Models\GmAction;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

if (! function_exists('gm_log')) {
    /**
     * Create a GM audit log entry with rate limiting.
     *
     * @param  array<string, mixed>  $data
     */
    function gm_log(array $data): GmAction
    {
        $key = 'gm_limit:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'rate_limit' => 'Hệ thống giới hạn: Chỉ được gửi tối đa 10 lệnh GM mỗi phút.',
            ]);
        }

        RateLimiter::hit($key, 60);

        return GmAction::create([
            'action_uuid' => $data['action_uuid'] ?? (string) Str::uuid(),
            'admin_id' => auth()->id(),
            'server_id' => $data['server_id'] ?? null,
            'action_type' => $data['action'] ?? $data['action_type'] ?? 'unknown',
            'target_user' => $data['target'] ?? $data['target_user'] ?? 'unknown',
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);
    }
}
