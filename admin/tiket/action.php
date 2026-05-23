<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$act = $_GET['act'];

if ($act == 'insert') {
    $idEvent    = $_POST['id_event'];
    $namaTiket  = $_POST['nama_tiket'];
    $harga      = $_POST['harga'];
    $kuota      = $_POST['kuota'];

    $stmt = $conn->prepare("INSERT INTO tiket (id_event, nama_tiket, harga, kuota) VALUES (?, ?, ?, ?)");
    $stmt->execute([$idEvent, $namaTiket, $harga, $kuota]);

    $success = urlencode("Tiket berhasil ditambahkan");
    header("Location: index.php?success=" . $success);
    exit();
}

if ($act == 'update') {
    $idTiket    = $_POST['id_tiket'];
    $idEvent    = $_POST['id_event'];
    $namaTiket  = $_POST['nama_tiket'];
    $harga      = $_POST['harga'];
    $kuota      = $_POST['kuota'];

    $stmt = $conn->prepare("UPDATE tiket SET id_event = ?, nama_tiket = ?, harga = ?, kuota = ? WHERE id_tiket = ?");
    $stmt->execute([$idEvent, $namaTiket, $harga, $kuota, $idTiket]);

    $success = urlencode("Data tiket berhasil diubah");
    header("Location: edit.php?id_tiket=" . $idTiket . "&success=" . $success);
    exit();
}

if ($act == 'delete') {
    $idTiket = $_GET['id_tiket'];

    $stmt = $conn->prepare("DELETE FROM tiket WHERE id_tiket = ?");
    $stmt->execute([$idTiket]);

    $success = urlencode("Tiket berhasil dihapus");
    header("Location: index.php?success=" . $success);
    exit();
}
?>
