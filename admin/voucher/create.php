<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - Add</title>
</head>
<body>
    <h1>Tambah Voucher</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=insert" method="post">
        <input type="text" name="kode_voucher" placeholder="Kode Voucher" required>
        <input type="number" name="potongan" placeholder="Potongan (Rp)" required>
        <input type="number" name="kuota" placeholder="Kuota" required>
        <select name="status" required>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
