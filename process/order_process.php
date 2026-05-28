<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['role'] !== 'user') {
    die('Akses ditolak');
}

validate_csrf();

$id_event       = (int)($_POST['id_event'] ?? 0);
$qty_input      = $_POST['qty'] ?? [];
$voucher_code   = sanitize($_POST['voucher_code'] ?? '');
$attendee_names = $_POST['attendee_names'] ?? [];
$user_id        = $_SESSION['user_id'];
$user_name      = $_SESSION['nama'] ?? null;

if ($id_event < 1) {
    flash_message('error', 'Event tidak valid.');
    header('Location: ../user/home.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM event WHERE id_event = ?");
$stmt->execute([$id_event]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: ../user/home.php');
    exit;
}

$items = [];
$subtotal = 0;
foreach ($qty_input as $id_tiket => $qty_val) {
    $qty = (int)$qty_val;
    if ($qty < 1) continue;

    $stmtT = $conn->prepare("
        SELECT t.*,
            (t.kuota - COALESCE(
                (SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0
            )) as sisa
        FROM tiket t
        WHERE t.id_tiket = ? AND t.id_event = ?
    ");
    $stmtT->execute([$id_tiket, $id_event]);
    $tiket = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$tiket) continue;
    if ($qty > $tiket['sisa']) {
        flash_message('error', 'Kuota tiket ' . htmlspecialchars($tiket['nama_tiket']) . ' tidak mencukupi.');
        header('Location: ../user/detail_event.php?id=' . $id_event);
        exit;
    }

    $item_sub = $qty * $tiket['harga'];
    $items[] = [
        'id_tiket' => $id_tiket,
        'nama_tiket' => $tiket['nama_tiket'],
        'harga' => $tiket['harga'],
        'qty' => $qty,
        'subtotal' => $item_sub,
    ];
    $subtotal += $item_sub;
}

if (count($items) === 0) {
    flash_message('error', 'Pilih minimal 1 tiket.');
    header('Location: ../user/detail_event.php?id=' . $id_event);
    exit;
}

$id_voucher = null;
$voucher_discount = 0;
if ($voucher_code !== '') {
    $stmtV = $conn->prepare("
        SELECT * FROM voucher
        WHERE kode_voucher = ? AND status = 'aktif' AND kuota > 0
        FOR UPDATE
    ");
    $stmtV->execute([strtoupper($voucher_code)]);
    $voucher = $stmtV->fetch(PDO::FETCH_ASSOC);
    if ($voucher) {
        $id_voucher = $voucher['id_voucher'];
        $voucher_discount = (int)$voucher['potongan'];
    }
}

$total = max(0, $subtotal - $voucher_discount);

try {
    $conn->beginTransaction();

    // Generate invoice code
    $datePrefix = date('Ymd');
    $seqStmt = $conn->query("SELECT COUNT(*)+1 FROM orders WHERE DATE(tanggal_order) = CURDATE()");
    $seq = (int)$seqStmt->fetchColumn();
    $invoice_code = 'INV-' . $datePrefix . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);

    $stmtOrder = $conn->prepare("
        INSERT INTO orders (id_user, invoice_code, total, status, id_voucher, expired_at)
        VALUES (?, ?, ?, 'pending_payment', ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
    ");
    $stmtOrder->execute([$user_id, $invoice_code, $total, $id_voucher]);
    $id_order = (int)$conn->lastInsertId();

    $stmtDetail = $conn->prepare("
        INSERT INTO order_detail (id_order, id_tiket, qty, subtotal)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $stmtDetail->execute([$id_order, $item['id_tiket'], $item['qty'], $item['subtotal']]);
    }

    if ($id_voucher) {
        $conn->prepare("UPDATE voucher SET kuota = kuota - 1 WHERE id_voucher = ?")
            ->execute([$id_voucher]);
    }

    $conn->commit();

    // Store attendee names in session for use after payment approval
    $_SESSION['pending_attendees'][$id_order] = $attendee_names;

    flash_message('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
    header('Location: ../user/payment.php?id=' . $id_order);
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    flash_message('error', 'Terjadi kesalahan saat memproses pemesanan.');
    header('Location: ../user/checkout.php');
    exit;
}
