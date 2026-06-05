<?php

declare(strict_types=1);

/**
 * app/legacy_user.php
 * Adapter đọc data từ legacy database.
 */

/**
 * Tìm legacy user dựa vào portal_uid từ GreenJade.
 *
 * @return array{id: int, portal_uid: string, username: string, name: string|null, tier: int}|null
 */
function find_legacy_username_by_portal_uid(PDO $pdo, string $portalUid): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, portal_uid, username, name, tier
         FROM users
         WHERE portal_uid = :portal_uid
         LIMIT 1'
    );
    $stmt->execute([':portal_uid' => $portalUid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (! $row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'portal_uid' => $row['portal_uid'],
        'username' => $row['username'],
        'name' => $row['name'],
        'tier' => (int) ($row['tier'] ?? 0),
    ];
}
