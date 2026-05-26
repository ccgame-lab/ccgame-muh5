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

// ── Helper: Normalize WebSocket Host ─────────────────────────────
function normalize_game_srvaddr(string $host): string {
    $host = trim($host);
    if ($host !== '' && !str_ends_with($host, '/')) {
        return $host . '/';
    }
    return $host;
}

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
$game_cfg   = $_CFG['game'] ?? [];
$ccgame_cfg = $_CFG['ccgame'] ?? [];

// ── Lấy thông tin user từ session (Patch 4 - CCGame Launch) ──────
// Không dùng force_user dev hay guest_uid cho game iframe nữa.
$user = $_SESSION['legacy_username'] ?? null;
$expires_at = $_SESSION['muh5_expires_at'] ?? null;
$expired = false;

if ($user !== null && $expires_at !== null && time() > $expires_at) {
    unset($_SESSION['legacy_username'], $_SESSION['legacy_name'], $_SESSION['greenjade_ulid'], $_SESSION['muh5_launch_at'], $_SESSION['muh5_expires_at']);
    $user = null;
    $expired = true;
}

if ($user !== null) {
    $auth_mode    = 'ccgame';
    $display_name = $_SESSION['legacy_name'] ?? $user;
} else {
    $auth_mode    = 'none';
    $display_name = 'Khách';
}

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
        $srvaddr  = normalize_game_srvaddr((string) $row['host']);
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
    $srvaddr  = normalize_game_srvaddr((string) ($game_cfg['srvaddr'] ?? 'muh5-ws.ccgame.org/s1/'));
    $srvport  = $game_cfg['srvport'] ?? '443';
    // srvid fallback về giá trị đã validate hoặc từ config
    $server_id = (int) ($game_cfg['srvid'] ?? $server_id);
}

// ── Dựng iframe URL ─────────────────────────────────────────────
if ($user !== null) {
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
} else {
    $game_url = null;
}

$page_title = $srv_name ? 'MU H5 — ' . $srv_name : 'MU H5';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/sdk/ccgame-sdk.css">
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
    
    <?php if ($game_url !== null): ?>
        <iframe
            id="game-frame"
            src="<?= htmlspecialchars($game_url, ENT_QUOTES, 'UTF-8') ?>"
            allowfullscreen
            allow="autoplay; fullscreen"
            scrolling="no"
        ></iframe>
    <?php else: ?>
        <?php
            $return_url = $ccgame_cfg['return_home_url'] ?? 'https://ccgame.org';
        ?>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#0d0d14;font-family:sans-serif;">
            <div style="color:#c9a94e;font-size:18px;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">
                <?= isset($expired) && $expired ? 'Phiên chơi đã hết hạn' : 'Vui lòng vào game từ ccgame.org' ?>
            </div>
            <a href="<?= htmlspecialchars($return_url, ENT_QUOTES, 'UTF-8') ?>" 
               style="background:#16161f;color:#4a4a6a;text-decoration:none;padding:10px 24px;border:1px solid #2a2a3d;border-radius:6px;font-size:14px;transition:opacity 0.2s;">
                Quay lại Trang chủ
            </a>
        </div>
    <?php endif; ?>

    <!-- CCGame SDK Root -->
    <div id="ccgame-sdk-root"
         data-user="<?= htmlspecialchars((string) $user, ENT_QUOTES, 'UTF-8') ?>"
         data-server-id="<?= htmlspecialchars((string) $server_id, ENT_QUOTES, 'UTF-8') ?>"
         data-server-name="<?= htmlspecialchars((string) $srv_name, ENT_QUOTES, 'UTF-8') ?>"
         data-auth-mode="<?= htmlspecialchars($auth_mode, ENT_QUOTES, 'UTF-8') ?>"
         data-display-name="<?= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    
    <script src="assets/sdk/ccgame-sdk.js"></script>
</body>
</html>
