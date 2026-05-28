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

function uploadPoster(array $file): string|false {
    $uploadDir = __DIR__ . '/../../assets/images/posters/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        return false;
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMime)) {
        return false;
    }

    $filename = uniqid('poster_') . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'posters/' . $filename;
    }
    return false;
}

function deletePoster(string $posterPath): void {
    if (empty($posterPath)) return;
    $file = __DIR__ . '/../../assets/images/' . $posterPath;
    if (file_exists($file)) {
        unlink($file);
    }
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'insert') {
    $namaEvent = sanitize($_POST['nama_event'] ?? '');
    $tanggal   = $_POST['tanggal'] ?? '';
    $idVenue   = (int)($_POST['id_venue'] ?? 0);
    $jam       = sanitize($_POST['jam'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if ($namaEvent === '' || $tanggal === '' || $idVenue < 1) {
        flash_message('error', 'Semua field wajib diisi.');
        header('Location: create.php');
        exit;
    }
    if ($tanggal < date('Y-m-d')) {
        flash_message('error', 'Tanggal event tidak boleh di masa lalu.');
        header('Location: create.php');
        exit;
    }

    $posterUrl = '';
    if (!empty($_FILES['poster']['tmp_name'])) {
        $posterUrl = uploadPoster($_FILES['poster']);
        if ($posterUrl === false) {
            flash_message('error', 'Gagal upload poster. Pastikan format JPG/PNG/GIF/WEBP dan maks 2MB.');
            header('Location: create.php');
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO event (nama_event, tanggal, jam, deskripsi, poster_url, id_venue) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$namaEvent, $tanggal, $jam ?: null, $deskripsi ?: null, $posterUrl, $idVenue]);

    flash_message('success', 'Event berhasil ditambahkan.');
    header('Location: index.php');
    exit;
}

if ($act === 'update') {
    $idEvent   = (int)($_POST['id_event'] ?? 0);
    $namaEvent = sanitize($_POST['nama_event'] ?? '');
    $tanggal   = $_POST['tanggal'] ?? '';
    $idVenue   = (int)($_POST['id_venue'] ?? 0);
    $jam       = sanitize($_POST['jam'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if ($idEvent < 1 || $namaEvent === '' || $tanggal === '' || $idVenue < 1) {
        flash_message('error', 'Semua field wajib diisi.');
        header('Location: edit.php?id_event=' . $idEvent);
        exit;
    }
    if ($tanggal < date('Y-m-d')) {
        flash_message('error', 'Tanggal event tidak boleh di masa lalu.');
        header('Location: edit.php?id_event=' . $idEvent);
        exit;
    }

    $stmt = $conn->prepare("SELECT poster_url FROM event WHERE id_event = ?");
    $stmt->execute([$idEvent]);
    $oldPoster = $stmt->fetchColumn() ?: '';

    $posterUrl = $oldPoster;
    if (!empty($_FILES['poster']['tmp_name'])) {
        $newPoster = uploadPoster($_FILES['poster']);
        if ($newPoster === false) {
            flash_message('error', 'Gagal upload poster. Pastikan format JPG/PNG/GIF/WEBP dan maks 2MB.');
            header('Location: edit.php?id_event=' . $idEvent);
            exit;
        }
        deletePoster($oldPoster);
        $posterUrl = $newPoster;
    }

    $stmt = $conn->prepare("UPDATE event SET nama_event = ?, tanggal = ?, jam = ?, deskripsi = ?, poster_url = ?, id_venue = ? WHERE id_event = ?");
    $stmt->execute([$namaEvent, $tanggal, $jam ?: null, $deskripsi ?: null, $posterUrl, $idVenue, $idEvent]);

    flash_message('success', 'Event berhasil diupdate.');
    header('Location: index.php');
    exit;
}

if ($act === 'delete') {
    $idEvent = (int)($_GET['id_event'] ?? 0);

    if ($idEvent < 1) {
        flash_message('error', 'ID event tidak valid.');
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT poster_url FROM event WHERE id_event = ?");
    $stmt->execute([$idEvent]);
    $poster = $stmt->fetchColumn() ?: '';
    deletePoster($poster);

    $stmt = $conn->prepare("DELETE FROM event WHERE id_event = ?");
    $stmt->execute([$idEvent]);

    flash_message('success', 'Event berhasil dihapus.');
    header('Location: index.php');
    exit;
}

flash_message('error', 'Aksi tidak dikenali.');
header('Location: index.php');
exit;
