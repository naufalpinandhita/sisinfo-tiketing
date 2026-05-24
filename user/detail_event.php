<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['role'] !== 'user') {
    die('Akses ditolak');
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: home.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT e.*, v.nama_venue, v.alamat
    FROM event e
    JOIN venue v ON e.id_venue = v.id_venue
    WHERE e.id_event = ?
");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: home.php');
    exit;
}

$stmtTiket = $conn->prepare("
    SELECT t.*,
        (t.kuota - COALESCE(
            (SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0
        )) as sisa
    FROM tiket t
    WHERE t.id_event = ?
    ORDER BY t.harga ASC
");
$stmtTiket->execute([$id]);
$tikets = $stmtTiket->fetchAll(PDO::FETCH_ASSOC);

$page_title = htmlspecialchars($event['nama_event']);
$active_menu = 'home';
include 'header.php';
?>

<div class="container">
    <div class="row g-4">
        <div class="col-lg-5">
            <?php if (!empty($event['poster_url'])): ?>
            <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($event['poster_url']); ?>" alt="<?php echo htmlspecialchars($event['nama_event']); ?>" class="detail-poster">
            <?php else: ?>
            <div class="detail-poster bg-secondary d-flex align-items-center justify-content-center">
                <i class="bi bi-image text-white fs-1"></i>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <h3 class="fw-brand mb-3"><?php echo htmlspecialchars($event['nama_event']); ?></h3>
            <div class="mb-2"><i class="bi bi-calendar-event text-brand me-2"></i> <?php echo htmlspecialchars($event['tanggal']); ?></div>
            <div class="mb-2"><i class="bi bi-geo-alt text-brand me-2"></i> <?php echo htmlspecialchars($event['nama_venue']); ?></div>
            <div class="mb-2"><i class="bi bi-map text-brand me-2"></i> <?php echo htmlspecialchars($event['alamat']); ?></div>
            <hr>
            <h5 class="fw-brand mb-3">Pilih Tiket</h5>
            <?php if (count($tikets) === 0): ?>
            <div class="alert alert-info">Tiket untuk event ini belum tersedia.</div>
            <?php else: ?>
            <form action="checkout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id_event" value="<?php echo $id; ?>">

                <?php foreach ($tikets as $t): ?>
                <div class="ticket-row">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($t['nama_tiket']); ?></div>
                            <div class="text-muted small">Rp <?php echo number_format($t['harga']); ?> &bull; Tersisa <?php echo number_format($t['sisa']); ?> tiket</div>
                        </div>
                        <div class="text-end">
                            <?php if ($t['sisa'] > 0): ?>
                            <input type="number" name="qty[<?php echo $t['id_tiket']; ?>]" class="form-control form-control-sm qty-input" min="0" max="<?php echo $t['sisa']; ?>" value="0">
                            <?php else: ?>
                            <span class="badge bg-danger">Habis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="home.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-cart me-1"></i> Pesan Sekarang</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
