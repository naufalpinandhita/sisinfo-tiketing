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
    $namaVenue = sanitize($_POST['nama_venue'] ?? '');
    $alamat    = sanitize($_POST['alamat'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);

    if ($namaVenue === '' || $alamat === '' || $kapasitas < 1) {
        flash_message('error', 'Semua field harus diisi dengan benar.');
        header('Location: create.php');
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO venue (nama_venue, alamat, kapasitas) VALUES (?, ?, ?)");
    $stmt->execute([$namaVenue, $alamat, $kapasitas]);

    flash_message('success', 'Venue berhasil ditambahkan.');
    header('Location: index.php');
    exit;
}

if ($act === 'update') {
    $idVenue   = (int)($_POST['id_venue'] ?? 0);
    $namaVenue = sanitize($_POST['nama_venue'] ?? '');
    $alamat    = sanitize($_POST['alamat'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);

    if ($idVenue < 1 || $namaVenue === '' || $alamat === '' || $kapasitas < 1) {
        flash_message('error', 'Semua field harus diisi dengan benar.');
        header('Location: edit.php?id_venue=' . $idVenue);
        exit;
    }

    $stmt = $conn->prepare("UPDATE venue SET nama_venue = ?, alamat = ?, kapasitas = ? WHERE id_venue = ?");
    $stmt->execute([$namaVenue, $alamat, $kapasitas, $idVenue]);

    flash_message('success', 'Venue berhasil diupdate.');
    header('Location: index.php');
    exit;
}

if ($act === 'delete') {
    $idVenue = (int)($_GET['id_venue'] ?? 0);

    if ($idVenue < 1) {
        flash_message('error', 'ID venue tidak valid.');
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM event WHERE id_venue = ?");
    $stmt->execute([$idVenue]);
    $eventCount = (int)$stmt->fetchColumn();

    if ($eventCount > 0) {
        flash_message('warning', 'Venue ini memiliki event. Event terkait akan ikut terhapus karena cascade delete.');
    }

    $stmt = $conn->prepare("DELETE FROM venue WHERE id_venue = ?");
    $stmt->execute([$idVenue]);

    flash_message('success', 'Venue berhasil dihapus.');
    header('Location: index.php');
    exit;
}

flash_message('error', 'Aksi tidak dikenali.');
header('Location: index.php');
exit;