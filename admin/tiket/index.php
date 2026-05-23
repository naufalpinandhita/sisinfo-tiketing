<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$query = "SELECT t.*, e.nama_event FROM tiket t JOIN event e ON t.id_event = e.id_event ORDER BY t.id_tiket DESC";
$tikets = $conn->query($query);
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tiket</title>
</head>
<body>
    <h1>Daftar Tiket</h1>
    <a href="create.php">Tambah Tiket</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <table border='1'>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Tiket</th>
                <th>Event</th>
                <th>Harga</th>
                <th>Kuota</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $tikets->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_tiket']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
                <td><?php echo htmlspecialchars($row['harga']); ?></td>
                <td><?php echo htmlspecialchars($row['kuota']); ?></td>
                <td>
                    <a href="edit.php?id_tiket=<?php echo urlencode($row['id_tiket']); ?>">Edit</a>
                    <a href="action.php?act=delete&id_tiket=<?php echo urlencode($row['id_tiket']); ?>" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
