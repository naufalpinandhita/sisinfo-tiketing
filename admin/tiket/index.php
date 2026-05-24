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

$events = $conn->query("SELECT id_event, nama_event FROM event ORDER BY nama_event ASC")->fetchAll(PDO::FETCH_ASSOC);

$filterEvent = isset($_GET['id_event']) && is_numeric($_GET['id_event']) ? (int)$_GET['id_event'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($filterEvent > 0) {
    $where = "WHERE t.id_event = ?";
    $params[] = $filterEvent;
}
if ($search !== '') {
    $where = $where ? $where . " AND t.nama_tiket LIKE ?" : "WHERE t.nama_tiket LIKE ?";
    $params[] = '%' . $search . '%';
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM tiket t $where");
$countStmt->execute($params);
$total_rows = (int)$countStmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $limit);
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

$sql = "SELECT t.*, e.nama_event,
        COALESCE((SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0) as terjual
        FROM tiket t JOIN event e ON t.id_event = e.id_event
        $where ORDER BY t.id_tiket DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$paramIdx = 1;
foreach ($params as $p) { $stmt->bindValue($paramIdx++, $p); }
$stmt->bindValue($paramIdx++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIdx++, $offset, PDO::PARAM_INT);
$stmt->execute();
$tikets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Daftar Tiket';
$active_menu = 'tiket';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Daftar Tiket</h4>
    <a href="create.php" class="btn btn-primary-custom"><i class="bi bi-plus-lg me-1"></i> Tambah Tiket</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted">Filter Event</label>
            <select name="id_event" class="form-select" onchange="this.form.submit()">
                <option value="0">-- Semua Event --</option>
                <?php foreach ($events as $ev): ?>
                <option value="<?php echo $ev['id_event']; ?>" <?php echo $filterEvent == $ev['id_event'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ev['nama_event']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted">Cari</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama tiket..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary-custom">Cari</button>
            </div>
        </div>
        <?php if ($search || $filterEvent > 0): ?>
        <div class="col-md-2">
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Nama Tiket</th>
                    <th>Event</th>
                    <th>Harga</th>
                    <th>Kuota</th>
                    <th>Terjual</th>
                    <th>Sisa</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tikets) === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data tiket.</td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($tikets as $row):
                    $sisa = $row['kuota'] - (int)$row['terjual'];
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_tiket']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
                    <td>Rp <?php echo number_format($row['harga']); ?></td>
                    <td><?php echo number_format($row['kuota']); ?></td>
                    <td><?php echo number_format($row['terjual']); ?></td>
                    <td>
                        <span class="badge <?php echo $sisa > 0 ? 'bg-success' : 'bg-danger'; ?>"><?php echo number_format($sisa); ?></span>
                    </td>
                    <td class="text-center">
                        <a href="edit.php?id_tiket=<?php echo urlencode($row['id_tiket']); ?>" class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil-square"></i></a>
                        <a href="action.php?act=delete&id_tiket=<?php echo urlencode($row['id_tiket']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus tiket ini?')"><i class="bi bi-trash"></i></a>
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
            <a class="page-link" href="?page=1<?php echo $filterEvent ? '&id_event=' . $filterEvent : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">First</a>
        </li>
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filterEvent ? '&id_event=' . $filterEvent : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filterEvent ? '&id_event=' . $filterEvent : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filterEvent ? '&id_event=' . $filterEvent : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
        </li>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $filterEvent ? '&id_event=' . $filterEvent : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Last</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include '../footer.php'; ?>
