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

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = ["o.id_user = ?"];
$params = [$user_id];

if ($search !== '') {
    $where[] = "e.nama_event LIKE ?";
    $params[] = '%' . $search . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM orders o
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

$page_title = 'Riwayat Pembelian';
$active_menu = 'history';
include 'header.php';
?>

<div class="container">
    <h4 class="fw-brand mb-4">Riwayat Pembelian</h4>

    <div class="card card-clean p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari event..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <button type="submit" class="btn btn-primary-custom w-100">Cari</button>
            </div>
            <?php if ($search): ?>
            <div class="col-md-3 col-lg-2">
                <a href="history.php" class="btn btn-outline-secondary w-100">Reset</a>
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
                        <th>Tanggal</th>
                        <th>Event</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat pembelian.</td></tr>
                    <?php else: ?>
                    <?php $no = $offset + 1; foreach ($orders as $row): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_order']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_event'] ?: '-'); ?></td>
                        <td class="text-center"><?php echo number_format($row['qty']); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['total']); ?></td>
                        <td class="text-center">
                            <span class="badge <?php
                                echo match($row['status']) {
                                    'paid' => 'bg-success',
                                    'pending' => 'bg-warning text-dark',
                                    'cancel' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span>
                        </td>
                        <td class="text-center">
                            <a href="order_confirm.php?id=<?php echo urlencode($row['id_order']); ?>" class="btn btn-sm btn-primary-custom">
                                <i class="bi bi-eye"></i> Detail
                            </a>
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
                <a class="page-link" href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">First</a>
            </li>
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
            </li>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Last</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
