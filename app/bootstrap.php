<?php
declare(strict_types=1);

/**
 * Bootstrap tối thiểu — Patch 1
 * Không phụ thuộc composer, không framework.
 */

define('ROOT',    dirname(__DIR__));
define('APP_DIR', ROOT . '/app');
define('STORAGE', ROOT . '/storage');
define('PUB_DIR', ROOT . '/public');

// Đọc storage/config.ini nếu tồn tại.
// Các patch sau (setup wizard, gateway) sẽ ghi và đọc file này.
// Patch 1: chạy bình thường nếu file chưa có.
$_CFG = [];
$_cfg_file = STORAGE . '/config.ini';
if (is_file($_cfg_file)) {
    $_CFG = parse_ini_file($_cfg_file, true) ?: [];
}

// Session thuần PHP — cookie httpOnly, SameSite=Lax
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => isset($_SERVER['HTTPS']),
    ]);
}
