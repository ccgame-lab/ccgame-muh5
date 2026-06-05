<?php

declare(strict_types=1);

/**
 * app/db.php — Patch 2
 *
 * Read-only adapter cho muh5_ccgame (Laravel portal DB legacy).
 * Không CREATE/ALTER/INSERT/UPDATE/DELETE bất kỳ thứ gì ở layer này.
 *
 * Cấu hình đọc từ storage/config.ini [db]:
 *   [db]
 *   host = 127.0.0.1
 *   port = 3306
 *   name = muh5_ccgame
 *   user = root_muh5
 *   pass = ****
 */

/**
 * Trả về mảng cấu hình DB từ $_CFG global.
 * Ném RuntimeException nếu thiếu key bắt buộc.
 *
 * @return array{host:string, port:string, name:string, user:string, pass:string}
 *
 * @throws RuntimeException
 */
function db_config(): array
{
    // $_CFG được parse từ bootstrap.php
    global $_CFG;

    $cfg = $_CFG['db'] ?? null;

    if (! is_array($cfg)) {
        throw new RuntimeException(
            'DB chưa được cấu hình. Tạo storage/config.ini với section [db].'
        );
    }

    $required = ['host', 'name', 'user', 'pass'];
    foreach ($required as $key) {
        if (empty($cfg[$key])) {
            throw new RuntimeException(
                "Thiếu key [db].$key trong storage/config.ini."
            );
        }
    }

    return [
        'host' => $cfg['host'],
        'port' => $cfg['port'] ?? '3306',
        'name' => $cfg['name'],
        'user' => $cfg['user'],
        'pass' => $cfg['pass'],
    ];
}

/**
 * Trả về PDO instance đã cấu hình.
 * Kết nối lazy: gọi mỗi lần cần, không singleton toàn cục.
 *
 * Charset: utf8mb4 (khớp collation utf8mb4_unicode_ci của DB)
 * Error mode: ERRMODE_EXCEPTION
 * Fetch mode: FETCH_ASSOC
 *
 * @throws RuntimeException nếu thiếu config
 * @throws PDOException nếu kết nối thất bại
 */
function db_pdo(): PDO
{
    $c = db_config(); // ném RuntimeException nếu thiếu config

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $c['host'],
        $c['port'],
        $c['name']
    );

    return new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Timeout kết nối 5 giây — tránh treo request khi DB down
        PDO::ATTR_TIMEOUT => 5,
    ]);
}
