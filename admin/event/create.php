<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$venues = $conn->query("SELECT * FROM venue ORDER BY nama_venue ASC");
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event - Add</title>
</head>
<body>
    <h1>Tambah Event</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=insert" method="post">
        <input type="text" name="nama_event" placeholder="Nama Event" required>
        <input type="date" name="tanggal" required>
        <select name="id_venue" required>
            <option value="">-- Pilih Venue --</option>
            <?php while ($v = $venues->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($v['id_venue']); ?>">
                    <?php echo htmlspecialchars($v['nama_venue']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
