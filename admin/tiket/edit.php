<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id_tiket'])) {
    header('Location: index.php');
    exit();
}

$idTiket = $_GET['id_tiket'];

$stmt = $conn->prepare("SELECT * FROM tiket WHERE id_tiket = ?");
$stmt->execute([$idTiket]);
$tiket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tiket) {
    header('Location: index.php');
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
    <title>Tiket - Edit</title>
</head>
<body>
    <h1>Edit Tiket</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=update" method="post">
        <input type="hidden" name="id_tiket" value="<?php echo htmlspecialchars($tiket['id_tiket']); ?>">
        <select name="id_event" required>
            <option value="">-- Pilih Event --</option>
            <?php while ($e = $events->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($e['id_event']); ?>" <?php echo ($e['id_event'] == $tiket['id_event']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($e['nama_event']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="nama_tiket" placeholder="Nama Tiket" value="<?php echo htmlspecialchars($tiket['nama_tiket']); ?>" required>
        <input type="number" name="harga" placeholder="Harga" value="<?php echo htmlspecialchars($tiket['harga']); ?>" required>
        <input type="number" name="kuota" placeholder="Kuota" value="<?php echo htmlspecialchars($tiket['kuota']); ?>" required>
        <button type="submit">Update</button>
    </form>
</body>
</html>
