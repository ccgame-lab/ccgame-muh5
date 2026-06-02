<?php
declare(strict_types=1);

/**
 * public/api/sdk/bootstrap.php
 *
 * API Backend cung cấp dữ liệu read-only từ các bảng dữ liệu legacy của MUH5.
 * Được bảo vệ nghiêm ngặt bằng session và whitelist bảo mật.
 */

// Import các tệp bootstrap và db của dự án
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/db.php';

// Thiết lập định dạng header JSON
header('Content-Type: application/json; charset=utf-8');

// 1. Kiểm tra session legacy_username
$user = $_SESSION['legacy_username'] ?? null;
if ($user === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 2. Khởi tạo kết nối PDO an toàn
try {
    $pdo = db_pdo();
} catch (Exception $e) {
    // Không leak SQL error hoặc thông tin nhạy cảm ra ngoài
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 3. Khai báo helper kiểm tra sự tồn tại của bảng và cột an toàn (chỉ lấy theo whitelist)
function table_exists(PDO $pdo, string $table): bool
{
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function get_safe_columns(PDO $pdo, string $table, array $allowed_cols): array
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!$cols) {
            return [];
        }
        return array_values(array_intersect($cols, $allowed_cols));
    } catch (PDOException $e) {
        return [];
    }
}

// Whitelist các bảng và cột được phép truy vấn (Không nhận từ request)
$whitelist = [
    'users' => ['id', 'username', 'name', 'vip', 'tier', 'wcoin', 'wpoint', 'created_at'],
    'web_wallets' => ['user_id', 'wcoin', 'wpoint', 'balance'],
    'changelogs' => ['id', 'title', 'version_date', 'player_notes', 'server_id', 'is_published', 'created_at'],
    'giftcodes' => ['id', 'code', 'description', 'used_count', 'limit_usage', 'reward_type'],
    'diamond_wallets' => ['user_id', 'balance'],
    'diamond_machines' => ['user_id', 'machine_index', 'level', 'speed_level', 'storage_level', 'efficiency_level', 'base_rate', 'capacity', 'last_claim_at'],
    'wcoin_transactions' => ['id', 'user_id', 'amount', 'type', 'description', 'created_at'],
    'wpoint_transactions' => ['id', 'user_id', 'amount', 'type', 'description', 'created_at'],
    'social_events' => ['id', 'username', 'event_type', 'description', 'created_at']
];

// Khởi tạo các mảng dữ liệu mặc định để tránh lỗi rỗng
$user_data = [];
$changelogs = [];
$giftcodes = [];
$diamond = [
    'balance' => 0,
    'machines' => []
];
$transactions = [
    'wcoin' => [],
    'wpoint' => []
];
$ranking = [];
$social = [];

// 4. Lấy dữ liệu người dùng (users + web_wallets)
try {
    if (table_exists($pdo, 'users')) {
        $user_cols = get_safe_columns($pdo, 'users', $whitelist['users']);
        if (in_array('username', $user_cols, true)) {
            $select_user = implode(', ', array_map(fn($c) => "`$c`", $user_cols));
            $stmt = $pdo->prepare("SELECT $select_user FROM `users` WHERE `username` = :username LIMIT 1");
            $stmt->execute([':username' => $user]);
            $u = $stmt->fetch();
            if ($u) {
                $user_data = $u;
                
                // Khởi tạo ví chuẩn từ bảng users (wcoin, wpoint)
                $user_data['wallet'] = [
                    'wcoin' => (int) ($u['wcoin'] ?? 0),
                    'wpoint' => (int) ($u['wpoint'] ?? 0),
                    'balance' => 0
                ];
                
                // Query kết hợp web_wallets để lấy balance phụ (nếu có)
                if (table_exists($pdo, 'web_wallets') && isset($u['id'])) {
                    $wallet_cols = get_safe_columns($pdo, 'web_wallets', $whitelist['web_wallets']);
                    if (in_array('user_id', $wallet_cols, true)) {
                        $select_wallet = implode(', ', array_map(fn($c) => "`$c`", $wallet_cols));
                        $stmt = $pdo->prepare("SELECT $select_wallet FROM `web_wallets` WHERE `user_id` = :user_id LIMIT 1");
                        $stmt->execute([':user_id' => $u['id']]);
                        $w = $stmt->fetch();
                        if ($w) {
                            $user_data['wallet']['balance'] = (int) ($w['balance'] ?? 0);
                        }
                    }
                }
                
                // Loại bỏ trường wcoin và wpoint ở cấp độ root để giữ data sạch
                unset($user_data['wcoin'], $user_data['wpoint']);
            }
        }
    }
} catch (Exception $e) {
    // Bỏ qua lỗi cục bộ
}

$user_db_id = $user_data['id'] ?? null;

// 5. Lấy changelog (changelogs - Limit 5)
try {
    if (table_exists($pdo, 'changelogs')) {
        $cols = get_safe_columns($pdo, 'changelogs', $whitelist['changelogs']);
        if ($cols) {
            $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
            $query = "SELECT $select FROM `changelogs`";
            $where = [];
            if (in_array('is_published', $cols, true)) {
                $where[] = "`is_published` = 1";
            }
            if (!empty($where)) {
                $query .= " WHERE " . implode(" AND ", $where);
            }
            if (in_array('created_at', $cols, true)) {
                $query .= " ORDER BY `created_at` DESC";
            } elseif (in_array('version_date', $cols, true)) {
                $query .= " ORDER BY `version_date` DESC";
            } else {
                $query .= " ORDER BY `id` DESC";
            }
            $query .= " LIMIT 5";
            $changelogs = $pdo->query($query)->fetchAll();
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// 6. Lấy mã quà tặng (giftcodes - Limit 10, chỉ các cột public-safe)
try {
    if (table_exists($pdo, 'giftcodes')) {
        $cols = get_safe_columns($pdo, 'giftcodes', $whitelist['giftcodes']);
        if ($cols) {
            $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
            $giftcodes = $pdo->query("SELECT $select FROM `giftcodes` ORDER BY `id` DESC LIMIT 10")->fetchAll();
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// 7. Lấy dữ liệu Diamond Generator (diamond_wallets + diamond_machines)
try {
    if ($user_db_id !== null) {
        if (table_exists($pdo, 'diamond_wallets')) {
            $cols = get_safe_columns($pdo, 'diamond_wallets', $whitelist['diamond_wallets']);
            if (in_array('user_id', $cols, true) && in_array('balance', $cols, true)) {
                $stmt = $pdo->prepare("SELECT `balance` FROM `diamond_wallets` WHERE `user_id` = :user_id LIMIT 1");
                $stmt->execute([':user_id' => $user_db_id]);
                $val = $stmt->fetchColumn();
                if ($val !== false) {
                    $diamond['balance'] = (int) $val;
                }
            }
        }
        if (table_exists($pdo, 'diamond_machines')) {
            $cols = get_safe_columns($pdo, 'diamond_machines', $whitelist['diamond_machines']);
            if (in_array('user_id', $cols, true)) {
                $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
                $stmt = $pdo->prepare("SELECT $select FROM `diamond_machines` WHERE `user_id` = :user_id ORDER BY `machine_index` ASC");
                $stmt->execute([':user_id' => $user_db_id]);
                $diamond['machines'] = $stmt->fetchAll();
            }
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// 8. Lấy giao dịch gần đây (wcoin_transactions + wpoint_transactions - Limit 10 mỗi bảng)
try {
    if ($user_db_id !== null) {
        if (table_exists($pdo, 'wcoin_transactions')) {
            $cols = get_safe_columns($pdo, 'wcoin_transactions', $whitelist['wcoin_transactions']);
            if (in_array('user_id', $cols, true)) {
                $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
                $order_col = in_array('created_at', $cols, true) ? 'created_at' : 'id';
                $stmt = $pdo->prepare("SELECT $select FROM `wcoin_transactions` WHERE `user_id` = :user_id ORDER BY `$order_col` DESC LIMIT 10");
                $stmt->execute([':user_id' => $user_db_id]);
                $transactions['wcoin'] = $stmt->fetchAll();
            }
        }
        if (table_exists($pdo, 'wpoint_transactions')) {
            $cols = get_safe_columns($pdo, 'wpoint_transactions', $whitelist['wpoint_transactions']);
            if (in_array('user_id', $cols, true)) {
                $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
                $order_col = in_array('created_at', $cols, true) ? 'created_at' : 'id';
                $stmt = $pdo->prepare("SELECT $select FROM `wpoint_transactions` WHERE `user_id` = :user_id ORDER BY `$order_col` DESC LIMIT 10");
                $stmt->execute([':user_id' => $user_db_id]);
                $transactions['wpoint'] = $stmt->fetchAll();
            }
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// 9. Lấy Vinh danh (hall_of_fame_legends - Limit 10)
try {
    if (table_exists($pdo, 'hall_of_fame_legends')) {
        $cols = get_safe_columns($pdo, 'hall_of_fame_legends', $whitelist['hall_of_fame_legends']);
        if ($cols) {
            $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
            $order_col = in_array('created_at', $cols, true) ? 'created_at' : 'id';
            $ranking = $pdo->query("SELECT $select FROM `hall_of_fame_legends` ORDER BY `$order_col` DESC LIMIT 10")->fetchAll();
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// 10. Lấy bảng tin hoạt động (social_events - Limit 10)
try {
    if (table_exists($pdo, 'social_events')) {
        $cols = get_safe_columns($pdo, 'social_events', $whitelist['social_events']);
        if ($cols) {
            $select = implode(', ', array_map(fn($c) => "`$c`", $cols));
            $order_col = in_array('created_at', $cols, true) ? 'created_at' : 'id';
            $social = $pdo->query("SELECT $select FROM `social_events` ORDER BY `$order_col` DESC LIMIT 10")->fetchAll();
        }
    }
} catch (Exception $e) {
    // Bỏ qua
}

// Feature flags trạng thái của các action ghi (chưa kích hoạt/chế độ read-only)
$features = [
    'diamond_generator_enabled' => false,
    'giftcode_redeem_enabled' => false,
    'lucky_spin_enabled' => false,
    'pshop_enabled' => false,
    's1_shop_enabled' => false,
];

// Trả về JSON kết quả
echo json_encode([
    'user' => $user_data,
    'announcements' => $changelogs,
    'giftcodes' => $giftcodes,
    'diamond' => $diamond,
    'transactions' => $transactions,
    'ranking' => $ranking,
    'social' => $social,
    'features' => $features
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
