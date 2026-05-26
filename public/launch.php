<?php
declare(strict_types=1);

/**
 * public/launch.php
 * Endpoint nhận handoff token từ CCGame/GreenJade.
 */

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/legacy_user.php';

// Chỉ định dạng return JSON hoặc redirect. Ở đây cơ bản xử lý token.
$token = $_GET['token'] ?? $_GET['code'] ?? null;

if (empty($token)) {
    http_response_code(400);
    echo "Bad Request: Missing token";
    exit;
}

/**
 * Placeholder cho việc gọi GreenJade API để verify launch token.
 * Sẽ trả về portal_uid nếu hợp lệ.
 * 
 * @param string $token
 * @return array{portal_uid: string, username: string}
 * @throws RuntimeException
 */
function verify_ccgame_launch_token(string $token): array
{
    throw new RuntimeException("CCGame launch verify endpoint not configured");
}

try {
    // 1. Verify token với Gateway
    $ccgame_data = verify_ccgame_launch_token($token);
    
    // 2. Tìm legacy username từ portal_uid
    $pdo = db_pdo();
    $legacy_user = find_legacy_username_by_portal_uid($pdo, $ccgame_data['portal_uid']);
    
    if (!$legacy_user) {
        http_response_code(403);
        echo "Tài khoản chưa được đồng bộ với MU H5.";
        exit;
    }

    // 3. Set session
    $_SESSION['legacy_username'] = $legacy_user['username'];
    $_SESSION['legacy_name']     = $legacy_user['name'] ?: $legacy_user['username'];
    
    // Redirect về game
    header("Location: play.php?server=1");
    exit;

} catch (RuntimeException $e) {
    http_response_code(500);
    echo "Lỗi hệ thống: " . htmlspecialchars($e->getMessage());
    exit;
}
