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

$page_title = 'Tambah Voucher';
$active_menu = 'voucher';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Tambah Voucher</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=insert" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3">
            <label for="kode_voucher" class="form-label">Kode Voucher <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="kode_voucher" name="kode_voucher" placeholder="Masukkan kode voucher" required>
        </div>
        <div class="mb-3">
            <label for="potongan" class="form-label">Potongan Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="potongan" name="potongan" placeholder="Masukkan potongan" required min="1">
        </div>
        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kuota" name="kuota" placeholder="Masukkan kuota" required min="1">
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" name="status" required>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
