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

$page_title = 'Tambah Venue';
$active_menu = 'venue';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Tambah Venue</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=insert" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3">
            <label for="nama_venue" class="form-label">Nama Venue <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_venue" name="nama_venue" placeholder="Masukkan nama venue" required>
        </div>
        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="alamat" name="alamat" placeholder="Masukkan alamat" required>
        </div>
        <div class="mb-3">
            <label for="kapasitas" class="form-label">Kapasitas <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kapasitas" name="kapasitas" placeholder="Masukkan kapasitas" required min="1">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>