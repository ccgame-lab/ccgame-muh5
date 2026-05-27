<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$game_ready = is_dir(__DIR__ . '/game') && is_file(__DIR__ . '/game/index.html');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MU H5</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0d0d14;
            color: #e2e2f0;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .card {
            background: #16161f;
            border: 1px solid #2a2a3d;
            border-radius: 12px;
            padding: 2.5rem 3rem;
            text-align: center;
            max-width: 360px;
            width: 90%;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #c9a94e;
            margin-bottom: 0.25rem;
        }

        .subtitle {
            font-size: 0.8rem;
            color: #6b6b8a;
            margin-bottom: 2rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .status {
            font-size: 0.78rem;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.75rem;
            display: inline-block;
        }

        .status.ok  { background: #0e2a1a; color: #4cde80; border: 1px solid #1a5c33; }
        .status.err { background: #2a0e0e; color: #de4c4c; border: 1px solid #5c1a1a; }

        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
        }
        .btn:active { transform: scale(.98); }

        .btn-primary {
            background: linear-gradient(135deg, #c9a94e 0%, #e0c46a 100%);
            color: #0d0d14;
        }
        .btn-primary:hover { opacity: .88; }

        .btn-disabled {
            background: #2a2a3d;
            color: #4a4a6a;
            pointer-events: none;
        }

        .hint {
            margin-top: 1.25rem;
            font-size: 0.72rem;
            color: #4a4a6a;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">MU H5</div>
        <div class="subtitle">CCGame Wrapper</div>

        <?php if ($game_ready): ?>
            <span class="status ok">&#x2713; Game client sẵn sàng</span>
        <?php else: ?>
            <span class="status err">&#x26A0; Chưa có game client</span>
        <?php endif; ?>

        <?php if ($game_ready): ?>
            <a class="btn btn-primary" href="play.php">&#9654;&#xFE0F; Vào game</a>
        <?php else: ?>
            <span class="btn btn-disabled">Chờ GM upload game client</span>
            <p class="hint">
                Copy nội dung thư mục legacy vào:<br>
                <code>public/game/</code><br>
                (index.html, config.js, manifest.json, h5/)
            </p>
        <?php endif; ?>

        <p class="hint">
            <?= htmlspecialchars(php_uname('n')) ?> &middot;
            PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>
        </p>
    </div>
</body>
</html>
