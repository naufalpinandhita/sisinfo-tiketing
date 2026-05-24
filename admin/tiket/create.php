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

$events = $conn->query("SELECT * FROM event ORDER BY nama_event ASC");

$page_title = 'Tambah Tiket';
$active_menu = 'tiket';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Tambah Tiket</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=insert" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3">
            <label for="id_event" class="form-label">Pilih Event <span class="text-danger">*</span></label>
            <select class="form-select" id="id_event" name="id_event" required>
                <option value="">-- Pilih Event --</option>
                <?php while ($e = $events->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($e['id_event']); ?>">
                    <?php echo htmlspecialchars($e['nama_event']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="nama_tiket" class="form-label">Nama Tiket <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_tiket" name="nama_tiket" placeholder="Contoh: VIP, Regular" required>
        </div>
        <div class="mb-3">
            <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="harga" name="harga" placeholder="Masukkan harga" required min="1">
        </div>
        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="kuota" name="kuota" placeholder="Masukkan kuota" required min="1">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
