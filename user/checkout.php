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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

validate_csrf();

$id_event      = (int)($_POST['id_event'] ?? 0);
$qty_input     = $_POST['qty'] ?? [];
$voucher_code  = sanitize($_POST['voucher_code'] ?? '');
$attendee_names = $_POST['attendee_names'] ?? [];

if ($id_event < 1) {
    flash_message('error', 'Event tidak valid.');
    header('Location: home.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT e.*, v.nama_venue
    FROM event e
    JOIN venue v ON e.id_venue = v.id_venue
    WHERE e.id_event = ?
");
$stmt->execute([$id_event]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    flash_message('error', 'Event tidak ditemukan.');
    header('Location: home.php');
    exit;
}

$items = [];
$subtotal = 0;
foreach ($qty_input as $id_tiket => $qty_val) {
    $qty = (int)$qty_val;
    if ($qty < 1) continue;

    $stmtT = $conn->prepare("
        SELECT t.*,
            (t.kuota - COALESCE(
                (SELECT SUM(od.qty) FROM order_detail od WHERE od.id_tiket = t.id_tiket), 0
            )) as sisa
        FROM tiket t
        WHERE t.id_tiket = ? AND t.id_event = ?
    ");
    $stmtT->execute([$id_tiket, $id_event]);
    $tiket = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$tiket) continue;
    if ($qty > $tiket['sisa']) {
        flash_message('error', 'Kuota tiket ' . htmlspecialchars($tiket['nama_tiket']) . ' tidak mencukupi. Tersisa ' . $tiket['sisa'] . '.');
        header('Location: select_ticket.php?id=' . $id_event);
        exit;
    }

    $item_sub = $qty * $tiket['harga'];
    $items[] = [
        'id_tiket' => $id_tiket,
        'nama_tiket' => $tiket['nama_tiket'],
        'harga' => $tiket['harga'],
        'qty' => $qty,
        'subtotal' => $item_sub,
    ];
    $subtotal += $item_sub;
}

if (count($items) === 0) {
    flash_message('error', 'Pilih minimal 1 tiket.');
    header('Location: select_ticket.php?id=' . $id_event);
    exit;
}

$voucher_discount = 0;
$voucher_valid = false;
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
    } else {
        $voucher_error = 'Kode voucher tidak valid atau sudah tidak aktif.';
    }
}

$total = max(0, $subtotal - $voucher_discount);

$page_title = 'Checkout';
$active_menu = 'home';
include 'header.php';
?>

<div class="container pb-4">
    <!-- Header -->
    <div class="d-flex align-items-center mb-3">
        <a href="select_ticket.php?id=<?php echo $id_event; ?>" class="text-dark me-2"><i class="bi bi-arrow-left fs-5"></i></a>
        <h5 class="fw-brand mb-0">Checkout</h5>
    </div>

    <!-- Event Info -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
            <div class="text-muted small">
                <i class="bi bi-calendar-event me-1"></i> <?php echo htmlspecialchars($event['tanggal']); ?>
                <span class="mx-2">|</span>
                <i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($event['nama_venue']); ?>
            </div>
        </div>
    </div>

    <!-- Ticket List -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
            <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($item['nama_tiket']); ?></div>
                    <div class="text-muted small"><?php echo $item['qty']; ?> x Rp <?php echo number_format($item['harga']); ?></div>
                </div>
                <div class="fw-bold">Rp <?php echo number_format($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Attendee Names Review -->
    <?php
    $allAttendeeNames = [];
    foreach ($items as $item) {
        $names = $attendee_names[$item['id_tiket']] ?? [];
        for ($i = 0; $i < $item['qty']; $i++) {
            $allAttendeeNames[] = [
                'tiket' => $item['nama_tiket'],
                'nama'  => sanitize($names[$i] ?? ($_SESSION['nama'] ?? '')),
            ];
        }
    }
    ?>
    <?php if (count($allAttendeeNames) > 0): ?>
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Detail Pengunjung</h6>
            <?php foreach ($allAttendeeNames as $idx => $att): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                <div class="text-muted">Pengunjung <?php echo $idx + 1; ?> &mdash; <?php echo htmlspecialchars($att['tiket']); ?></div>
                <div class="fw-bold"><?php echo htmlspecialchars($att['nama']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Voucher -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Voucher</h6>
            <form method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id_event" value="<?php echo $id_event; ?>">
                <?php foreach ($items as $item): ?>
                <input type="hidden" name="qty[<?php echo $item['id_tiket']; ?>]" value="<?php echo $item['qty']; ?>">
                <?php endforeach; ?>
                <?php foreach ($attendee_names as $tid => $names): ?>
                <?php foreach ($names as $idx => $nm): ?>
                <input type="hidden" name="attendee_names[<?php echo (int)$tid; ?>][<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars(sanitize($nm)); ?>">
                <?php endforeach; ?>
                <?php endforeach; ?>
                <div class="col-8">
                    <input type="text" name="voucher_code" class="form-control" placeholder="Masukkan kode voucher" value="<?php echo htmlspecialchars($voucher_code); ?>">
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-outline-primary w-100">Gunakan</button>
                </div>
            </form>
            <?php if (isset($voucher_error)): ?>
            <div class="alert alert-danger mt-2 mb-0 py-2 small"><?php echo htmlspecialchars($voucher_error); ?></div>
            <?php elseif ($voucher_valid): ?>
            <div class="alert alert-success mt-2 mb-0 py-2 small">Voucher berhasil! Diskon Rp <?php echo number_format($voucher_discount); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Total -->
    <div class="card card-clean mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span class="fw-bold">Rp <?php echo number_format($subtotal); ?></span>
            </div>
            <?php if ($voucher_valid): ?>
            <div class="d-flex justify-content-between mb-2 text-success">
                <span>Diskon</span>
                <span class="fw-bold">- Rp <?php echo number_format($voucher_discount); ?></span>
            </div>
            <?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold">Total Bayar</span>
                <span class="fs-5 fw-bold text-brand">Rp <?php echo number_format($total); ?></span>
            </div>
        </div>
    </div>

    <!-- Spacer for sticky CTA -->
    <div class="d-none-desktop sticky-spacer"></div>

    <!-- Sticky CTA -->
    <div class="sticky-cta">
        <div class="cta-info">
            <div class="price-label">Total Bayar</div>
            <div class="price-value">Rp <?php echo number_format($total); ?></div>
        </div>
        <form action="/sisinfo-tiketing/process/order_process.php" method="POST" class="m-0">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="id_event" value="<?php echo $id_event; ?>">
            <?php foreach ($items as $item): ?>
            <input type="hidden" name="qty[<?php echo $item['id_tiket']; ?>]" value="<?php echo $item['qty']; ?>">
            <?php endforeach; ?>
            <input type="hidden" name="voucher_code" value="<?php echo htmlspecialchars($voucher_code); ?>">
            <?php foreach ($attendee_names as $tid => $names): ?>
            <?php foreach ($names as $idx => $nm): ?>
            <input type="hidden" name="attendee_names[<?php echo (int)$tid; ?>][<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars(sanitize($nm)); ?>">
            <?php endforeach; ?>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary-custom px-4">
                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
