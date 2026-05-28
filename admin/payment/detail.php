<?php
session_start();
require_once '../../config/helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { die('Akses ditolak'); }

$id_order = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT o.*, u.nama as nama_user, u.email as email_user,
           vo.kode_voucher, vo.potongan
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN voucher vo ON o.id_voucher = vo.id_voucher
    WHERE o.id_order = ?
");
$stmt->execute([$id_order]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_message('error', 'Order tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$stmtItems = $conn->prepare("
    SELECT od.*, t.nama_tiket, t.harga, e.nama_event, e.tanggal, v.nama_venue
    FROM order_detail od
    JOIN tiket t ON od.id_tiket = t.id_tiket
    JOIN event e ON t.id_event = e.id_event
    LEFT JOIN venue v ON e.id_venue = v.id_venue
    WHERE od.id_order = ?
");
$stmtItems->execute([$id_order]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

$stmtConf = $conn->prepare("SELECT * FROM payment_confirmation WHERE id_order = ?");
$stmtConf->execute([$id_order]);
$confirmation = $stmtConf->fetch(PDO::FETCH_ASSOC);

$invoice_no = $order['invoice_code'] ?? ('INV-' . date('Ymd') . '-' . str_pad($id_order, 5, '0', STR_PAD_LEFT));

$page_title  = 'Detail Pembayaran';
$active_menu = 'payment';
include '../header.php';
include '../sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">
        Detail Pembayaran
        <span class="ic-status-badge <?php echo match($order['status']) {
            'paid'                  => 'paid',
            'waiting_confirmation'  => 'pending',
            'rejected'              => 'cancel',
            'pending_payment'       => 'pending',
            'expired'               => 'cancel',
            default                 => 'pending',
        }; ?> ms-2"><?php echo match($order['status']) {
            'paid'                  => 'Disetujui',
            'waiting_confirmation'  => 'Menunggu Verifikasi',
            'rejected'              => 'Ditolak',
            'pending_payment'       => 'Belum Bayar',
            'expired'               => 'Kadaluarsa',
            default                 => $order['status'],
        }; ?></span>
    </h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<div class="row g-4">
    <!-- Left: Order Info + Items -->
    <div class="col-lg-8">
        <!-- Invoice Info -->
        <div class="card card-clean mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Detail Invoice</h6>
                    <span class="font-monospace fw-bold"><?php echo htmlspecialchars($invoice_no); ?></span>
                </div>
                <div class="row">
                    <div class="col-6 mb-2">
                        <div class="text-muted small">Pemesan</div>
                        <div class="fw-bold"><?php echo htmlspecialchars($order['nama_user']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($order['email_user']); ?></div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="text-muted small">Tanggal Order</div>
                        <div><?php echo date('d F Y, H:i', strtotime($order['tanggal_order'])); ?> WIB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card card-clean mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2"></i>Detail Tiket</h6>
                <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($item['nama_event']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($item['nama_tiket']); ?> &times;<?php echo $item['qty']; ?> &mdash; <?php echo date('d F Y', strtotime($item['tanggal'])); ?> &bull; <?php echo htmlspecialchars($item['nama_venue']); ?></div>
                    </div>
                    <div class="fw-bold">Rp <?php echo number_format($item['subtotal']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($order['potongan'])): ?>
                <div class="d-flex justify-content-between align-items-center py-2 text-success">
                    <div>Diskon (<?php echo htmlspecialchars($order['kode_voucher']); ?>)</div>
                    <div>- Rp <?php echo number_format($order['potongan']); ?></div>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center pt-2 fw-bold fs-5">
                    <div>Total</div>
                    <div class="text-brand">Rp <?php echo number_format($order['total']); ?></div>
                </div>
            </div>
        </div>

        <!-- Approval/Rejection History -->
        <?php if ($confirmation): ?>
        <?php if ($order['status'] === 'rejected' && $confirmation['reject_reason']): ?>
        <div class="card card-clean mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2 text-danger"><i class="bi bi-x-circle me-2"></i>Alasan Penolakan</h6>
                <div class="alert alert-danger mb-0"><?php echo htmlspecialchars($confirmation['reject_reason']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Right: Payment Proof + Actions -->
    <div class="col-lg-4">
        <?php if ($confirmation): ?>
        <!-- Payment Proof -->
        <div class="card card-clean mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-image me-2"></i>Bukti Pembayaran</h6>
                <a href="../../assets/<?php echo htmlspecialchars($confirmation['payment_proof']); ?>" target="_blank">
                    <img src="../../assets/<?php echo htmlspecialchars($confirmation['payment_proof']); ?>"
                         class="img-fluid rounded border" alt="Bukti Pembayaran"
                         style="cursor: zoom-in;">
                </a>
            </div>
        </div>

        <!-- Sender Info -->
        <div class="card card-clean mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Info Pengirim</h6>
                <div class="mb-2">
                    <div class="text-muted small">Nama Pengirim</div>
                    <div class="fw-bold"><?php echo htmlspecialchars($confirmation['sender_name']); ?></div>
                </div>
                <div class="mb-2">
                    <div class="text-muted small">Bank</div>
                    <div class="fw-bold"><?php echo htmlspecialchars($confirmation['bank_name']); ?></div>
                </div>
                <?php if ($confirmation['notes']): ?>
                <div class="mb-2">
                    <div class="text-muted small">Catatan</div>
                    <div><?php echo htmlspecialchars($confirmation['notes']); ?></div>
                </div>
                <?php endif; ?>
                <div class="mb-2">
                    <div class="text-muted small">Waktu Upload</div>
                    <div><?php echo date('d F Y, H:i', strtotime($confirmation['created_at'])); ?> WIB</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($order['status'] === 'waiting_confirmation'): ?>
        <div class="card card-clean mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-check2-square me-2"></i>Verifikasi</h6>
                <form method="POST" action="action.php" onsubmit="return confirm('Setujui pembayaran ini?')">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="act" value="approve">
                    <input type="hidden" name="id_order" value="<?php echo $id_order; ?>">
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="bi bi-check-lg me-1"></i> Setujui Pembayaran
                    </button>
                </form>
                <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-lg me-1"></i> Tolak Pembayaran
                </button>
            </div>
        </div>
        <?php elseif ($order['status'] === 'paid'): ?>
        <div class="card card-clean mb-4">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill fs-1 text-success mb-2 d-block"></i>
                <div class="fw-bold text-success">Pembayaran Disetujui</div>
                <?php if ($confirmation['confirmed_at']): ?>
                <div class="small text-muted mt-1">
                    <?php echo date('d F Y, H:i', strtotime($confirmation['confirmed_at'])); ?> WIB
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="card card-clean mb-4">
            <div class="card-body text-center py-5">
                <i class="bi bi-hourglass-split fs-1 text-muted mb-3 d-block"></i>
                <div class="text-muted">Belum ada bukti pembayaran yang diupload.</div>
                <?php if ($order['status'] === 'expired'): ?>
                <div class="text-danger mt-2 small">Order ini telah kadaluarsa.</div>
                <?php elseif ($order['status'] === 'pending_payment'): ?>
                <div class="text-muted mt-2 small">Menunggu user melakukan pembayaran.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($confirmation && $order['status'] === 'waiting_confirmation'): ?>
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="action.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="act" value="reject">
                <input type="hidden" name="id_order" value="<?php echo $id_order; ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tolak Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Pembayaran akan ditolak dan user dapat mengupload ulang bukti.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../footer.php'; ?>
