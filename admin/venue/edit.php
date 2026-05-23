<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id_venue'])) {
    header('Location: index.php');
    exit();
}

$idVenue = $_GET['id_venue'];

$stmt = $conn->prepare("SELECT * FROM venue WHERE id_venue = ?");
$stmt->execute([$idVenue]);
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venue) {
    header('Location: index.php');
    exit();
}

$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venue - Edit</title>
</head>
<body>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=update" method="post">
        <input type="hidden" name="id_venue" value="<?php echo htmlspecialchars($venue['id_venue']); ?>">
        <input type="text" name="nama_venue" placeholder="Nama Venue" value="<?php echo htmlspecialchars($venue['nama_venue']); ?>" required>
        <input type="text" name="alamat" placeholder="Alamat" value="<?php echo htmlspecialchars($venue['alamat']); ?>" required>
        <input type="number" name="kapasitas" placeholder="Kapasitas" value="<?php echo htmlspecialchars($venue['kapasitas']); ?>" required>
        <button type="submit">Update</button>
    </form>
</body>
</html>