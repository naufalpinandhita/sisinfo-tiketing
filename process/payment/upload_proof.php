<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['role'] !== 'user')  { die('Akses ditolak'); }

validate_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../user/home.php');
    exit;
}

$id_order = (int)($_POST['id_order'] ?? 0);
$user_id  = $_SESSION['user_id'];
$is_resubmit = !empty($_POST['is_resubmit']);

// Validate order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id_order = ? AND id_user = ?");
$stmt->execute([$id_order, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_message('error', 'Order tidak ditemukan.');
    header('Location: ../../user/home.php');
    exit;
}

if (!$is_resubmit && $order['status'] !== 'pending_payment') {
    flash_message('error', 'Status order tidak valid untuk upload bukti.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

if ($is_resubmit && $order['status'] !== 'rejected') {
    flash_message('error', 'Order ini belum ditolak.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

// Validate inputs
$sender_name = sanitize($_POST['sender_name'] ?? '');
$bank_name   = sanitize($_POST['bank_name'] ?? '');
$notes       = sanitize($_POST['notes'] ?? '');

if ($sender_name === '' || $bank_name === '') {
    flash_message('error', 'Nama pengirim dan bank wajib diisi.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

// Validate file upload
if (empty($_FILES['payment_proof']['tmp_name'])) {
    flash_message('error', 'Bukti pembayaran wajib diupload.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

$file = $_FILES['payment_proof'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    flash_message('error', 'Gagal mengupload file.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    flash_message('error', 'Ukuran file maksimal 2MB.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    flash_message('error', 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMime)) {
    flash_message('error', 'File bukan gambar yang valid.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

// Save file
$uploadDir = __DIR__ . '/../../assets/payment_proof/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$filename = 'proof_' . $id_order . '_' . uniqid() . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    flash_message('error', 'Gagal menyimpan file.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}

$proofPath = 'payment_proof/' . $filename;

try {
    $conn->beginTransaction();

    if ($is_resubmit) {
        // Delete old proof file
        $stmtOld = $conn->prepare("SELECT payment_proof FROM payment_confirmation WHERE id_order = ?");
        $stmtOld->execute([$id_order]);
        $oldProof = $stmtOld->fetchColumn();
        if ($oldProof) {
            $oldFile = __DIR__ . '/../../assets/' . $oldProof;
            if (file_exists($oldFile)) { unlink($oldFile); }
        }
        // Update existing confirmation
        $stmtUpd = $conn->prepare("
            UPDATE payment_confirmation
            SET sender_name = ?, bank_name = ?, notes = ?, payment_proof = ?, status = 'pending', reject_reason = NULL, confirmed_by = NULL, confirmed_at = NULL, created_at = NOW()
            WHERE id_order = ?
        ");
        $stmtUpd->execute([$sender_name, $bank_name, $notes ?: null, $proofPath, $id_order]);
    } else {
        // Delete old rejected confirmation if any
        $stmtOld = $conn->prepare("SELECT payment_proof FROM payment_confirmation WHERE id_order = ?");
        $stmtOld->execute([$id_order]);
        $oldProof = $stmtOld->fetchColumn();
        if ($oldProof) {
            $oldFile = __DIR__ . '/../../assets/' . $oldProof;
            if (file_exists($oldFile)) { unlink($oldFile); }
        }
        $conn->prepare("DELETE FROM payment_confirmation WHERE id_order = ?")->execute([$id_order]);

        $stmtIns = $conn->prepare("
            INSERT INTO payment_confirmation (id_order, sender_name, bank_name, notes, payment_proof)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtIns->execute([$id_order, $sender_name, $bank_name, $notes ?: null, $proofPath]);
    }

    // Update order status
    $conn->prepare("UPDATE orders SET status = 'waiting_confirmation' WHERE id_order = ?")->execute([$id_order]);

    $conn->commit();

    flash_message('success', 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    // Clean up uploaded file on error
    if (file_exists($dest)) { unlink($dest); }
    flash_message('error', 'Terjadi kesalahan saat menyimpan data.');
    header('Location: ../../user/payment.php?id=' . $id_order);
    exit;
}
