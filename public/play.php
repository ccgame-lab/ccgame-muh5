<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

// Kiểm tra game client tồn tại
if (!is_dir(__DIR__ . '/game') || !is_file(__DIR__ . '/game/index.html')) {
    http_response_code(503);
    echo '<p style="font-family:sans-serif;padding:2rem;color:#c00">Game client chưa được cài. Copy legacy vào public/game/</p>';
    exit;
}

// --- Patch 1: params mặc định từ config.ini, chưa có gateway ---
// Patch sau sẽ thay bằng: gọi CCGame/GreenJade gateway để lấy launch token,
// rồi inject token vào spverify.

$game_cfg = $_CFG['game'] ?? [];

$user     = $game_cfg['default_user'] ?? 'guest';
$srvid    = $game_cfg['srvid']        ?? '1';
$spverify = $game_cfg['spverify']     ?? 'portal-auth';
$srvaddr  = $game_cfg['srvaddr']      ?? 'muh5-ws.ccgame.org/s1/';
$srvport  = $game_cfg['srvport']      ?? '443';

// URL tương đối — iframe cùng origin, không cần CORS
$game_url = './game/index.html?' . http_build_query([
    'user'     => $user,
    'srvid'    => $srvid,
    'spverify' => $spverify,
    'srvaddr'  => $srvaddr,
    'srvport'  => $srvport,
]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>MU H5</title>
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
            background: rgba(0,0,0,.55);
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
