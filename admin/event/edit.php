<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id_event'])) {
    header('Location: index.php');
    exit();
}

$idEvent = $_GET['id_event'];

$stmt = $conn->prepare("SELECT * FROM event WHERE id_event = ?");
$stmt->execute([$idEvent]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php');
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
    <title>Event - Edit</title>
</head>
<body>
    <h1>Edit Event</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=update" method="post">
        <input type="hidden" name="id_event" value="<?php echo htmlspecialchars($event['id_event']); ?>">
        <input type="text" name="nama_event" placeholder="Nama Event" value="<?php echo htmlspecialchars($event['nama_event']); ?>" required>
        <input type="date" name="tanggal" value="<?php echo htmlspecialchars($event['tanggal']); ?>" required>
        <select name="id_venue" required>
            <option value="">-- Pilih Venue --</option>
            <?php while ($v = $venues->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($v['id_venue']); ?>" <?php echo ($v['id_venue'] == $event['id_venue']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($v['nama_venue']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Update</button>
    </form>
</body>
</html>
