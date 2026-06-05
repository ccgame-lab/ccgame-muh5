<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fire-and-forget Telegram ops alert.
 *
 * Reads the bot token + chat id from config/services.php (env-driven). Swallows
 * every error: an alert must never break the business flow that triggered it.
 * If token or chat id are missing, it silently no-ops.
 */
final class TelegramAlert
{
    public static function send(string $message): void
    {
        try {
            $token = (string) config('services.telegram.bot_token');
            $chatId = (string) config('services.telegram.ops_chat_id');

            if ($token === '' || $chatId === '') {
                return;
            }

            // Plain text (no parse_mode): usernames/ids may contain Markdown
            // metacharacters that would otherwise make Telegram reject the
            // message — reliability matters more than formatting for ops alerts.
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
