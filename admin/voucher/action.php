<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$act = $_GET['act'];

if ($act == 'insert') {
    $kodeVoucher = $_POST['kode_voucher'];
    $potongan    = $_POST['potongan'];
    $kuota       = $_POST['kuota'];
    $status      = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO voucher (kode_voucher, potongan, kuota, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$kodeVoucher, $potongan, $kuota, $status]);

    $success = urlencode("Voucher berhasil ditambahkan");
    header("Location: index.php?success=" . $success);
    exit();
}

if ($act == 'update') {
    $idVoucher   = $_POST['id_voucher'];
    $kodeVoucher = $_POST['kode_voucher'];
    $potongan    = $_POST['potongan'];
    $kuota       = $_POST['kuota'];
    $status      = $_POST['status'];

    $stmt = $conn->prepare("UPDATE voucher SET kode_voucher = ?, potongan = ?, kuota = ?, status = ? WHERE id_voucher = ?");
    $stmt->execute([$kodeVoucher, $potongan, $kuota, $status, $idVoucher]);

    $success = urlencode("Data voucher berhasil diubah");
    header("Location: edit.php?id_voucher=" . $idVoucher . "&success=" . $success);
    exit();
}

if ($act == 'delete') {
    $idVoucher = $_GET['id_voucher'];

    $stmt = $conn->prepare("DELETE FROM voucher WHERE id_voucher = ?");
    $stmt->execute([$idVoucher]);

    $success = urlencode("Voucher berhasil dihapus");
    header("Location: index.php?success=" . $success);
    exit();
}
?>
