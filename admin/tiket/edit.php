<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak');
}

if (!isset($_GET['id_tiket']) || !is_numeric($_GET['id_tiket'])) {
    flash_message('error', 'ID tiket tidak valid.');
    header('Location: index.php');
    exit;
}

$idTiket = (int)$_GET['id_tiket'];

$stmt = $conn->prepare("SELECT t.*, e.nama_event FROM tiket t JOIN event e ON t.id_event = e.id_event WHERE t.id_tiket = ?");
$stmt->execute([$idTiket]);
$tiket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tiket) {
    flash_message('error', 'Tiket tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Tiket';
$active_menu = 'tiket';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Edit Tiket</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=update" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id_tiket" value="<?php echo htmlspecialchars($tiket['id_tiket']); ?>">
        <div class="mb-3">
            <label class="form-label">Event</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($tiket['nama_event']); ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Tiket</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($tiket['nama_tiket']); ?>" disabled>
        </div>
        <div class="mb-3">
            <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="harga" name="harga" value="<?php echo htmlspecialchars($tiket['harga']); ?>" required min="1">
        </div>
        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kuota" name="kuota" value="<?php echo htmlspecialchars($tiket['kuota']); ?>" required min="1">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
