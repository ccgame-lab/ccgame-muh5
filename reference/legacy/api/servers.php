<?php

declare(strict_types=1);

/**
 * public/api/servers.php — Patch 2
 *
 * JSON endpoint: trả danh sách game server đang visible.
 * Read-only. Không ghi DB.
 *
 * Method: GET only
 * Response: application/json
 *   200 → [{id, name, host, port, status, region}, ...]
 *   405 → {"error": "Method Not Allowed"}
 *   503 → {"error": "Service Unavailable"} — không lộ chi tiết lỗi
 */

require __DIR__.'/../../app/bootstrap.php';
require __DIR__.'/../../app/db.php';

// Chỉ chấp nhận GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    header('Allow: GET');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

header('Content-Type: application/json');
// Không cache — server list có thể thay đổi
header('Cache-Control: no-store');

try {
    $pdo = db_pdo();

    $stmt = $pdo->query(
        'SELECT id, name, host, port, status, region
         FROM servers
         WHERE visible = 1
         ORDER BY priority ASC, id ASC'
    );

    $servers = $stmt->fetchAll(); // PDO::FETCH_ASSOC từ db_pdo()

    // Cast numeric fields — PDO trả string theo mặc định
    foreach ($servers as &$row) {
        $row['id'] = (int) $row['id'];
        $row['port'] = (int) $row['port'];
        $row['status'] = (int) $row['status'];
    }
    unset($row);

    echo json_encode($servers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (RuntimeException $e) {
    // DB chưa cấu hình — trả 503, không lộ message config
    http_response_code(503);
    echo json_encode(['error' => 'DB not configured']);

} catch (PDOException $e) {
    // Lỗi kết nối / query — không lộ password, host, query detail
    http_response_code(503);
    echo json_encode(['error' => 'Service Unavailable']);
}
