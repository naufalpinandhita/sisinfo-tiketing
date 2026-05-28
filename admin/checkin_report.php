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

$id_event = isset($_GET['id_event']) && is_numeric($_GET['id_event']) ? (int)$_GET['id_event'] : 0;

$events = $conn->query("SELECT id_event, nama_event FROM event ORDER BY nama_event ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT a.id_attendee, a.kode_tiket, a.status_checkin, a.waktu_checkin,
           u.nama as nama_user, e.nama_event, t.nama_tiket
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN users u ON o.id_user = u.id_user
    JOIN tiket t ON od.id_tiket = t.id_tiket
    JOIN event e ON t.id_event = e.id_event
";
$params = [];
if ($id_event > 0) {
    $sql .= " WHERE e.id_event = ?";
    $params[] = $id_event;
}
$sql .= " ORDER BY a.waktu_checkin DESC, a.id_attendee DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_checkin = 0;
$total_belum = 0;
foreach ($rows as $r) {
    if ($r['status_checkin'] === 'sudah') $total_checkin++;
    else $total_belum++;
}

$page_title = 'Laporan Check-in';
$active_menu = 'checkin_report';
include 'header.php';
include 'sidebar.php';
?>

<h4 class="mb-4 fw-brand">Laporan Check-in</h4>

<div class="card card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted">Filter Event</label>
            <select name="id_event" class="form-select">
                <option value="0">Semua Event</option>
                <?php foreach ($events as $ev): ?>
                <option value="<?php echo $ev['id_event']; ?>" <?php echo $id_event == $ev['id_event'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ev['nama_event']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
        </div>
        <?php if ($id_event > 0): ?>
        <div class="col-md-2">
            <a href="checkin_report.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-clean p-3 text-center">
            <div class="text-muted small">Total Tiket</div>
            <div class="fs-4 fw-bold"><?php echo count($rows); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-clean p-3 text-center">
            <div class="text-muted small">Sudah Check-in</div>
            <div class="fs-4 fw-bold text-success"><?php echo $total_checkin; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-clean p-3 text-center">
            <div class="text-muted small">Belum Check-in</div>
            <div class="fs-4 fw-bold text-warning"><?php echo $total_belum; ?></div>
        </div>
    </div>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Kode Tiket</th>
                    <th>Event</th>
                    <th>Tiket</th>
                    <th>Pemesan</th>
                    <th class="text-center">Status</th>
                    <th>Waktu Check-in</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data check-in.</td></tr>
                <?php else: ?>
                <?php $no = 1; foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><code><?php echo htmlspecialchars($r['kode_tiket']); ?></code></td>
                    <td><?php echo htmlspecialchars($r['nama_event']); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_tiket']); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_user']); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $r['status_checkin'] === 'sudah' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <?php echo $r['status_checkin'] === 'sudah' ? 'Sudah' : 'Belum'; ?>
                        </span>
                    </td>
                    <td><?php echo $r['waktu_checkin'] ? htmlspecialchars($r['waktu_checkin']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
