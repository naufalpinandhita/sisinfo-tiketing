<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak');
}

validate_csrf();

$act = isset($_GET['act']) ? $_GET['act'] : '';

function kodeExists(PDO $conn, string $kode, int $excludeId = 0): bool {
    if ($excludeId > 0) {
        $stmt = $conn->prepare("SELECT id_voucher FROM voucher WHERE kode_voucher = ? AND id_voucher != ?");
        $stmt->execute([$kode, $excludeId]);
    } else {
        $stmt = $conn->prepare("SELECT id_voucher FROM voucher WHERE kode_voucher = ?");
        $stmt->execute([$kode]);
    }
    return $stmt->rowCount() > 0;
}

if ($act === 'insert') {
    $kodeVoucher = strtoupper(sanitize($_POST['kode_voucher'] ?? ''));
    $potongan    = (int)($_POST['potongan'] ?? 0);
    $kuota       = (int)($_POST['kuota'] ?? 0);
    $status      = $_POST['status'] ?? 'aktif';

    if ($kodeVoucher === '' || $potongan < 1 || $kuota < 1) {
        flash_message('error', 'Semua field wajib diisi. Potongan dan kuota harus lebih dari 0.');
        header('Location: create.php');
        exit;
    }
    if (!in_array($status, ['aktif', 'nonaktif'])) {
        flash_message('error', 'Status tidak valid.');
        header('Location: create.php');
        exit;
    }
    if (kodeExists($conn, $kodeVoucher)) {
        flash_message('error', 'Kode voucher sudah digunakan.');
        header('Location: create.php');
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO voucher (kode_voucher, potongan, kuota, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$kodeVoucher, $potongan, $kuota, $status]);

    flash_message('success', 'Voucher berhasil ditambahkan.');
    header('Location: index.php');
    exit;
}

if ($act === 'update') {
    $idVoucher   = (int)($_POST['id_voucher'] ?? 0);
    $kodeVoucher = strtoupper(sanitize($_POST['kode_voucher'] ?? ''));
    $potongan    = (int)($_POST['potongan'] ?? 0);
    $kuota       = (int)($_POST['kuota'] ?? 0);
    $status      = $_POST['status'] ?? 'aktif';

    if ($idVoucher < 1 || $kodeVoucher === '' || $potongan < 1 || $kuota < 1) {
        flash_message('error', 'Semua field wajib diisi.');
        header('Location: edit.php?id_voucher=' . $idVoucher);
        exit;
    }
    if (!in_array($status, ['aktif', 'nonaktif'])) {
        flash_message('error', 'Status tidak valid.');
        header('Location: edit.php?id_voucher=' . $idVoucher);
        exit;
    }
    if (kodeExists($conn, $kodeVoucher, $idVoucher)) {
        flash_message('error', 'Kode voucher sudah digunakan.');
        header('Location: edit.php?id_voucher=' . $idVoucher);
        exit;
    }

    $stmt = $conn->prepare("UPDATE voucher SET kode_voucher = ?, potongan = ?, kuota = ?, status = ? WHERE id_voucher = ?");
    $stmt->execute([$kodeVoucher, $potongan, $kuota, $status, $idVoucher]);

    flash_message('success', 'Voucher berhasil diupdate.');
    header('Location: index.php');
    exit;
}

if ($act === 'delete') {
    $idVoucher = (int)($_GET['id_voucher'] ?? 0);

    if ($idVoucher < 1) {
        flash_message('error', 'ID voucher tidak valid.');
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM voucher WHERE id_voucher = ?");
    $stmt->execute([$idVoucher]);

    flash_message('success', 'Voucher berhasil dihapus.');
    header('Location: index.php');
    exit;
}

flash_message('error', 'Aksi tidak dikenali.');
header('Location: index.php');
exit;
