<?php
declare(strict_types=1);

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 21600');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CCGame MUH5 đang nâng cấp</title>
  <style>
    :root {
      color-scheme: dark;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: #09090f;
      color: #f8fafc;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background:
        radial-gradient(circle at top, rgba(234,179,8,.18), transparent 35%),
        linear-gradient(135deg, #07070b, #111827 55%, #050505);
    }
    .card {
      width: min(92vw, 560px);
      border: 1px solid rgba(250,204,21,.22);
      background: rgba(15,23,42,.72);
      box-shadow: 0 24px 80px rgba(0,0,0,.45);
      border-radius: 22px;
      padding: 34px 28px;
      text-align: center;
      backdrop-filter: blur(12px);
    }
    .badge {
      display: inline-block;
      margin-bottom: 16px;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(250,204,21,.12);
      color: #facc15;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    h1 {
      margin: 0 0 12px;
      font-size: clamp(28px, 5vw, 42px);
      line-height: 1.05;
    }
    p {
      margin: 0 auto;
      max-width: 460px;
      color: #cbd5e1;
      line-height: 1.6;
      font-size: 15px;
    }
    .time {
      margin-top: 22px;
      color: #fbbf24;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <main class="card">
    <div class="badge">MUH5 S1 Upgrade</div>
    <h1>Đang nâng cấp hệ thống</h1>
    <p>CCGame MUH5 đang bảo trì để chuẩn bị phiên bản vận hành mới. Vui lòng quay lại sau ít phút.</p>
    <div class="time">Dự kiến hoàn tất trong khoảng 6 giờ.</div>
  </main>
</body>
</html>
