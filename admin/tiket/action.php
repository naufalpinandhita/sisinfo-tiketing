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

if ($act === 'insert') {
    $idEvent   = (int)($_POST['id_event'] ?? 0);
    $namaTiket = sanitize($_POST['nama_tiket'] ?? '');
    $harga     = (int)($_POST['harga'] ?? 0);
    $kuota     = (int)($_POST['kuota'] ?? 0);

    if ($idEvent < 1 || $namaTiket === '' || $harga < 1 || $kuota < 1) {
        flash_message('error', 'Semua field wajib diisi. Harga dan kuota harus lebih dari 0.');
        header('Location: create.php');
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tiket (id_event, nama_tiket, harga, kuota) VALUES (?, ?, ?, ?)");
    $stmt->execute([$idEvent, $namaTiket, $harga, $kuota]);

    flash_message('success', 'Tiket berhasil ditambahkan.');
    header('Location: index.php');
    exit;
}

if ($act === 'update') {
    $idTiket = (int)($_POST['id_tiket'] ?? 0);
    $harga   = (int)($_POST['harga'] ?? 0);
    $kuota   = (int)($_POST['kuota'] ?? 0);

    if ($idTiket < 1 || $harga < 1 || $kuota < 1) {
        flash_message('error', 'Harga dan kuota harus lebih dari 0.');
        header('Location: edit.php?id_tiket=' . $idTiket);
        exit;
    }

    $stmt = $conn->prepare("UPDATE tiket SET harga = ?, kuota = ? WHERE id_tiket = ?");
    $stmt->execute([$harga, $kuota, $idTiket]);

    flash_message('success', 'Tiket berhasil diupdate.');
    header('Location: index.php');
    exit;
}

if ($act === 'delete') {
    $idTiket = (int)($_GET['id_tiket'] ?? 0);

    if ($idTiket < 1) {
        flash_message('error', 'ID tiket tidak valid.');
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM tiket WHERE id_tiket = ?");
    $stmt->execute([$idTiket]);

    flash_message('success', 'Tiket berhasil dihapus.');
    header('Location: index.php');
    exit;
}

flash_message('error', 'Aksi tidak dikenali.');
header('Location: index.php');
exit;

