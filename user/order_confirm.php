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

$id_order = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*, MIN(e.nama_event) as nama_event, MIN(e.tanggal) as tanggal, MIN(v.nama_venue) as nama_venue, vo.kode_voucher, vo.potongan
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    LEFT JOIN venue v ON e.id_venue = v.id_venue
    LEFT JOIN voucher vo ON o.id_voucher = vo.id_voucher
    WHERE o.id_order = ? AND o.id_user = ?
    GROUP BY o.id_order, vo.kode_voucher, vo.potongan
");
$stmt->execute([$id_order, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_message('error', 'Order tidak ditemukan.');
    header('Location: history.php');
    exit;
}

$stmtItems = $conn->prepare("
    SELECT od.*, t.nama_tiket, t.harga
    FROM order_detail od
    JOIN tiket t ON od.id_tiket = t.id_tiket
    WHERE od.id_order = ?
");
$stmtItems->execute([$id_order]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

$stmtCodes = $conn->prepare("
    SELECT a.*, t.nama_tiket
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN tiket t ON od.id_tiket = t.id_tiket
    WHERE od.id_order = ?
    ORDER BY t.nama_tiket, a.id_attendee
");
$stmtCodes->execute([$id_order]);
$codes = $stmtCodes->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Konfirmasi Order';
$active_menu = 'history';
include 'header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h4 class="fw-brand mb-4"><i class="bi bi-check-circle-fill text-success me-2"></i>Pemesanan Berhasil</h4>

            <div class="card card-clean mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">Nomor Order</div>
                            <div class="fw-bold">#<?php echo $order['id_order']; ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Tanggal Order</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($order['tanggal_order']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Event</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($order['nama_event']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Status</div>
                            <div class="badge bg-success">Paid</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-clean mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Detail Tiket</h6>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Tiket</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nama_tiket']); ?></td>
                                <td class="text-center"><?php echo number_format($item['qty']); ?></td>
                                <td class="text-end">Rp <?php echo number_format($item['harga']); ?></td>
                                <td class="text-end">Rp <?php echo number_format($item['subtotal']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($order['potongan'])): ?>
                    <div class="d-flex justify-content-between mt-2 text-success">
                        <span>Diskon Voucher (<?php echo htmlspecialchars($order['kode_voucher']); ?>)</span>
                        <span class="fw-bold">- Rp <?php echo number_format($order['potongan']); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total Pembayaran</span>
                        <span class="fw-bold fs-5 text-brand">Rp <?php echo number_format($order['total']); ?></span>
                    </div>
                </div>
            </div>

            <div class="card card-clean mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Kode Tiket & QR Code</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr><th>No</th><th>Tiket</th><th>Kode</th><th>QR</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($codes as $c): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($c['nama_tiket']); ?></td>
                                    <td><code><?php echo htmlspecialchars($c['kode_tiket']); ?></code></td>
                                    <td><div class="qr-small" id="qr-<?php echo $c['id_attendee']; ?>"></div></td>
                                    <td>
                                        <span class="badge <?php echo $c['status_checkin'] === 'sudah' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo $c['status_checkin'] === 'sudah' ? 'Sudah Check-in' : 'Belum Check-in'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="home.php" class="btn btn-outline-secondary"><i class="bi bi-house me-1"></i> Kembali ke Home</a>
                <a href="history.php" class="btn btn-primary-custom"><i class="bi bi-clock-history me-1"></i> Lihat Riwayat</a>
            </div>
        </div>
    </div>
</div>

<script>
<?php foreach ($codes as $c): ?>
new QRCode(document.getElementById('qr-<?php echo $c['id_attendee']; ?>'), {
    text: '<?php echo $c['kode_tiket']; ?>',
    width: 64,
    height: 64,
    correctLevel: QRCode.CorrectLevel.H
});
<?php endforeach; ?>
</script>

<?php include 'footer.php'; ?>
