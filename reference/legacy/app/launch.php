<?php

declare(strict_types=1);

/**
 * app/launch.php
 * Resolves launch identity for the game wrapper.
 */

/**
 * Lấy thông tin user đăng nhập.
 *
 * @return array{auth_mode: string, user: string|null, display_name: string, spverify: string, guest_uid: string|null}
 */
function launch_identity(array $game_cfg): array
{
    if (! empty($game_cfg['force_user'])) {
        // Mode: DEV
        $auth_mode = 'dev';
        $user = (string) $game_cfg['force_user'];
        $display_name = $user;
    } else {
        // Mode: GUEST
        if (empty($_SESSION['guest_uid'])) {
            $_SESSION['guest_uid'] = 'guest_'.bin2hex(random_bytes(8));
        }
        $auth_mode = 'guest';
        // KHÔNG truyền guest_uid vào game. guest_uid chỉ dùng cho SDK session.
        $user = null;
        $display_name = 'Khách';

        // TODO Patch 4 (Production launch):
        // Cần resolve GreenJade portal_uid -> users.username
        // Bằng câu query: SELECT username FROM users WHERE portal_uid = ?
        // Chỉ khi username legacy tồn tại mới được truyền vào $user.
    }

    $spverify = $game_cfg['spverify'] ?? 'portal-auth';

    return [
        'auth_mode' => $auth_mode,
        'user' => $user,
        'display_name' => $display_name,
        'spverify' => $spverify,
        'guest_uid' => $_SESSION['guest_uid'] ?? null,
    ];
}
