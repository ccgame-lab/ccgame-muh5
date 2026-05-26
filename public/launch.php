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

function render_error_page(string $message, int $code = 500) {
    global $_CFG;
    $return_url = $_CFG['ccgame']['return_home_url'] ?? 'https://ccgame.org';
    http_response_code($code);
    echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lỗi Xác Thực</title>
    <style>
        body { font-family: sans-serif; background: #0d0d14; color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: #16161f; border: 1px solid #2a2a3d; padding: 2rem; border-radius: 8px; text-align: center; max-width: 400px; width: 90%; }
        h1 { color: #c9a94e; font-size: 1.25rem; margin-top: 0; }
        p { color: #a0a0b0; font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5; }
        a.btn { background: #059669; color: #fff; text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: bold; font-size: 0.9rem; display: inline-block; transition: background 0.2s; }
        a.btn:hover { background: #047857; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Lỗi Xác Thực</h1>
        <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        <a href="' . htmlspecialchars($return_url, ENT_QUOTES, 'UTF-8') . '" class="btn">Về CCGame</a>
    </div>
</body>
</html>';
    exit;
}

if (empty($token)) {
    render_error_page("Missing launch token", 400);
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
        render_error_page("Tài khoản GreenJade chưa có nhân vật MU H5", 403);
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
    render_error_page("Không thể xác thực phiên chơi. Vui lòng quay lại ccgame.org.", 500);
}
