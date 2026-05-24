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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="action.php?act=update" method="post" class="card card-clean p-4">
        <input type="hidden" name="id_venue" value="<?php echo htmlspecialchars($venue['id_venue']); ?>">
        <div class="mb-3">
            <label class="form-label">Nama Venue</label>
            <input type="text" class="form-control" name="nama_venue" placeholder="Nama Venue" value="<?php echo htmlspecialchars($venue['nama_venue']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <input type="text" class="form-control" name="alamat" placeholder="Alamat" value="<?php echo htmlspecialchars($venue['alamat']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Kapasitas</label>
            <input type="number" class="form-control" name="kapasitas" placeholder="Kapasitas" value="<?php echo htmlspecialchars($venue['kapasitas']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary-custom">Update</button>
    </form>
    </div>
</body>
</html>