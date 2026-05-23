<?php 
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$act = $_GET['act'];

if ($act == 'insert'){
    $namaEvent  = $_POST['nama_event'];
    $tanggal    = $_POST['tanggal'];
    $idVenue    = $_POST['id_venue'];

    $stmt = $conn->prepare("INSERT INTO event (nama_event, tanggal, id_venue) VALUES (?, ?, ?)");
    $stmt->execute([$namaEvent, $tanggal, $idVenue]);

    $success = urlencode("Event berhasil ditambahkan");
    header("Location: index.php?success=" . $success);
    exit();
}

if ($act == 'update'){
    $idEvent    = $_POST['id_event'];
    $namaEvent  = $_POST['nama_event'];
    $tanggal    = $_POST['tanggal'];
    $idVenue    = $_POST['id_venue'];

    $stmt = $conn->prepare("UPDATE event SET nama_event = ?, tanggal = ?, id_venue = ? WHERE id_event = ?");
    $stmt->execute([$namaEvent, $tanggal, $idVenue, $idEvent]);

    $success = urlencode("Data event berhasil diubah");
    header("Location: edit.php?id_event=" . $idEvent . "&success=" . $success);
    exit();
}

if ($act == 'delete'){
    $idEvent    = $_GET['id_event'];

    $stmt = $conn->prepare("DELETE FROM event WHERE id_event = ?");
    $stmt->execute([$idEvent]);

    $success = urlencode("Event berhasil dihapus");
    header("Location: index.php?success=" . $success);
    exit();
}
?>