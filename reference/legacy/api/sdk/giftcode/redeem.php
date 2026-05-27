<?php
declare(strict_types=1);

/**
 * public/api/sdk/giftcode/redeem.php
 *
 * API xử lý nhập mã Giftcode và cấp phần thưởng portal_credit (Wcoin/Wpoint).
 * Chỉ đọc/ghi Portal DB trong một Transaction an toàn, có khóa dòng FOR UPDATE.
 */

require_once __DIR__ . '/../../../../app/bootstrap.php';
require_once __DIR__ . '/../../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Kiểm tra session legacy_username
$username = $_SESSION['legacy_username'] ?? null;
if ($username === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Phiên chơi đã hết hạn, vui lòng đăng nhập lại qua ccgame.org.'
    ]);
    exit;
}

// 2. Lấy dữ liệu đầu vào
$code = trim((string) ($_POST['code'] ?? ''));
if ($code === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập mã giftcode.'
    ]);
    exit;
}

try {
    $pdo = db_pdo();
    
    // A. Lấy thông tin User ID thật từ DB dựa trên username trong session
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy thông tin tài khoản legacy.'
        ]);
        exit;
    }
    $userId = (int) $user['id'];
    
    // B. Khởi động Transaction trên Portal DB
    $pdo->beginTransaction();
    
    // 1. Khóa dòng Giftcode bằng FOR UPDATE để chống Race Condition
    $stmt = $pdo->prepare("SELECT id, code, limit_usage, used_count, reward_type, reward_data, expires_at FROM giftcodes WHERE code = :code LIMIT 1 FOR UPDATE");
    $stmt->execute([':code' => $code]);
    $giftcode = $stmt->fetch();
    
    if (!$giftcode) {
        throw new PDOException("Mã giftcode không tồn tại.", 400);
    }
    
    // 2. Kiểm tra hạn sử dụng
    if ($giftcode['expires_at'] !== null && strtotime($giftcode['expires_at']) < time()) {
        throw new PDOException("Mã giftcode đã hết hạn sử dụng.", 400);
    }
    
    // 3. Kiểm tra giới hạn lượt dùng toàn máy chủ
    $limitUsage = (int) $giftcode['limit_usage'];
    $usedCount = (int) $giftcode['used_count'];
    if ($limitUsage > 0 && $usedCount >= $limitUsage) {
        throw new PDOException("Mã giftcode đã hết lượt sử dụng.", 400);
    }
    
    // 4. Kiểm tra loại phần thưởng (PATCH 1 chỉ cho phép portal_credit)
    if ($giftcode['reward_type'] !== 'portal_credit') {
        throw new PDOException("Giftcode vật phẩm game chưa mở, cần xác minh GM mail bridge.", 400);
    }
    
    // 5. Parse dữ liệu phần thưởng JSON
    $rewardData = json_decode((string) $giftcode['reward_data'], true);
    if (!is_array($rewardData)) {
        throw new PDOException("Dữ liệu phần thưởng giftcode không hợp lệ.", 400);
    }
    
    $currency = strtolower(trim((string) ($rewardData['currency'] ?? '')));
    $amount = (int) ($rewardData['amount'] ?? 0);
    
    if ($currency !== 'wcoin' && $currency !== 'wpoint') {
        throw new PDOException("Loại tiền tệ phần thưởng không được hỗ trợ.", 400);
    }
    if ($amount <= 0) {
        throw new PDOException("Số lượng phần thưởng không hợp lệ.", 400);
    }
    
    // 6. Kiểm tra xem người dùng hiện tại đã dùng mã này chưa (Đồng thời khóa dòng SELECT)
    $stmt = $pdo->prepare("SELECT id FROM giftcode_redemptions WHERE giftcode_id = :gid AND user_id = :uid LIMIT 1 FOR UPDATE");
    $stmt->execute([
        ':gid' => (int) $giftcode['id'],
        ':uid' => $userId
    ]);
    if ($stmt->fetch()) {
        throw new PDOException("Bạn đã sử dụng mã giftcode này rồi.", 400);
    }
    
    // C. TIẾN HÀNH GHI DỮ LIỆU
    
    // 1. Lưu lịch sử redemption
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO giftcode_redemptions (giftcode_id, user_id, ip_address, created_at, updated_at) VALUES (:gid, :uid, :ip, NOW(), NOW())");
    $stmt->execute([
        ':gid' => (int) $giftcode['id'],
        ':uid' => $userId,
        ':ip'  => $ipAddress
    ]);
    
    // 2. Tăng lượt sử dụng giftcode
    $stmt = $pdo->prepare("UPDATE giftcodes SET used_count = used_count + 1 WHERE id = :gid");
    $stmt->execute([':gid' => (int) $giftcode['id']]);
    
    // 3. Cập nhật số dư người dùng và chèn nhật ký giao dịch
    // Lấy và khóa dòng số dư hiện tại của người dùng
    $stmt = $pdo->prepare("SELECT wcoin, wpoint FROM users WHERE id = :uid FOR UPDATE");
    $stmt->execute([':uid' => $userId]);
    $userRow = $stmt->fetch();
    if (!$userRow) {
        throw new PDOException("Không tìm thấy thông tin tài khoản để cập nhật số dư.", 400);
    }
    
    $currentBalance = (int) ($userRow[$currency] ?? 0);
    $newBalance = $currentBalance + $amount;
    
    // Cập nhật số dư mới
    $stmt = $pdo->prepare("UPDATE users SET `$currency` = :new_balance WHERE id = :uid");
    $stmt->execute([
        ':new_balance' => $newBalance,
        ':uid'         => $userId
    ]);
    
    // Chèn lịch sử giao dịch tương ứng
    if ($currency === 'wpoint') {
        $stmt = $pdo->prepare("INSERT INTO wpoint_transactions (user_id, type, amount, balance_after, reference, created_at, updated_at) VALUES (:uid, 'giftcode', :amount, :balance_after, :ref, NOW(), NOW())");
        $stmt->execute([
            ':uid'           => $userId,
            ':amount'        => $amount,
            ':balance_after' => $newBalance,
            ':ref'           => $code
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wcoin_transactions (user_id, type, amount, balance_after, reference, created_at, updated_at) VALUES (:uid, 'giftcode', :amount, :balance_after, :ref, NOW(), NOW())");
        $stmt->execute([
            ':uid'           => $userId,
            ':amount'        => $amount,
            ':balance_after' => $newBalance,
            ':ref'           => $code
        ]);
    }
    
    // D. COMMIT TRANSACTION
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Nhận thành công " . number_format($amount) . " " . strtoupper($currency) . " vào ví tài khoản!"
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Kiểm tra mã lỗi xung đột khóa/deadlock
    if ($e->getCode() === '40001' || $e->getCode() === 'HY000') {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Hệ thống đang bận xử lý yêu cầu, vui lòng thử lại sau.'
        ]);
    } else {
        http_response_code($e->getCode() === 400 ? 400 : 500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() === 400 ? 400 : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
