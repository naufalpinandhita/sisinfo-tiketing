<?php
// Expire orders that have passed their payment deadline.
// Can be called from admin dashboard or via cron/scheduled task.
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'expired'
        WHERE status = 'pending_payment'
          AND expired_at IS NOT NULL
          AND expired_at < NOW()
    ");
    $stmt->execute();
    $expired = $stmt->rowCount();

    // Also expire waiting_confirmation orders older than 24 hours
    // (admin had time to verify but didn't)
    $stmt2 = $conn->prepare("
        UPDATE orders
        SET status = 'expired'
        WHERE status = 'waiting_confirmation'
          AND tanggal_order < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt2->execute();
    $expired2 = $stmt2->rowCount();

    if (php_sapi_name() !== 'cli') {
        // Called from browser (admin)
        echo json_encode([
            'success' => true,
            'expired_pending' => $expired,
            'expired_waiting' => $expired2,
        ]);
    } else {
        echo "Expired {$expired} pending_payment orders, {$expired2} waiting_confirmation orders.\n";
    }
} catch (PDOException $e) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
