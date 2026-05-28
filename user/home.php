<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'user')  { die('Akses ditolak'); }

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "
    SELECT e.*, v.nama_venue, v.alamat,
        (SELECT MIN(harga) FROM tiket WHERE id_event = e.id_event) as harga_min
    FROM event e
    JOIN venue v ON e.id_venue = v.id_venue
";
if ($search !== '') {
    $sql .= " WHERE e.nama_event LIKE ? OR v.nama_venue LIKE ?";
    $like = '%' . $search . '%';
}
$sql .= " ORDER BY e.tanggal ASC";

if ($search !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$like, $like]);
} else {
    $stmt = $conn->query($sql);
}
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Event';
$active_menu = 'home';
include 'header.php';
?>

<!-- Banner Carousel -->
<?php $banners = array_slice(array_filter($events, fn($e) => !empty($e['poster_url'])), 0, 5); ?>
<?php if (count($banners) > 0): ?>
<div id="bannerCarousel" class="carousel slide banner-carousel mb-0" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <?php foreach ($banners as $idx => $b): ?>
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?php echo $idx; ?>"
            class="<?php echo $idx === 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($banners as $idx => $b): ?>
        <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
            <a href="detail_event.php?id=<?php echo urlencode($b['id_event']); ?>">
                <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($b['poster_url']); ?>"
                     class="d-block w-100" alt="<?php echo htmlspecialchars($b['nama_event']); ?>">
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>
<?php endif; ?>

<!-- Mobile Search Bar -->
<div class="container d-lg-none mt-3 mb-1">
    <form method="GET" action="home.php">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Cari event..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary-custom"><i class="bi bi-search"></i></button>
            <?php if ($search): ?>
            <a href="home.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Event Grid -->
<div class="container">
    <?php if ($search): ?>
    <h5 class="section-heading">Hasil pencarian: "<?php echo htmlspecialchars($search); ?>"</h5>
    <?php else: ?>
    <h5 class="section-heading">Event</h5>
    <?php endif; ?>

    <?php if (count($events) === 0): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
        <p>Tidak ada event yang ditemukan.</p>
        <?php if ($search): ?><a href="home.php" class="btn btn-outline-secondary btn-sm">Lihat Semua Event</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="event-grid">
        <?php foreach ($events as $e): ?>
        <a href="detail_event.php?id=<?php echo urlencode($e['id_event']); ?>" class="event-grid-card">
            <?php if (!empty($e['poster_url'])): ?>
            <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($e['poster_url']); ?>"
                 class="eg-poster" alt="<?php echo htmlspecialchars($e['nama_event']); ?>">
            <?php else: ?>
            <div class="eg-poster-ph"><i class="bi bi-image"></i></div>
            <?php endif; ?>
            <div class="eg-body">
                <div class="eg-title"><?php echo htmlspecialchars($e['nama_event']); ?></div>
                <div class="eg-date"><?php echo date('d M Y', strtotime($e['tanggal'])); ?></div>
                <div class="eg-venue"><?php echo htmlspecialchars($e['nama_venue']); ?> | <?php echo htmlspecialchars($e['alamat'] ?? ''); ?></div>
                <div class="eg-price-from">Mulai Dari</div>
                <?php if (($e['harga_min'] ?? 0) == 0): ?>
                <div class="eg-price free">Gratis</div>
                <?php else: ?>
                <div class="eg-price">Rp <?php echo number_format($e['harga_min']); ?></div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
