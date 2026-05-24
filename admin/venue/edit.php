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

if (!isset($_GET['id_venue']) || !is_numeric($_GET['id_venue'])) {
    flash_message('error', 'ID venue tidak valid.');
    header('Location: index.php');
    exit;
}

$idVenue = (int)$_GET['id_venue'];

$stmt = $conn->prepare("SELECT * FROM venue WHERE id_venue = ?");
$stmt->execute([$idVenue]);
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venue) {
    flash_message('error', 'Venue tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Venue';
$active_menu = 'venue';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Edit Venue</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=update" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id_venue" value="<?php echo htmlspecialchars($venue['id_venue']); ?>">
        <div class="mb-3">
            <label for="nama_venue" class="form-label">Nama Venue <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_venue" name="nama_venue" value="<?php echo htmlspecialchars($venue['nama_venue']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="alamat" name="alamat" value="<?php echo htmlspecialchars($venue['alamat']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="kapasitas" class="form-label">Kapasitas <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kapasitas" name="kapasitas" value="<?php echo htmlspecialchars($venue['kapasitas']); ?>" required min="1">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>