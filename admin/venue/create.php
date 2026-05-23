<?php
include '../../config/database.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venue - Add</title>
</head>
<body>
    <form action="action.php?act=insert" method="post">
        <input type="text" name="nama_venue" placeholder="Nama Venue" required>
        <input type="text" name="alamat" placeholder="Alamat" required>
        <input type="number" name="kapasitas" placeholder="Kapasitas" required>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>