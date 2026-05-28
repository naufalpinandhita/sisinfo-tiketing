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

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

$valid_status = ['all', 'paid', 'pending', 'cancel'];
if (!in_array($status_filter, $valid_status)) $status_filter = 'all';

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = ["o.id_user = ?"];
$params = [$user_id];

if ($search !== '') {
    $where[] = "e.nama_event LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id_order) FROM orders o
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    $whereSql
");
$countStmt->execute($params);
$total_rows = (int)$countStmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $limit);
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT o.id_order, o.tanggal_order, o.total, o.status,
           MIN(e.nama_event) as nama_event,
           MIN(e.poster_url) as poster_url,
           COALESCE(SUM(od.qty), 0) as qty
    FROM orders o
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    $whereSql
    GROUP BY o.id_order
    ORDER BY o.tanggal_order DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$paramIdx = 1;
foreach ($params as $p) { $stmt->bindValue($paramIdx++, $p); }
$stmt->bindValue($paramIdx++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIdx++, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadgeClass($s) {
    return match($s) {
        'paid'   => 'paid',
        'pending'=> 'pending',
        'cancel' => 'cancel',
        default  => 'pending',
    };
}
function statusBadgeLabel($s) {
    return match($s) {
        'paid'   => 'Payment Success',
        'pending'=> 'Pending',
        'cancel' => 'Canceled',
        default  => ucfirst($s),
    };
}

$page_title  = 'Transaksi';
$active_menu = 'history';
include 'header.php';
?>

<div class="container">
    <h4 class="fw-bold mb-4">Transaksi</h4>

    <!-- Filter Row -->
    <div class="tx-filter-wrap">
        <form method="GET" class="d-contents" id="filterForm">
            <input type="text" name="search" id="searchInput" class="tx-search-input"
                   placeholder="Cari nama event" value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="tx-status-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"   <?php echo $status_filter === 'all'    ? 'selected' : ''; ?>>All Transaction</option>
                <option value="paid"  <?php echo $status_filter === 'paid'   ? 'selected' : ''; ?>>Payment Success</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="cancel" <?php echo $status_filter === 'cancel' ? 'selected' : ''; ?>>Canceled</option>
            </select>
            <button type="submit" class="btn btn-primary-custom px-3"><i class="bi bi-search"></i></button>
            <?php if ($search || $status_filter !== 'all'): ?>
            <a href="history.php" class="btn btn-outline-secondary px-3"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Invoice Cards -->
    <?php if (count($orders) === 0): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-receipt fs-1 mb-3 d-block"></i>
        <p>Belum ada transaksi<?php echo $search ? ' yang cocok dengan pencarian' : ''; ?>.</p>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $row): ?>
    <div class="invoice-label">invoice-<?php echo str_pad($row['id_order'], 6, '0', STR_PAD_LEFT); ?></div>
    <div class="invoice-card">
        <div class="ic-inner">
            <?php if (!empty($row['poster_url'])): ?>
            <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($row['poster_url']); ?>" class="ic-thumb" alt="">
            <?php else: ?>
            <div class="ic-thumb-ph"><i class="bi bi-image"></i></div>
            <?php endif; ?>
            <div class="ic-body">
                <div>
                    <div class="ic-title"><?php echo htmlspecialchars($row['nama_event'] ?: 'Event'); ?></div>
                    <span class="ic-status-badge <?php echo statusBadgeClass($row['status']); ?>">
                        <?php echo statusBadgeLabel($row['status']); ?>
                    </span>
                    <div class="ic-meta">
                        <span>Tanggal Transaksi</span><br>
                        <?php echo date('d F Y, H:i', strtotime($row['tanggal_order'])); ?> WIB
                    </div>
                </div>
                <div class="ic-footer">
                    <div>
                        <div class="text-muted text-xs">Total</div>
                        <div class="ic-price">Rp <?php echo number_format($row['total']); ?></div>
                    </div>
                    <a href="order_confirm.php?id=<?php echo urlencode($row['id_order']); ?>" class="btn-detail">Lihat Detail</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
