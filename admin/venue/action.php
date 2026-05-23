<?php 
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$act = $_GET['act'];

if ($act == 'insert'){
    $namaVenue  = $_POST['nama_venue'];
    $alamat     = $_POST['alamat'];
    $kapasitas  = $_POST['kapasitas'];

    $stmt = $conn->prepare("INSERT INTO venue (nama_venue, alamat, kapasitas) VALUES (?, ?, ?)");
    $stmt->execute([$namaVenue, $alamat, $kapasitas]);

    $success = urlencode("Venue berhasil ditambahkan");
    header("Location: index.php?success=" . $success);
    exit();
}

if ($act == 'update'){
    $idVenue    = $_POST['id_venue'];
    $namaVenue  = $_POST['nama_venue'];
    $alamat     = $_POST['alamat'];
    $kapasitas  = $_POST['kapasitas'];

    $stmt = $conn->prepare("UPDATE venue SET nama_venue = ?, alamat = ?, kapasitas = ? WHERE id_venue = ?");
    $stmt->execute([$namaVenue, $alamat, $kapasitas, $idVenue]);

    $success = urlencode("Data venue berhasil diubah");
    header("Location: edit.php?id_venue=" . $idVenue . "&success=" . $success);
    exit();
}

if ($act == 'delete'){
    $idVenue    = $_GET['id_venue'];

    $stmt = $conn->prepare("DELETE FROM venue WHERE id_venue = ?");
    $stmt->execute([$idVenue]);

    $success = urlencode("Venue berhasil dihapus");
    header("Location: index.php?success=" . $success);
    exit();
}
?>