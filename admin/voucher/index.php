<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$query = "SELECT * FROM voucher ORDER BY id_voucher DESC";
$vouchers = $conn->query($query);
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Voucher</title>
</head>
<body>
    <h1>Daftar Voucher</h1>
    <a href="create.php">Tambah Voucher</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <table border='1'>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Voucher</th>
                <th>Potongan</th>
                <th>Kuota</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $vouchers->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['kode_voucher']); ?></td>
                <td><?php echo htmlspecialchars($row['potongan']); ?></td>
                <td><?php echo htmlspecialchars($row['kuota']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <a href="edit.php?id_voucher=<?php echo urlencode($row['id_voucher']); ?>">Edit</a>
                    <a href="action.php?act=delete&id_voucher=<?php echo urlencode($row['id_voucher']); ?>" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
