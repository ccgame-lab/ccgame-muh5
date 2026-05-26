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
 * Gọi CCGame/GreenJade API để verify launch token.
 * Trả về thông tin user (đặc biệt là greenjade.ulid) nếu hợp lệ.
 * 
 * @param string $token
 * @return array
 * @throws RuntimeException
 */
function verify_ccgame_launch_token(string $token): array
{
    global $_CFG;
    $ccgame_cfg = $_CFG['ccgame'] ?? [];
    $verify_url = $ccgame_cfg['launch_verify_url'] ?? '';
    $launch_secret = $ccgame_cfg['launch_secret'] ?? '';
    
    if (empty($verify_url)) {
        throw new RuntimeException("CCGame launch verify endpoint not configured");
    }
    
    if (empty($launch_secret)) {
        throw new RuntimeException("CCGame launch secret not configured");
    }

    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    // Gửi token qua form data.
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $token]));
    
    // Thêm Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-CCGame-Launch-Secret: ' . $launch_secret,
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout 5s
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("Verify curl error: $curl_error");
    }

    if ($http_code !== 200) {
        throw new RuntimeException("Verify failed with HTTP code $http_code");
    }

    $data = json_decode((string) $response, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON response from CCGame verify");
    }

    if (empty($data['ok']) || $data['ok'] !== true) {
        throw new RuntimeException("CCGame verify returned not ok");
    }

    if (empty($data['greenjade']['ulid'])) {
        throw new RuntimeException("Missing greenjade ulid in verify response");
    }

    return $data['greenjade'];
}

try {
    // 1. Verify token với Gateway
    $greenjade_data = verify_ccgame_launch_token($token);
    
    // 2. Tìm legacy username từ portal_uid
    $pdo = db_pdo();
    $legacy_user = find_legacy_username_by_portal_uid($pdo, (string) $greenjade_data['ulid']);
    
    if (!$legacy_user && !empty($greenjade_data['id'])) {
        $legacy_user = find_legacy_username_by_portal_uid($pdo, (string) $greenjade_data['id']);
    }
    
    if (!$legacy_user) {
        http_response_code(403);
        echo "Tài khoản GreenJade chưa có nhân vật MU H5";
        exit;
    }

    // 3. Set session
    $_SESSION['legacy_username'] = $legacy_user['username'];
    $_SESSION['legacy_name']     = $legacy_user['name'] ?: $legacy_user['username'];
    $_SESSION['greenjade_ulid']  = $greenjade_data['ulid'];
    
    // Redirect về game
    header("Location: play.php?server=1");
    exit;

} catch (RuntimeException $e) {
    error_log("MUH5 Launch Error: " . $e->getMessage());
    http_response_code(500);
    echo "Không thể xác thực phiên chơi. Vui lòng quay lại ccgame.org.";
    exit;
}
