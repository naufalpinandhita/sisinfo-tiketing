<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$query = "SELECT e.*, v.nama_venue FROM event e JOIN venue v ON e.id_venue = v.id_venue ORDER BY e.id_event DESC";
$events = $conn->query($query);
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Event</title>
</head>
<body>
    <h1>Daftar Event</h1>
    <a href="create.php">Tambah Event</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <table border='1'>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Event</th>
                <th>Tanggal</th>
                <th>Venue</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $events->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
                <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_venue']); ?></td>
                <td>
                    <a href="edit.php?id_event=<?php echo urlencode($row['id_event']); ?>">Edit</a>
                    <a href="action.php?act=delete&id_event=<?php echo urlencode($row['id_event']); ?>" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
