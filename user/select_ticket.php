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

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    flash_message('error', 'Event tidak valid.');
    header('Location: home.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT e.*, v.nama_venue, v.alamat
    FROM event e
    JOIN venue v ON e.id_venue = v.id_venue
    WHERE e.id_event = ?
");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: home.php');
    exit;
}

$stmtTiket = $conn->prepare("
    SELECT t.*,
        (t.kuota - COALESCE(
            (SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0
        )) as sisa
    FROM tiket t
    WHERE t.id_event = ?
    ORDER BY t.harga ASC
");
$stmtTiket->execute([$id]);
$tikets = $stmtTiket->fetchAll(PDO::FETCH_ASSOC);

$voucher_code = '';
$voucher_discount = 0;
$voucher_valid = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
    validate_csrf();
    $voucher_code = sanitize($_POST['voucher_code'] ?? '');
    if ($voucher_code !== '') {
        $stmtV = $conn->prepare("
            SELECT * FROM voucher
            WHERE kode_voucher = ? AND status = 'aktif' AND kuota > 0
        ");
        $stmtV->execute([strtoupper($voucher_code)]);
        $voucher = $stmtV->fetch(PDO::FETCH_ASSOC);
        if ($voucher) {
            $voucher_valid = true;
            $voucher_discount = (int)$voucher['potongan'];
        }
    }
}

$page_title = 'Pilih Tiket';
$active_menu = 'home';
include 'header.php';
?>

<div class="container pb-4">
    <!-- Event Mini Header -->
    <div class="d-flex align-items-center mb-3">
        <a href="detail_event.php?id=<?php echo $id; ?>" class="text-dark me-2"><i class="bi bi-arrow-left fs-5"></i></a>
        <div>
            <div class="fw-bold"><?php echo htmlspecialchars($event['nama_event']); ?></div>
            <div class="text-muted small"><?php echo htmlspecialchars($event['tanggal']); ?> &bull; <?php echo htmlspecialchars($event['nama_venue']); ?></div>
        </div>
    </div>

    <h5 class="fw-brand mb-3">Pilih Tiket</h5>

    <form id="ticketForm" action="checkout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id_event" value="<?php echo $id; ?>">
        <input type="hidden" name="voucher_code" id="voucherInputHidden" value="<?php echo htmlspecialchars($voucher_code); ?>">

        <?php if (count($tikets) === 0): ?>
        <div class="alert alert-info">Tiket untuk event ini belum tersedia.</div>
        <?php else: ?>
        <?php foreach ($tikets as $t): ?>
        <div class="ticket-row <?php echo $t['sisa'] <= 0 ? 'sold-out' : ''; ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($t['nama_tiket']); ?></div>
                    <div class="text-muted small">Rp <?php echo number_format($t['harga']); ?></div>
                    <?php if ($t['sisa'] > 0): ?>
                    <div class="text-success small">Tersisa <?php echo number_format($t['sisa']); ?> tiket</div>
                    <?php else: ?>
                    <div class="badge-sold d-inline-block mt-1">Habis Terjual</div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($t['sisa'] > 0): ?>
                    <div class="ticket-stepper">
                        <button type="button" onclick="updateQty(<?php echo $t['id_tiket']; ?>, -1, <?php echo $t['harga']; ?>, <?php echo $t['sisa']; ?>)">-</button>
                        <input type="number" name="qty[<?php echo $t['id_tiket']; ?>]" id="qty-<?php echo $t['id_tiket']; ?>" value="0" min="0" max="<?php echo $t['sisa']; ?>" readonly>
                        <button type="button" onclick="updateQty(<?php echo $t['id_tiket']; ?>, 1, <?php echo $t['harga']; ?>, <?php echo $t['sisa']; ?>)">+</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Attendee Names Section (shown dynamically by JS) -->
        <div class="d-none mb-3" id="attendeeNamesSection">
            <h6 class="fw-bold mb-2"><i class="bi bi-person-badge me-1 text-brand"></i>Detail Pengunjung</h6>
            <div id="attendeeNamesBody"></div>
        </div>

        <!-- Coupon Trigger -->
        <div class="mb-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#couponModal" class="link-brand">
                <i class="bi bi-ticket-perforated me-1"></i>
                <?php echo $voucher_valid ? 'Voucher: ' . htmlspecialchars($voucher_code) . ' (-Rp ' . number_format($voucher_discount) . ')' : 'Punya kode kupon? Masukkan di sini'; ?>
            </a>
        </div>

        <!-- Spacer for sticky CTA on mobile -->
        <div class="d-none-desktop sticky-spacer"></div>

        <!-- Sticky Bottom CTA with Summary -->
        <div class="sticky-cta">
            <div class="cta-info">
                <div class="price-label">Total (<span id="totalQty">0</span> tiket)</div>
                <div class="price-value" id="totalPrice">Rp 0</div>
            </div>
            <button type="submit" class="btn btn-primary-custom px-4" id="btnCheckout" disabled>
                Checkout
            </button>
        </div>
    </form>
</div>

<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-brand">Kode Kupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="couponForm">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="apply_voucher" value="1">
                    <div class="mb-3">
                        <label class="form-label">Masukkan kode kupon</label>
                        <input type="text" name="voucher_code" class="form-control" placeholder="CONTOH: DISKON50" value="<?php echo htmlspecialchars($voucher_code); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100">Gunakan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const prices = {};
const tiketLabels = {};
<?php foreach ($tikets as $t): ?>
prices[<?php echo $t['id_tiket']; ?>] = <?php echo $t['harga']; ?>;
tiketLabels[<?php echo $t['id_tiket']; ?>] = '<?php echo addslashes(htmlspecialchars($t['nama_tiket'])); ?>';
<?php endforeach; ?>
let voucherDiscount = <?php echo $voucher_discount; ?>;
const defaultName = '<?php echo addslashes(htmlspecialchars($_SESSION['nama'] ?? '')); ?>';

function escAttr(s) { return s.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

function updateQty(idTiket, delta, harga, sisa) {
    const input = document.getElementById('qty-' + idTiket);
    let val = parseInt(input.value) || 0;
    val += delta;
    if (val < 0) val = 0;
    if (val > sisa) val = sisa;
    input.value = val;
    recalc();
    updateAttendeeNames();
}

function recalc() {
    let totalQty = 0, subtotal = 0;
    for (const id in prices) {
        const qty = parseInt(document.getElementById('qty-' + id)?.value) || 0;
        totalQty += qty;
        subtotal += qty * prices[id];
    }
    const total = Math.max(0, subtotal - voucherDiscount);
    document.getElementById('totalQty').textContent = totalQty;
    document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('btnCheckout').disabled = totalQty === 0;
}

function updateAttendeeNames() {
    let totalQty = 0;
    for (const id in prices) totalQty += parseInt(document.getElementById('qty-' + id)?.value) || 0;
    const section = document.getElementById('attendeeNamesSection');
    if (totalQty === 0) { section.classList.add('d-none'); return; }
    section.classList.remove('d-none');
    const body = document.getElementById('attendeeNamesBody');
    const existing = {};
    body.querySelectorAll('input[name]').forEach(inp => { existing[inp.name] = inp.value; });
    body.innerHTML = '';
    let num = 1;
    for (const idTiket in prices) {
        const qty = parseInt(document.getElementById('qty-' + idTiket)?.value) || 0;
        const label = tiketLabels[idTiket] || 'Tiket';
        for (let i = 0; i < qty; i++) {
            const fieldName = 'attendee_names[' + idTiket + '][' + i + ']';
            const savedVal = existing[fieldName] || defaultName;
            const row = document.createElement('div');
            row.className = 'attendee-name-row';
            row.innerHTML = '<label>Pengunjung ' + num + ' &mdash; ' + label + '</label>' +
                '<input type="text" name="' + fieldName + '" placeholder="Nama lengkap pengunjung" value="' + escAttr(savedVal) + '" required>';
            body.appendChild(row);
            num++;
        }
    }
}

recalc();
</script>

<?php include 'footer.php'; ?>
