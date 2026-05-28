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

if (!isset($_GET['id_event']) || !is_numeric($_GET['id_event'])) {
    flash_message('error', 'ID event tidak valid.');
    header('Location: index.php');
    exit;
}

$idEvent = (int)$_GET['id_event'];

$stmt = $conn->prepare("SELECT * FROM event WHERE id_event = ?");
$stmt->execute([$idEvent]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$venues = $conn->query("SELECT * FROM venue ORDER BY nama_venue ASC");
$minDate = date('Y-m-d');

$page_title = 'Edit Event';
$active_menu = 'event';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Edit Event</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-4 form-max-w">
    <form action="action.php?act=update" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id_event" value="<?php echo htmlspecialchars($event['id_event']); ?>">
        <div class="mb-3">
            <label for="nama_event" class="form-label">Nama Event <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_event" name="nama_event" value="<?php echo htmlspecialchars($event['nama_event']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="tanggal" class="form-label">Tanggal Event <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($event['tanggal']); ?>" min="<?php echo $minDate; ?>" required>
        </div>
        <div class="mb-3">
            <label for="jam" class="form-label">Jam Mulai</label>
            <input type="time" class="form-control" id="jam" name="jam" value="<?php echo htmlspecialchars($event['jam'] ?? ''); ?>">
            <div class="form-text">Opsional. Contoh: 19:00</div>
        </div>
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi Event</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($event['deskripsi'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="id_venue" class="form-label">Venue <span class="text-danger">*</span></label>
            <select class="form-select" id="id_venue" name="id_venue" required>
                <option value="">-- Pilih Venue --</option>
                <?php while ($v = $venues->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo htmlspecialchars($v['id_venue']); ?>" <?php echo ($v['id_venue'] == $event['id_venue']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($v['nama_venue']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Poster Saat Ini</label>
            <div>
                <?php if (!empty($event['poster_url'])): ?>
                <img src="../../assets/images/<?php echo htmlspecialchars($event['poster_url']); ?>" alt="Poster" class="img-thumbnail poster-preview">
                <?php else: ?>
                <span class="text-muted">Belum ada poster.</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="poster" class="form-label">Ganti Poster (opsional)</label>
            <input type="file" class="form-control" id="poster" name="poster" accept="image/*">
            <div class="form-text">Kosongkan jika tidak ingin mengganti poster.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i> Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
