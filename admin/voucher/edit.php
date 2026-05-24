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

if (!isset($_GET['id_voucher']) || !is_numeric($_GET['id_voucher'])) {
    flash_message('error', 'ID voucher tidak valid.');
    header('Location: index.php');
    exit;
}

$idVoucher = (int)$_GET['id_voucher'];

$stmt = $conn->prepare("SELECT * FROM voucher WHERE id_voucher = ?");
$stmt->execute([$idVoucher]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voucher) {
    flash_message('error', 'Voucher tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Voucher';
$active_menu = 'voucher';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Edit Voucher</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=update" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id_voucher" value="<?php echo htmlspecialchars($voucher['id_voucher']); ?>">
        <div class="mb-3">
            <label for="kode_voucher" class="form-label">Kode Voucher <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="kode_voucher" name="kode_voucher" value="<?php echo htmlspecialchars($voucher['kode_voucher']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="potongan" class="form-label">Potongan Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="potongan" name="potongan" value="<?php echo htmlspecialchars($voucher['potongan']); ?>" required min="1">
        </div>
        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kuota" name="kuota" value="<?php echo htmlspecialchars($voucher['kuota']); ?>" required min="1">
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" name="status" required>
                <option value="aktif" <?php echo ($voucher['status'] === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                <option value="nonaktif" <?php echo ($voucher['status'] === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
