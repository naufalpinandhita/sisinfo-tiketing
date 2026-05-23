<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$result = $conn->query("SELECT * FROM venue ORDER BY id_venue DESC");
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Venue</title>
</head>
<body>
    <h1>Daftar Venue</h1>
    <a href="create.php">Tambah Venue</a>
    <table border='1'>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Venue</th>
                <th>Lokasi</th>
                <th>Kapasitas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_venue']);?></td>
                <td><?php echo htmlspecialchars($row['alamat']);?></td>
                <td><?php echo htmlspecialchars($row['kapasitas']);?></td>
                <td>
                    <a href="edit.php?id_venue=<?php echo urlencode($row['id_venue']); ?>">Edit</a>
                    <a href="action.php?act=delete&id_venue=<?php echo urlencode($row['id_venue']); ?>" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>