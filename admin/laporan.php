<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo   = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.nama LIKE ? OR e.nama_event LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $where[] = "DATE(o.tanggal_order) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(o.tanggal_order) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM orders o
    JOIN users u ON o.id_user = u.id_user
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
           u.nama as nama_user,
           MIN(e.nama_event) as nama_event,
           COALESCE(SUM(od.qty), 0) as qty
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
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

$page_title = 'Laporan Transaksi';
$active_menu = 'laporan';
include 'header.php';
include 'sidebar.php';
?>

<h4 class="mb-4 fw-brand">Laporan Transaksi</h4>

<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted">Cari</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari user atau event..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
        </div>
        <?php if ($search || $dateFrom || $dateTo): ?>
        <div class="col-md-2">
            <a href="laporan.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Tanggal Order</th>
                    <th>Nama User</th>
                    <th>Event</th>
                    <th>Qty</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data transaksi.</td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($orders as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['tanggal_order']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_user']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_event'] ?: '-'); ?></td>
                    <td><?php echo number_format($row['qty']); ?></td>
                    <td>Rp <?php echo number_format($row['total']); ?></td>
                    <td>
                        <span class="badge <?php
                            echo match($row['status']) {
                                'paid' => 'bg-success',
                                'pending' => 'bg-warning text-dark',
                                'cancel' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        ?>"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">First</a>
        </li>
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">Previous</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">Next</a>
        </li>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">Last</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include 'footer.php'; ?>
