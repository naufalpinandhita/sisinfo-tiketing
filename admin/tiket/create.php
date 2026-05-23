<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$events = $conn->query("SELECT * FROM event ORDER BY nama_event ASC");
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket - Add</title>
</head>
<body>
    <h1>Tambah Tiket</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=insert" method="post">
        <select name="id_event" required>
            <option value="">-- Pilih Event --</option>
            <?php while ($e = $events->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($e['id_event']); ?>">
                    <?php echo htmlspecialchars($e['nama_event']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="nama_tiket" placeholder="Nama Tiket" required>
        <input type="number" name="harga" placeholder="Harga" required>
        <input type="number" name="kuota" placeholder="Kuota" required>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
