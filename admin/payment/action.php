<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { die('Akses ditolak'); }

validate_csrf();

$act     = $_POST['act'] ?? '';
$id_order = (int)($_POST['id_order'] ?? 0);

if ($id_order < 1) {
    flash_message('error', 'Order tidak valid.');
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id_order = ?");
$stmt->execute([$id_order]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_message('error', 'Order tidak ditemukan.');
    header('Location: index.php');
    exit;
}

if ($order['status'] !== 'waiting_confirmation') {
    flash_message('error', 'Order ini sudah diproses.');
    header('Location: detail.php?id=' . $id_order);
    exit;
}

$stmtConf = $conn->prepare("SELECT * FROM payment_confirmation WHERE id_order = ?");
$stmtConf->execute([$id_order]);
$confirmation = $stmtConf->fetch(PDO::FETCH_ASSOC);

if (!$confirmation) {
    flash_message('error', 'Bukti pembayaran tidak ditemukan.');
    header('Location: index.php');
    exit;
}

// ── APPROVE ──────────────────────────────────────────────────────────────
if ($act === 'approve') {
    try {
        $conn->beginTransaction();

        // Update confirmation
        $conn->prepare("
            UPDATE payment_confirmation
            SET status = 'approved', confirmed_by = ?, confirmed_at = NOW()
            WHERE id_order = ?
        ")->execute([$_SESSION['user_id'], $id_order]);

        // Update order status
        $conn->prepare("UPDATE orders SET status = 'paid', expired_at = NULL WHERE id_order = ?")
            ->execute([$id_order]);

        // Generate tickets (moved from order_process.php)
        $attendee_names = $_SESSION['pending_attendees'][$id_order] ?? [];

        // Get user name
        $stmtUser = $conn->prepare("SELECT nama FROM users WHERE id_user = ?");
        $stmtUser->execute([$order['id_user']]);
        $user_name = $stmtUser->fetchColumn();

        // Fetch order items
        $stmtItems = $conn->prepare("SELECT od.*, t.nama_tiket FROM order_detail od JOIN tiket t ON od.id_tiket = t.id_tiket WHERE od.id_order = ?");
        $stmtItems->execute([$id_order]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Check if nama_attendee column exists
        try {
            $conn->query("SELECT nama_attendee FROM attendee LIMIT 1");
            $has_nama = true;
        } catch (PDOException $e) {
            $has_nama = false;
        }

        $stmtAttendee = $has_nama
            ? $conn->prepare("INSERT INTO attendee (id_detail, kode_tiket, status_checkin, nama_attendee) VALUES (?, ?, 'belum', ?)")
            : $conn->prepare("INSERT INTO attendee (id_detail, kode_tiket, status_checkin) VALUES (?, ?, 'belum')");

        foreach ($orderItems as $item) {
            for ($i = 0; $i < $item['qty']; $i++) {
                $date = date('Ymd');
                $check = $conn->prepare("SELECT 1 FROM attendee WHERE kode_tiket = ?");
                do {
                    $random = substr(strtoupper(bin2hex(random_bytes(5))), 0, 8);
                    $kode = 'EVT-' . $date . '-' . $random;
                    $check->execute([$kode]);
                } while ($check->fetch());

                $nama = $attendee_names[$item['id_tiket']][$i] ?? $user_name;
                if ($has_nama) {
                    $stmtAttendee->execute([$item['id_detail'], $kode, $nama]);
                } else {
                    $stmtAttendee->execute([$item['id_detail'], $kode]);
                }
            }
        }

        // Clean up session
        unset($_SESSION['pending_attendees'][$id_order]);

        $conn->commit();

        flash_message('success', 'Pembayaran disetujui! Tiket berhasil di-generate.');
        header('Location: detail.php?id=' . $id_order);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        flash_message('error', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: detail.php?id=' . $id_order);
        exit;
    }
}

// ── REJECT ───────────────────────────────────────────────────────────────
if ($act === 'reject') {
    $reject_reason = sanitize($_POST['reject_reason'] ?? '');
    if ($reject_reason === '') {
        flash_message('error', 'Alasan penolakan wajib diisi.');
        header('Location: detail.php?id=' . $id_order);
        exit;
    }

    try {
        $conn->beginTransaction();

        $conn->prepare("
            UPDATE payment_confirmation
            SET status = 'rejected', reject_reason = ?, confirmed_by = ?, confirmed_at = NOW()
            WHERE id_order = ?
        ")->execute([$reject_reason, $_SESSION['user_id'], $id_order]);

        $conn->prepare("UPDATE orders SET status = 'rejected' WHERE id_order = ?")
            ->execute([$id_order]);

        $conn->commit();

        flash_message('success', 'Pembayaran ditolak.');
        header('Location: detail.php?id=' . $id_order);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        flash_message('error', 'Terjadi kesalahan saat memproses.');
        header('Location: detail.php?id=' . $id_order);
        exit;
    }
}

flash_message('error', 'Aksi tidak dikenali.');
header('Location: index.php');
exit;
