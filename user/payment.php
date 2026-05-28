<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'user')  { die('Akses ditolak'); }

$id_order = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id  = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*, u.nama as nama_user,
           MIN(e.nama_event) as nama_event, MIN(e.tanggal) as tanggal_event,
           MIN(e.poster_url) as poster_url, MIN(v.nama_venue) as nama_venue,
           MIN(v.alamat) as alamat_venue,
           vo.kode_voucher, vo.potongan
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    LEFT JOIN venue v ON e.id_venue = v.id_venue
    LEFT JOIN voucher vo ON o.id_voucher = vo.id_voucher
    WHERE o.id_order = ? AND o.id_user = ?
    GROUP BY o.id_order
");
$stmt->execute([$id_order, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_message('error', 'Order tidak ditemukan.');
    header('Location: home.php');
    exit;
}

// Redirect if already paid
if ($order['status'] === 'paid') {
    header('Location: order_confirm.php?id=' . $id_order);
    exit;
}

$stmtItems = $conn->prepare("
    SELECT od.*, t.nama_tiket, t.harga
    FROM order_detail od JOIN tiket t ON od.id_tiket = t.id_tiket
    WHERE od.id_order = ?
");
$stmtItems->execute([$id_order]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Load payment confirmation if exists
$stmtConf = $conn->prepare("SELECT * FROM payment_confirmation WHERE id_order = ?");
$stmtConf->execute([$id_order]);
$confirmation = $stmtConf->fetch(PDO::FETCH_ASSOC);

$invoice_no = $order['invoice_code'] ?? ('INV-' . date('Ymd') . '-' . str_pad($id_order, 5, '0', STR_PAD_LEFT));
$account_bank = 'BCA';
$account_number = '123456789';
$account_name = 'Sisinfo Tiketing';

$page_title  = 'Pembayaran';
$active_menu = 'home';
include 'header.php';
?>

<div class="container pb-5">
    <div class="d-flex align-items-center mb-3">
        <a href="history.php" class="text-dark me-2"><i class="bi bi-arrow-left fs-5"></i></a>
        <h5 class="fw-brand mb-0">Pembayaran</h5>
    </div>

    <?php echo show_flash(); ?>

    <?php if ($order['status'] === 'pending_payment'): ?>
    <!-- Invoice Header -->
    <div class="card card-clean mb-3">
        <div class="card-body text-center">
            <div class="text-muted small">No. Invoice</div>
            <div class="fs-5 fw-bold font-monospace"><?php echo htmlspecialchars($invoice_no); ?></div>
            <div class="mt-2"><span class="ic-status-badge pending">Menunggu Pembayaran</span></div>
        </div>
    </div>

    <!-- Countdown -->
    <div class="card card-clean mb-3">
        <div class="card-body text-center">
            <div class="text-muted small mb-1">Sisa Waktu Pembayaran</div>
            <div class="countdown-wrap" id="countdownWrap">
                <span class="countdown-value" id="countdownTimer">--:--:--</span>
            </div>
            <div class="text-muted small mt-1">Bayar sebelum waktu habis agar pesanan tidak otomatis dibatalkan</div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
            <div class="mb-2">
                <div class="fw-bold"><?php echo htmlspecialchars($order['nama_event']); ?></div>
                <div class="text-muted small">
                    <?php echo date('d F Y', strtotime($order['tanggal_event'])); ?>
                    <span class="mx-2">|</span>
                    <?php echo htmlspecialchars($order['nama_venue']); ?>
                </div>
            </div>
            <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                <div><?php echo htmlspecialchars($item['nama_tiket']); ?> &times;<?php echo $item['qty']; ?></div>
                <div>Rp <?php echo number_format($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (!empty($order['potongan'])): ?>
            <div class="d-flex justify-content-between align-items-center py-1 small text-success">
                <div>Diskon (<?php echo htmlspecialchars($order['kode_voucher']); ?>)</div>
                <div>- Rp <?php echo number_format($order['potongan']); ?></div>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center pt-2 fw-bold">
                <div>Total</div>
                <div class="text-brand">Rp <?php echo number_format($order['total']); ?></div>
            </div>
        </div>
    </div>

    <!-- Transfer Instruction -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Instruksi Pembayaran</h6>
            <p class="small text-muted">Lakukan transfer ke rekening berikut, kemudian upload bukti pembayaran.</p>
            <div class="bank-info-card">
                <div class="bank-info-row">
                    <span class="bir-label">Bank</span>
                    <span class="bir-value fw-bold"><?php echo $account_bank; ?></span>
                </div>
                <div class="bank-info-row">
                    <span class="bir-label">No. Rekening</span>
                    <span class="bir-value fw-bold font-monospace"><?php echo $account_number; ?></span>
                </div>
                <div class="bank-info-row">
                    <span class="bir-label">Atas Nama</span>
                    <span class="bir-value fw-bold"><?php echo $account_name; ?></span>
                </div>
                <div class="bank-info-row">
                    <span class="bir-label">Total Bayar</span>
                    <span class="bir-value fw-bold text-brand">Rp <?php echo number_format($order['total']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Payment Proof -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Upload Bukti Pembayaran</h6>
            <form action="../process/payment/upload_proof.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id_order" value="<?php echo $id_order; ?>">
                <div class="mb-3">
                    <label class="form-label">Nama Pengirim <span class="text-danger">*</span></label>
                    <input type="text" name="sender_name" class="form-control" placeholder="Nama sesuai rekening" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bank/Asal Transfer <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" placeholder="Contoh: BCA, Mandiri, GoPay" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Opsional"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Bukti Transfer <span class="text-danger">*</span></label>
                    <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                    <div class="form-text">Format: JPG, PNG, WEBP. Maks 2MB.</div>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">
                    <i class="bi bi-upload me-1"></i> Upload & Konfirmasi Pembayaran
                </button>
            </form>
        </div>
    </div>

    <script>
    (function() {
        var expiredAt = "<?php echo date('Y-m-d\\TH:i:s', strtotime($order['expired_at'])); ?>";
        var timer = document.getElementById('countdownTimer');
        function updateCountdown() {
            var now = new Date().getTime();
            var expiry = new Date(expiredAt).getTime();
            var diff = expiry - now;
            if (diff <= 0) { timer.textContent = '00:00:00'; location.reload(); return; }
            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            timer.textContent = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    })();
    </script>

    <?php elseif ($order['status'] === 'waiting_confirmation'): ?>
    <div class="card card-clean mb-3">
        <div class="card-body text-center py-5">
            <i class="bi bi-clock-history fs-1 text-warning mb-3 d-block"></i>
            <h5 class="fw-bold">Menunggu Verifikasi</h5>
            <p class="text-muted mb-1">Bukti pembayaran kamu telah kami terima.</p>
            <p class="text-muted">Admin akan memverifikasi pembayaran dalam waktu 1x24 jam.</p>
            <div class="mt-3"><span class="ic-status-badge pending">Menunggu Verifikasi</span></div>
            <div class="mt-3 small text-muted">
                No. Invoice: <span class="fw-bold font-monospace"><?php echo htmlspecialchars($invoice_no); ?></span>
            </div>
            <?php if ($confirmation): ?>
            <div class="mt-2 small text-muted">
                Bukti terupload: <?php echo date('d F Y, H:i', strtotime($confirmation['created_at'])); ?> WIB
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($order['status'] === 'rejected'): ?>
    <div class="card card-clean mb-3">
        <div class="card-body text-center py-4">
            <i class="bi bi-x-circle fs-1 text-danger mb-3 d-block"></i>
            <h5 class="fw-bold">Pembayaran Ditolak</h5>
            <?php if ($confirmation && $confirmation['reject_reason']): ?>
            <div class="alert alert-danger"><strong>Alasan:</strong> <?php echo htmlspecialchars($confirmation['reject_reason']); ?></div>
            <?php else: ?>
            <p class="text-muted">Mohon upload ulang bukti pembayaran yang valid.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Upload Ulang Bukti Pembayaran</h6>
            <form action="../process/payment/upload_proof.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id_order" value="<?php echo $id_order; ?>">
                <input type="hidden" name="is_resubmit" value="1">
                <div class="mb-3">
                    <label class="form-label">Nama Pengirim <span class="text-danger">*</span></label>
                    <input type="text" name="sender_name" class="form-control" value="<?php echo htmlspecialchars($confirmation['sender_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bank/Asal Transfer <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($confirmation['bank_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($confirmation['notes'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Bukti Transfer Baru <span class="text-danger">*</span></label>
                    <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                    <div class="form-text">Format: JPG, PNG, WEBP. Maks 2MB.</div>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">
                    <i class="bi bi-upload me-1"></i> Upload Ulang
                </button>
            </form>
        </div>
    </div>

    <?php elseif ($order['status'] === 'expired'): ?>
    <div class="card card-clean mb-3">
        <div class="card-body text-center py-5">
            <i class="bi bi-clock-history fs-1 text-muted mb-3 d-block"></i>
            <h5 class="fw-bold text-muted">Pembayaran Kadaluarsa</h5>
            <p class="text-muted">Waktu pembayaran untuk pesanan ini telah habis.</p>
            <a href="home.php" class="btn btn-primary-custom mt-3"><i class="bi bi-house me-1"></i> Kembali ke Beranda</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
