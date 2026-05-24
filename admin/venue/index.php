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

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE nama_venue LIKE ?";
    $params[] = '%' . $search . '%';
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM venue $where");
$countStmt->execute($params);
$total_rows = (int)$countStmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $limit);
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM venue $where ORDER BY id_venue DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$paramIdx = 1;
foreach ($params as $p) { $stmt->bindValue($paramIdx++, $p); }
$stmt->bindValue($paramIdx++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIdx++, $offset, PDO::PARAM_INT);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Daftar Venue';
$active_menu = 'venue';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Daftar Venue</h4>
    <a href="create.php" class="btn btn-primary-custom"><i class="bi bi-plus-lg me-1"></i> Tambah Venue</a>
</div>

<?php echo show_flash(); ?>

<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama venue..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary-custom">Cari</button>
            </div>
        </div>
        <?php if ($search): ?>
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
                    <th>Nama Venue</th>
                    <th>Alamat</th>
                    <th>Kapasitas</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($venues) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data venue.</td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($venues as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_venue']); ?></td>
                    <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                    <td><?php echo number_format($row['kapasitas']); ?></td>
                    <td class="text-center">
                        <a href="edit.php?id_venue=<?php echo urlencode($row['id_venue']); ?>" class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil-square"></i></a>
                        <a href="action.php?act=delete&id_venue=<?php echo urlencode($row['id_venue']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus venue ini?')"><i class="bi bi-trash"></i></a>
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

<?php include '../footer.php'; ?>