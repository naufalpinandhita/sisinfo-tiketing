<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'user')  { die('Akses ditolak'); }

$id_order = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id  = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*, u.nama as nama_user, u.email as email_user,
           MIN(e.nama_event) as nama_event, MIN(e.tanggal) as tanggal_event,
           MIN(e.poster_url) as poster_url, MIN(v.nama_venue) as nama_venue,
           MIN(v.alamat) as alamat_venue, MIN(e.id_event) as id_event,
           vo.kode_voucher, vo.potongan,
           COALESCE(SUM(od.qty), 0) as total_tiket
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    LEFT JOIN venue v ON e.id_venue = v.id_venue
    LEFT JOIN voucher vo ON o.id_voucher = vo.id_voucher
    WHERE o.id_order = ? AND o.id_user = ?
    GROUP BY o.id_order, u.nama, u.email, vo.kode_voucher, vo.potongan
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
    FROM order_detail od JOIN tiket t ON od.id_tiket = t.id_tiket
    WHERE od.id_order = ?
");
$stmtItems->execute([$id_order]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
$subtotal = array_sum(array_column($items, 'subtotal'));

// Attendee codes - graceful fallback if nama_attendee column absent
try {
    $stmtCodes = $conn->prepare("
        SELECT a.id_attendee, a.kode_tiket, a.status_checkin,
               COALESCE(a.nama_attendee, '') as nama_attendee,
               t.nama_tiket
        FROM attendee a
        JOIN order_detail od ON a.id_detail = od.id_detail
        JOIN tiket t ON od.id_tiket = t.id_tiket
        WHERE od.id_order = ?
        ORDER BY t.nama_tiket, a.id_attendee
    ");
    $stmtCodes->execute([$id_order]);
    $codes = $stmtCodes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stmtCodes2 = $conn->prepare("
        SELECT a.id_attendee, a.kode_tiket, a.status_checkin, '' as nama_attendee, t.nama_tiket
        FROM attendee a
        JOIN order_detail od ON a.id_detail = od.id_detail
        JOIN tiket t ON od.id_tiket = t.id_tiket
        WHERE od.id_order = ?
        ORDER BY t.nama_tiket, a.id_attendee
    ");
    $stmtCodes2->execute([$id_order]);
    $codes = $stmtCodes2->fetchAll(PDO::FETCH_ASSOC);
}

function txStatusBadge($s) {
    return match($s) {
        'paid'                  => ['d1fae5','065f46','Payment Success'],
        'waiting_confirmation'  => ['fef3c7','92400e','Menunggu Verifikasi'],
        'pending_payment'       => ['fef3c7','92400e','Menunggu Pembayaran'],
        'rejected'              => ['fee2e2','991b1b','Pembayaran Ditolak'],
        'expired'               => ['fee2e2','991b1b','Kadaluarsa'],
        'pending'               => ['fef3c7','92400e','Pending'],
        'cancel'                => ['fee2e2','991b1b','Canceled'],
        default                 => ['e5e7eb','374151', ucfirst($s)],
    };
}
[$bgc, $clr, $lbl] = txStatusBadge($order['status']);
$invoice_no = $order['invoice_code'] ?? ('INV-' . str_pad($order['id_order'], 6, '0', STR_PAD_LEFT));

$page_title  = 'Detail Transaksi';
$active_menu = 'history';
include 'header.php';
?>

<div class="container pb-5">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="history.php" class="text-muted"><i class="bi bi-arrow-left fs-5"></i></a>
            <h5 class="fw-bold mb-0">Detail Transaksi</h5>
        </div>
        <?php if (count($codes) > 0): ?>
        <a href="tickets.php" class="btn-detail">
            <i class="bi bi-qr-code me-1"></i>Lihat E-Tiket
        </a>
        <?php endif; ?>
    </div>

    <div class="tx-detail-layout">
        <!-- LEFT: Event Card -->
        <div>
            <div class="tx-event-card">
                <?php if (!empty($order['poster_url'])): ?>
                <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($order['poster_url']); ?>" alt="">
                <?php else: ?>
                <div class="tx-poster-ph">
                    <i class="bi bi-image text-muted fs-1"></i>
                </div>
                <?php endif; ?>
                <div class="txc-body">
                    <div class="txc-title"><?php echo htmlspecialchars($order['nama_event']); ?></div>
                    <div class="txc-date"><i class="bi bi-calendar-event me-1"></i><?php echo date('d F Y', strtotime($order['tanggal_event'])); ?></div>
                    <div class="txc-venue"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($order['nama_venue']); ?></div>
                    <a href="detail_event.php?id=<?php echo $order['id_event']; ?>" class="txc-link">Lihat Detail Acara &rsaquo;</a>
                </div>
            </div>
        </div>

        <!-- RIGHT: Detail Sections -->
        <div>
            <!-- Detail Pesanan -->
            <div class="tx-section">
                <h6>Detail Pesanan</h6>
                <div class="tx-detail-row">
                    <span class="tdr-label">No. Invoice</span>
                    <span class="tdr-value font-monospace"><?php echo $invoice_no; ?></span>
                </div>
                <div class="tx-detail-row">
                    <span class="tdr-label">Status</span>
                    <span class="tdr-value">
                        <span class="ic-status-badge <?php echo $order['status'] === 'paid' ? 'paid' : ($order['status'] === 'cancel' ? 'cancel' : 'pending'); ?>"><?php echo $lbl; ?></span>
                    </span>
                </div>
                <div class="tx-detail-row">
                    <span class="tdr-label">Tanggal Transaksi</span>
                    <span class="tdr-value"><?php echo date('d F Y, H:i', strtotime($order['tanggal_order'])); ?> WIB</span>
                </div>
                <div class="tx-detail-row">
                    <span class="tdr-label">Jumlah Tiket</span>
                    <span class="tdr-value"><?php echo $order['total_tiket']; ?> tiket</span>
                </div>
                <?php foreach ($items as $item): ?>
                <div class="tx-detail-row">
                    <span class="tdr-label"><?php echo htmlspecialchars($item['nama_tiket']); ?> &times;<?php echo $item['qty']; ?></span>
                    <span class="tdr-value">Rp <?php echo number_format($item['subtotal']); ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($order['potongan'])): ?>
                <div class="tx-detail-row">
                    <span class="tdr-label text-success">Diskon (<?php echo htmlspecialchars($order['kode_voucher']); ?>)</span>
                    <span class="tdr-value text-success">- Rp <?php echo number_format($order['potongan']); ?></span>
                </div>
                <?php endif; ?>
                <div class="tx-detail-row tx-total-row">
                    <span class="tdr-label">Total Pembayaran</span>
                    <span class="tdr-value text-brand">Rp <?php echo number_format($order['total']); ?></span>
                </div>
            </div>

            <!-- Detail Pembeli -->
            <div class="tx-section">
                <h6>Detail Pembeli</h6>
                <div class="tx-detail-row">
                    <span class="tdr-label">Nama</span>
                    <span class="tdr-value"><?php echo htmlspecialchars($order['nama_user']); ?></span>
                </div>
                <div class="tx-detail-row">
                    <span class="tdr-label">Email</span>
                    <span class="tdr-value"><?php echo htmlspecialchars($order['email_user']); ?></span>
                </div>
            </div>

            <!-- Detail Pengunjung -->
            <?php if (count($codes) > 0): ?>
            <div class="tx-section">
                <h6>Detail Pengunjung</h6>
                <?php foreach ($codes as $ci => $c): ?>
                <div class="pengunjung-item">
                    <div class="pengunjung-header" onclick="togglePengunjung(<?php echo $ci; ?>)" id="ph-<?php echo $ci; ?>">
                        <span><?php echo htmlspecialchars($c['nama_attendee'] ?: ('Pengunjung ' . ($ci + 1))); ?> &mdash; <?php echo htmlspecialchars($c['nama_tiket']); ?></span>
                        <i class="bi bi-chevron-down" id="ph-icon-<?php echo $ci; ?>"></i>
                    </div>
                    <div class="pengunjung-body d-none" id="pb-<?php echo $ci; ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="qr-medium flex-shrink-0" id="conf-qr-<?php echo $c['id_attendee']; ?>"></div>
                            <div>
                                <div class="pb-row"><span class="pb-label">Kode:</span><code class="small ms-2"><?php echo htmlspecialchars($c['kode_tiket']); ?></code></div>
                                <div class="pb-row mt-1">
                                    <?php if ($c['status_checkin'] === 'sudah'): ?>
                                    <span class="ic-status-badge paid">Sudah Check-in</span>
                                    <?php else: ?>
                                    <span class="ic-status-badge pending">Belum Check-in</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex flex-column gap-2">
                <?php if ($order['status'] === 'pending_payment'): ?>
                <a href="payment.php?id=<?php echo $id_order; ?>" class="btn btn-warning">
                    <i class="bi bi-credit-card me-1"></i> Lakukan Pembayaran
                </a>
                <?php elseif ($order['status'] === 'waiting_confirmation'): ?>
                <a href="payment.php?id=<?php echo $id_order; ?>" class="btn btn-outline-warning">
                    <i class="bi bi-clock me-1"></i> Lihat Status Pembayaran
                </a>
                <?php elseif ($order['status'] === 'rejected'): ?>
                <a href="payment.php?id=<?php echo $id_order; ?>" class="btn btn-danger">
                    <i class="bi bi-arrow-repeat me-1"></i> Upload Ulang Bukti
                </a>
                <?php elseif ($order['status'] === 'paid' && count($codes) > 0): ?>
                <a href="tickets.php" class="btn btn-primary-custom">
                    <i class="bi bi-qr-code-scan me-1"></i> Lihat E-Tiket
                </a>
                <?php endif; ?>
                <a href="history.php" class="btn btn-outline-secondary">
                    <i class="bi bi-receipt me-1"></i> Kembali ke Transaksi
                </a>
                <a href="home.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const openPanels = {};

function togglePengunjung(idx) {
    const body = document.getElementById('pb-' + idx);
    const icon = document.getElementById('ph-icon-' + idx);
    const isOpen = !body.classList.contains('d-none');
    if (isOpen) {
        body.classList.add('d-none');
        icon.className = 'bi bi-chevron-down';
    } else {
        body.classList.remove('d-none');
        icon.className = 'bi bi-chevron-up';
        if (!openPanels[idx]) {
            openPanels[idx] = true;
            setTimeout(function() {
                var codes = <?php echo json_encode(array_map(fn($c) => ['id' => $c['id_attendee'], 'code' => $c['kode_tiket']], $codes), JSON_HEX_TAG); ?>;
                codes.forEach(function(c) {
                    var el = document.getElementById('conf-qr-' + c.id);
                    if (el && !el.querySelector('canvas')) {
                        new QRCode(el, { text: c.code, width: 128, height: 128, correctLevel: QRCode.CorrectLevel.H });
                    }
                });
            }, 60);
        }
    }
}
</script>

<?php include 'footer.php'; ?>
