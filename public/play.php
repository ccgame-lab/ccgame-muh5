<?php
declare(strict_types=1);

/**
 * public/play.php — Patch 2
 *
 * Game shell: render full-screen iframe về game/index.html với params.
 *
 * Luồng lấy server params (theo thứ tự ưu tiên):
 *   1. Nếu DB cấu hình (storage/config.ini [db]) → đọc từ bảng servers
 *   2. Fallback → đọc từ config.ini [game] như Patch 1
 *
 * KHÔNG làm ở patch này:
 *   - Chưa gọi CCGame/GreenJade gateway để lấy launch token.
 *   - Chưa validate token phía browser (không inject qua JS/Alpine).
 *   - user/spverify vẫn lấy từ config.ini [game] — Patch 3+ sẽ bind gateway.
 */

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/db.php';

// ── Kiểm tra game client ────────────────────────────────────────
if (!is_dir(__DIR__ . '/game') || !is_file(__DIR__ . '/game/index.html')) {
    http_response_code(503);
    echo '<p style="font-family:sans-serif;padding:2rem;color:#c00">Game client chưa được cài. Copy legacy vào public/game/</p>';
    exit;
}

// ── Validate server id từ query string ─────────────────────────
// Mặc định server 1 (S1 MUH5 — visible = 1 trong DB)
$raw_server = $_GET['server'] ?? '1';
$server_id  = filter_var($raw_server, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($server_id === false) {
    $server_id = 1;
}

// ── Lấy params game theo thứ tự ưu tiên ────────────────────────
$game_cfg = $_CFG['game'] ?? [];

// ── user: session-scoped guest uid (Patch 2.1) ─────────────────
// Mỗi browser session nhận một guest_uid riêng (không share user=guest).
// Patch 3+ sẽ thay bằng portal_uid thật từ GreenJade gateway sau khi bind.
// KHÔNG validate token từ browser. KHÔNG inject qua JS/Alpine.
if (!empty($game_cfg['force_user'])) {
    // force_user trong config.ini [game] chỉ dùng cho dev/test — KHÔNG đặt trên production.
    $user = (string) $game_cfg['force_user'];
} else {
    // Tạo guest uid mới nếu session chưa có
    if (empty($_SESSION['guest_uid'])) {
        $_SESSION['guest_uid'] = 'guest_' . bin2hex(random_bytes(8));
    }
    $user = $_SESSION['guest_uid'];
}

// spverify: placeholder dev — Patch 3+ sẽ lấy launch token từ GreenJade gateway.
$spverify = $game_cfg['spverify'] ?? 'portal-auth';

// Host/port: cố gắng lấy từ DB, fallback về config.ini
$srvaddr = null;
$srvport = null;
$srv_name = null;

try {
    // db_config() ném RuntimeException nếu [db] chưa có trong config.ini
    $pdo  = db_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, name, host, port
         FROM servers
         WHERE id = :id AND visible = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $server_id]);
    $row = $stmt->fetch(); // FETCH_ASSOC

    if ($row) {
        $srvaddr  = $row['host'];
        $srvport  = (string) $row['port'];
        $srv_name = $row['name'];
        $server_id = (int) $row['id'];
    }

} catch (RuntimeException) {
    // DB chưa cấu hình — dùng fallback, không crash
} catch (PDOException) {
    // DB lỗi kết nối — dùng fallback, không crash
}

// Fallback về config.ini [game] nếu DB không có hoặc không tìm thấy server
if ($srvaddr === null) {
    $srvaddr  = $game_cfg['srvaddr'] ?? 'muh5-ws.ccgame.org/s1/';
    $srvport  = $game_cfg['srvport'] ?? '443';
    // srvid fallback về giá trị đã validate hoặc từ config
    $server_id = (int) ($game_cfg['srvid'] ?? $server_id);
}

// Build game URL — srvaddr KHÔNG dùng urlencode:
// config.js parse param bằng split('='), không decode %2F,
// nên slash trong host/path phải truyền nguyên xi.
// Các param khác vẫn encode bình thường.
$game_url = './game/index.html?'
    . 'user='      . rawurlencode($user)
    . '&srvid='    . rawurlencode((string) $server_id)
    . '&spverify=' . rawurlencode($spverify)
    . '&srvaddr='  . $srvaddr   // raw — chứa slash, game JS dùng trực tiếp làm WebSocket host
    . '&srvport='  . rawurlencode($srvport);

$page_title = $srv_name ? 'MU H5 — ' . $srv_name : 'MU H5';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        #game-frame {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        #back-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 100;
            padding: 4px 10px;
            background: rgba(0, 0, 0, .55);
            color: #c9a94e;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            opacity: .4;
            transition: opacity .2s;
        }
        #back-btn:hover { opacity: 1; }
    </style>
</head>
<body>
    <a id="back-btn" href="index.php">&#8592; Trang chủ</a>
    <iframe
        id="game-frame"
        src="<?= htmlspecialchars($game_url, ENT_QUOTES, 'UTF-8') ?>"
        allowfullscreen
        allow="autoplay; fullscreen"
        scrolling="no"
    ></iframe>
</body>
</html>
