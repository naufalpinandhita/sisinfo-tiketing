<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { die('Akses ditolak'); }

$tab      = isset($_GET['tab']) && $_GET['tab'] === 'tiket' ? 'tiket' : 'transaksi';
$search   = isset($_GET['search'])    ? sanitize($_GET['search'])   : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from']          : '';
$dateTo   = isset($_GET['date_to'])   ? $_GET['date_to']            : '';
$export   = isset($_GET['export'])    ? $_GET['export']             : '';

// ── Helper: build query string for pagination links ──────────────────────────
function qStr(array $extra = []): string {
    $base = [];
    global $tab, $search, $dateFrom, $dateTo;
    if ($tab      !== 'transaksi') $base['tab']       = $tab;
    if ($search   !== '')          $base['search']    = $search;
    if ($dateFrom !== '')          $base['date_from'] = $dateFrom;
    if ($dateTo   !== '')          $base['date_to']   = $dateTo;
    return '?' . http_build_query(array_merge($base, $extra));
}

// ── Tab: Tiket Terjual per Event ──────────────────────────────────────────────
// Tugas 23 – query: menampilkan total tiket terjual per event
$tiketPerEvent = $conn->query("
    SELECT e.id_event, e.nama_event, e.tanggal,
           v.nama_venue,
           COUNT(DISTINCT t.id_tiket)               AS jenis_tiket,
           COALESCE(SUM(od.qty), 0)                  AS total_terjual,
           COALESCE(SUM(od.subtotal), 0)             AS total_pendapatan,
           (SELECT SUM(kuota) FROM tiket WHERE id_event = e.id_event) AS total_kuota
    FROM event e
    LEFT JOIN venue v      ON e.id_venue = v.id_venue
    LEFT JOIN tiket t      ON t.id_event = e.id_event
    LEFT JOIN order_detail od ON od.id_tiket = t.id_tiket
    LEFT JOIN orders o     ON od.id_order = o.id_order AND o.status = 'paid'
    GROUP BY e.id_event, e.nama_event, e.tanggal, v.nama_venue
    ORDER BY total_terjual DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── CSV Export: Tiket per Event ───────────────────────────────────────────────
if ($export === 'tiket') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tiket_terjual_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, ['No', 'Event', 'Tanggal', 'Venue', 'Jenis Tiket', 'Total Kuota', 'Terjual', 'Pendapatan']);
    $no = 1;
    foreach ($tiketPerEvent as $r) {
        fputcsv($out, [
            $no++,
            $r['nama_event'],
            $r['tanggal'],
            $r['nama_venue'],
            $r['jenis_tiket'],
            $r['total_kuota'],
            $r['total_terjual'],
            $r['total_pendapatan'],
        ]);
    }
    fclose($out);
    exit;
}

// ── Tab: Transaksi ────────────────────────────────────────────────────────────
$limit  = 15;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.nama LIKE ? OR e.nama_event LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($dateFrom !== '') { $where[] = "DATE(o.tanggal_order) >= ?"; $params[] = $dateFrom; }
if ($dateTo   !== '') { $where[] = "DATE(o.tanggal_order) <= ?"; $params[] = $dateTo; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// CSV export: transaksi
if ($export === 'transaksi') {
    $allStmt = $conn->prepare("
        SELECT o.id_order, o.tanggal_order, o.total, o.status,
               u.nama as nama_user, MIN(e.nama_event) as nama_event,
               COALESCE(SUM(od.qty), 0) as qty
        FROM orders o
        JOIN users u ON o.id_user = u.id_user
        LEFT JOIN order_detail od ON o.id_order = od.id_order
        LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
        LEFT JOIN event e ON t.id_event = e.id_event
        $whereSql
        GROUP BY o.id_order ORDER BY o.tanggal_order DESC
    ");
    $allStmt->execute($params);
    $allOrders = $allStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="transaksi_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['No', 'No. Order', 'Tanggal', 'Nama User', 'Event', 'Qty', 'Total', 'Status']);
    $no = 1;
    foreach ($allOrders as $r) {
        fputcsv($out, [
            $no++,
            'ORD-' . str_pad($r['id_order'], 5, '0', STR_PAD_LEFT),
            $r['tanggal_order'],
            $r['nama_user'],
            $r['nama_event'] ?: '-',
            $r['qty'],
            $r['total'],
            $r['status'],
        ]);
    }
    fclose($out);
    exit;
}

$countStmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id_order) FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    $whereSql
");
$countStmt->execute($params);
$total_rows  = (int)$countStmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $limit);
$page = max(1, min($page, max(1, $total_pages)));
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
$pi = 1;
foreach ($params as $p) { $stmt->bindValue($pi++, $p); }
$stmt->bindValue($pi++, $limit, PDO::PARAM_INT);
$stmt->bindValue($pi,   $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Laporan';
$active_menu = 'laporan';
include 'header.php';
include 'sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Laporan</h4>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'transaksi' ? 'active' : ''; ?>"
           href="<?php echo qStr(['tab' => 'transaksi']); ?>">
            <i class="bi bi-receipt me-1"></i>Data Transaksi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'tiket' ? 'active' : ''; ?>"
           href="<?php echo qStr(['tab' => 'tiket']); ?>">
            <i class="bi bi-ticket-perforated me-1"></i>Tiket Terjual per Event
        </a>
    </li>
</ul>

<?php if ($tab === 'transaksi'): ?>
<!-- ── Tab: Transaksi ─────────────────────────────────────────────────────── -->
<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="transaksi">
        <div class="col-md-3">
            <label class="form-label small text-muted">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">Cari</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="User atau event..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary-custom w-100">Cari</button>
        </div>
        <?php if ($search || $dateFrom || $dateTo): ?>
        <div class="col-md-1">
            <a href="laporan.php?tab=transaksi" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
        <?php endif; ?>
        <div class="col-md-2">
            <a href="<?php echo qStr(['export' => 'transaksi']); ?>" class="btn btn-outline-success w-100">
                <i class="bi bi-file-earmark-excel me-1"></i>Export CSV
            </a>
        </div>
    </form>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>No. Order</th>
                    <th>Tanggal</th>
                    <th>Nama User</th>
                    <th>Event</th>
                    <th class="text-center">Qty</th>
                    <th>Total Harga</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data transaksi.</td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($orders as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><code>ORD-<?php echo str_pad($row['id_order'], 5, '0', STR_PAD_LEFT); ?></code></td>
                    <td class="small"><?php echo date('d M Y H:i', strtotime($row['tanggal_order'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_user']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_event'] ?: '-'); ?></td>
                    <td class="text-center"><?php echo number_format($row['qty']); ?></td>
                    <td>Rp <?php echo number_format($row['total']); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo match($row['status']) {
                            'paid'    => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'cancel'  => 'bg-danger',
                            default   => 'bg-secondary'
                        }; ?>"><?php echo ucfirst($row['status']); ?></span>
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
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo qStr(['page' => 1]); ?>">&laquo;</a>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end   = min($total_pages, $page + 2);
        for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo qStr(['page' => $i]); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo qStr(['page' => $total_pages]); ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<!-- ── Tab: Tiket Terjual per Event (Tugas 23) ────────────────────────────── -->
<div class="d-flex justify-content-end mb-3">
    <a href="<?php echo qStr(['tab' => 'tiket', 'export' => 'tiket']); ?>" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export CSV
    </a>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Event</th>
                    <th>Tanggal</th>
                    <th>Venue</th>
                    <th class="text-center">Jenis Tiket</th>
                    <th class="text-center">Total Kuota</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-center">Sisa</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tiketPerEvent) === 0): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data event.</td></tr>
                <?php else: ?>
                <?php $no = 1; foreach ($tiketPerEvent as $row):
                    $sisa = $row['total_kuota'] - $row['total_terjual'];
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td class="fw-bold"><?php echo htmlspecialchars($row['nama_event']); ?></td>
                    <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_venue'] ?: '-'); ?></td>
                    <td class="text-center"><?php echo $row['jenis_tiket']; ?></td>
                    <td class="text-center"><?php echo number_format($row['total_kuota'] ?? 0); ?></td>
                    <td class="text-center fw-bold text-primary"><?php echo number_format($row['total_terjual']); ?></td>
                    <td class="text-center <?php echo $sisa < 10 ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($sisa); ?></td>
                    <td>Rp <?php echo number_format($row['total_pendapatan']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (count($tiketPerEvent) > 0):
                $grandTiket = array_sum(array_column($tiketPerEvent, 'total_terjual'));
                $grandRev   = array_sum(array_column($tiketPerEvent, 'total_pendapatan'));
            ?>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="6" class="text-end">Total:</td>
                    <td class="text-center"><?php echo number_format($grandTiket); ?></td>
                    <td></td>
                    <td>Rp <?php echo number_format($grandRev); ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
