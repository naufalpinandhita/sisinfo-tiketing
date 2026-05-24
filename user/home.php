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

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT e.*, v.nama_venue
        FROM event e
        JOIN venue v ON e.id_venue = v.id_venue
        WHERE e.nama_event LIKE ? OR v.nama_venue LIKE ?
        ORDER BY e.tanggal ASC
    ");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $conn->query("
        SELECT e.*, v.nama_venue
        FROM event e
        JOIN venue v ON e.id_venue = v.id_venue
        ORDER BY e.tanggal ASC
    ");
}
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Event';
$active_menu = 'home';
include 'header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-brand">Katalog Event</h4>
        <form method="GET" class="d-flex gap-2 search-form-w">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari event..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <?php if ($search): ?>
            <a href="home.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-4">
        <?php if (count($events) === 0): ?>
        <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
            <p>Tidak ada event yang tersedia.</p>
        </div>
        <?php else: ?>
        <?php foreach ($events as $e): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card event-card h-100">
                <?php if (!empty($e['poster_url'])): ?>
                <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($e['poster_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($e['nama_event']); ?>">
                <?php else: ?>
                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                    <i class="bi bi-image text-white fs-1"></i>
                </div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-brand"><?php echo htmlspecialchars($e['nama_event']); ?></h5>
                    <div class="event-date mb-1"><i class="bi bi-calendar-event me-1"></i> <?php echo htmlspecialchars($e['tanggal']); ?></div>
                    <div class="event-venue mb-3"><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($e['nama_venue']); ?></div>
                    <div class="mt-auto">
                        <a href="detail_event.php?id=<?php echo urlencode($e['id_event']); ?>" class="btn btn-primary-custom w-100">Lihat Detail</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
