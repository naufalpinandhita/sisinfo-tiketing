<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'user')  { die('Akses ditolak'); }

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) { flash_message('error', 'Event tidak ditemukan.'); header('Location: home.php'); exit; }

$stmt = $conn->prepare("
    SELECT e.*, v.nama_venue, v.alamat
    FROM event e JOIN venue v ON e.id_venue = v.id_venue
    WHERE e.id_event = ?
");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) { flash_message('error', 'Event tidak ditemukan.'); header('Location: home.php'); exit; }

$stmtTiket = $conn->prepare("
    SELECT t.*,
        (t.kuota - COALESCE((SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0)) as sisa
    FROM tiket t WHERE t.id_event = ? ORDER BY t.harga ASC
");
$stmtTiket->execute([$id]);
$tikets = $stmtTiket->fetchAll(PDO::FETCH_ASSOC);

$harga_min = count($tikets) > 0 ? $tikets[0]['harga'] : 0;
$tersedia  = count(array_filter($tikets, fn($t) => $t['sisa'] > 0));
$deskripsi = $event['deskripsi'] ?? 'Event seru yang sayang untuk dilewatkan. Dapatkan pengalaman tak terlupakan bersama artis dan performer terbaik.';
$jam       = !empty($event['jam']) ? date('H:i', strtotime($event['jam'])) . ' WIB' : null;

$page_title  = htmlspecialchars($event['nama_event']);
$active_menu = 'home';
include 'header.php';
?>

<div class="container pb-5">
    <!-- Back link (mobile) -->
    <div class="d-lg-none mb-3">
        <a href="home.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="event-detail-layout">
        <!-- LEFT: Image + Thumbnails + Description -->
        <div>
            <?php if (!empty($event['poster_url'])): ?>
            <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($event['poster_url']); ?>"
                 class="event-main-image" id="mainImg" alt="<?php echo htmlspecialchars($event['nama_event']); ?>">
            <div class="event-thumbnails">
                <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($event['poster_url']); ?>"
                     class="active" onclick="document.getElementById('mainImg').src=this.src; document.querySelectorAll('.event-thumbnails img').forEach(i=>i.classList.remove('active')); this.classList.add('active');"
                     alt="Poster">
            </div>
            <?php else: ?>
            <div class="event-hero bg-secondary d-flex align-items-center justify-content-center mb-3 rounded">
                <i class="bi bi-image text-white fs-1"></i>
            </div>
            <?php endif; ?>

            <!-- Description (desktop) -->
            <div class="mt-4 d-none d-lg-block">
                <h6 class="fw-bold mb-2">Deskripsi</h6>
                <div class="deskripsi-text" id="descText"><?php echo nl2br(htmlspecialchars($deskripsi)); ?></div>
                <button class="toggle-desc mt-2" onclick="var t=document.getElementById('descText'); t.classList.toggle('expanded'); this.textContent=t.classList.contains('expanded')?'Tampilkan Lebih Sedikit':'Tampilkan Lebih Banyak';">Tampilkan Lebih Banyak</button>
            </div>
        </div>

        <!-- RIGHT: Sidebar -->
        <div class="event-sidebar mt-3 mt-lg-0">
            <div class="es-title"><?php echo htmlspecialchars($event['nama_event']); ?></div>

            <div class="info-row">
                <i class="bi bi-calendar-event"></i>
                <div>
                    <div class="info-label">Tanggal</div>
                    <div class="info-value"><?php echo date('d F Y', strtotime($event['tanggal'])); ?></div>
                    <?php if ($jam): ?><div class="info-value"><?php echo $jam; ?></div><?php endif; ?>
                </div>
            </div>

            <div class="info-row">
                <i class="bi bi-geo-alt"></i>
                <div>
                    <div class="info-label">Lokasi</div>
                    <div class="info-value"><?php echo htmlspecialchars($event['nama_venue']); ?></div>
                    <div class="info-value text-muted fw-normal small"><?php echo htmlspecialchars($event['alamat']); ?></div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($event['nama_venue'] . ' ' . $event['alamat']); ?>"
                       target="_blank" class="maps-link mt-1 d-inline-block">Petunjuk Arah</a>
                </div>
            </div>

            <?php if ($tersedia > 0): ?>
            <div class="price-from-label">Mulai Dari</div>
            <div class="price-from-value">Rp <?php echo number_format($harga_min); ?></div>
            <a href="select_ticket.php?id=<?php echo $id; ?>" class="btn-beli">Beli Sekarang</a>
            <?php else: ?>
            <div class="price-from-label">Status</div>
            <div class="mb-3"><span class="badge bg-danger">Tiket Habis</span></div>
            <button class="btn-beli" disabled>Tiket Habis</button>
            <?php endif; ?>

            <!-- Ticket availability summary -->
            <div class="mt-3">
                <div class="text-muted small mb-2">Kategori Tiket</div>
                <?php foreach ($tikets as $t): ?>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($t['nama_tiket']); ?></div>
                        <div class="text-muted">Rp <?php echo number_format($t['harga']); ?></div>
                    </div>
                    <?php if ($t['sisa'] > 0): ?>
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i><?php echo number_format($t['sisa']); ?></span>
                    <?php else: ?>
                    <span class="badge-sold">Habis</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Description (mobile) -->
    <div class="mt-4 d-lg-none">
        <h6 class="fw-bold mb-2">Deskripsi</h6>
        <div class="deskripsi-text" id="descTextMobile"><?php echo nl2br(htmlspecialchars($deskripsi)); ?></div>
        <button class="toggle-desc mt-2" onclick="var t=document.getElementById('descTextMobile'); t.classList.toggle('expanded'); this.textContent=t.classList.contains('expanded')?'Tampilkan Lebih Sedikit':'Tampilkan Lebih Banyak';">Tampilkan Lebih Banyak</button>
    </div>

    <!-- Mobile sticky CTA -->
    <div class="d-none-desktop sticky-spacer"></div>
    <?php if ($tersedia > 0): ?>
    <div class="sticky-cta d-lg-none">
        <div class="cta-info">
            <div class="price-label">Mulai Dari</div>
            <div class="price-value">Rp <?php echo number_format($harga_min); ?></div>
        </div>
        <a href="select_ticket.php?id=<?php echo $id; ?>" class="btn-beli">Beli Sekarang</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
