<?php
session_start();
require "../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id_voucher'])) {
    header('Location: index.php');
    exit();
}

$idVoucher = $_GET['id_voucher'];

$stmt = $conn->prepare("SELECT * FROM voucher WHERE id_voucher = ?");
$stmt->execute([$idVoucher]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voucher) {
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
    <title>Voucher - Edit</title>
</head>
<body>
    <h1>Edit Voucher</h1>
    <a href="index.php">Kembali ke List</a>

    <?php if ($success): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="action.php?act=update" method="post">
        <input type="hidden" name="id_voucher" value="<?php echo htmlspecialchars($voucher['id_voucher']); ?>">
        <input type="text" name="kode_voucher" placeholder="Kode Voucher" value="<?php echo htmlspecialchars($voucher['kode_voucher']); ?>" required>
        <input type="number" name="potongan" placeholder="Potongan (Rp)" value="<?php echo htmlspecialchars($voucher['potongan']); ?>" required>
        <input type="number" name="kuota" placeholder="Kuota" value="<?php echo htmlspecialchars($voucher['kuota']); ?>" required>
        <select name="status" required>
            <option value="aktif" <?php echo ($voucher['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
            <option value="nonaktif" <?php echo ($voucher['status'] == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
        </select>
        <button type="submit">Update</button>
    </form>
</body>
</html>
