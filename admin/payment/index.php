<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { die('Akses ditolak'); }

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'waiting_confirmation';
$valid_status = ['waiting_confirmation', 'paid', 'rejected', 'all'];
if (!in_array($status_filter, $valid_status)) $status_filter = 'waiting_confirmation';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$where = [];
$params = [];
if ($status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}
if ($search !== '') {
    $where[] = "(u.nama LIKE ? OR o.invoice_code LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$limit  = 15;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM orders o
    JOIN users u ON o.id_user = u.id_user
    $whereSql
");
$countStmt->execute($params);
$total_rows  = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $limit));
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT o.id_order, o.invoice_code, o.total, o.status, o.tanggal_order,
           u.nama as nama_user,
           pc.created_at as upload_time, pc.id_confirmation,
           MIN(e.nama_event) as nama_event
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN payment_confirmation pc ON o.id_order = pc.id_order
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    $whereSql
    GROUP BY o.id_order, u.nama, pc.created_at, pc.id_confirmation
    ORDER BY
        CASE WHEN o.status = 'waiting_confirmation' THEN 0 ELSE 1 END,
        o.tanggal_order DESC
    LIMIT ? OFFSET ?
");
$pi = 1;
foreach ($params as $p) { $stmt->bindValue($pi++, $p); }
$stmt->bindValue($pi++, $limit, PDO::PARAM_INT);
$stmt->bindValue($pi,   $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count waiting for tab badge
$waitingCount = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status = 'waiting_confirmation'")->fetchColumn();

$page_title  = 'Verifikasi Pembayaran';
$active_menu = 'payment';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">
        Verifikasi Pembayaran
        <?php if ($waitingCount > 0): ?>
        <span class="badge bg-warning text-dark ms-2"><?php echo $waitingCount; ?> pending</span>
        <?php endif; ?>
    </h4>
</div>

<!-- Filter -->
<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted">Status</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="waiting_confirmation" <?php echo $status_filter === 'waiting_confirmation' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted">Cari</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Nama user atau invoice..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary-custom">Cari</button>
            </div>
        </div>
        <?php if ($search || $status_filter !== 'waiting_confirmation'): ?>
        <div class="col-md-2">
            <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="card card-clean">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Invoice</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Total</th>
                    <th class="text-center">Status</th>
                    <th>Waktu Upload</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data pembayaran.</td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($orders as $r): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><code><?php echo htmlspecialchars($r['invoice_code'] ?? '-'); ?></code></td>
                    <td><?php echo htmlspecialchars($r['nama_user']); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_event'] ?: '-'); ?></td>
                    <td>Rp <?php echo number_format($r['total']); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo match($r['status']) {
                            'paid'                  => 'bg-success',
                            'waiting_confirmation'  => 'bg-warning text-dark',
                            'rejected'              => 'bg-danger',
                            'pending_payment'       => 'bg-secondary',
                            'expired'               => 'bg-dark',
                            default                 => 'bg-secondary',
                        }; ?>"><?php echo match($r['status']) {
                            'paid'                  => 'Disetujui',
                            'waiting_confirmation'  => 'Menunggu',
                            'rejected'              => 'Ditolak',
                            'pending_payment'       => 'Belum Bayar',
                            'expired'               => 'Kadaluarsa',
                            default                 => ucfirst($r['status']),
                        }; ?></span>
                    </td>
                    <td><?php echo $r['upload_time'] ? date('d M Y H:i', strtotime($r['upload_time'])) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($r['status'] === 'waiting_confirmation'): ?>
                        <a href="detail.php?id=<?php echo $r['id_order']; ?>" class="btn btn-sm btn-primary-custom">
                            <i class="bi bi-eye me-1"></i> Review
                        </a>
                        <?php else: ?>
                        <a href="detail.php?id=<?php echo $r['id_order']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=1&status=<?php echo $status_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include '../footer.php'; ?>
