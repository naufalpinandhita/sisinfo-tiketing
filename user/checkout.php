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

$id_event = (int)($_POST['id_event'] ?? 0);
$qty_input = $_POST['qty'] ?? [];
$voucher_code = sanitize($_POST['voucher_code'] ?? '');

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
        header('Location: detail_event.php?id=' . $id_event);
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
    header('Location: detail_event.php?id=' . $id_event);
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

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h4 class="fw-brand mb-4">Checkout</h4>
            <div class="card card-clean mb-4">
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
                    <div class="text-muted small mb-3">
                        <i class="bi bi-calendar-event me-1"></i> <?php echo htmlspecialchars($event['tanggal']); ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($event['nama_venue']); ?>
                    </div>
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
                </div>
            </div>

            <div class="card card-clean mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Voucher</h6>
                    <form method="POST" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="id_event" value="<?php echo $id_event; ?>">
                        <?php foreach ($items as $item): ?>
                        <input type="hidden" name="qty[<?php echo $item['id_tiket']; ?>]" value="<?php echo $item['qty']; ?>">
                        <?php endforeach; ?>
                        <div class="col-md-6">
                            <input type="text" name="voucher_code" class="form-control" placeholder="Masukkan kode voucher" value="<?php echo htmlspecialchars($voucher_code); ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary w-100">Gunakan Voucher</button>
                        </div>
                    </form>
                    <?php if (isset($voucher_error)): ?>
                    <div class="alert alert-danger mt-2 mb-0 py-2"><?php echo htmlspecialchars($voucher_error); ?></div>
                    <?php elseif ($voucher_valid): ?>
                    <div class="alert alert-success mt-2 mb-0 py-2">Voucher berhasil digunakan. Diskon Rp <?php echo number_format($voucher_discount); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-clean mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span class="fw-bold">Rp <?php echo number_format($subtotal); ?></span>
                    </div>
                    <?php if ($voucher_valid): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Diskon Voucher</span>
                        <span class="fw-bold">- Rp <?php echo number_format($voucher_discount); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold">Total</span>
                        <span class="fs-5 fw-bold text-brand">Rp <?php echo number_format($total); ?></span>
                    </div>
                </div>
            </div>

            <form action="/sisinfo-tiketing/process/order_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id_event" value="<?php echo $id_event; ?>">
                <?php foreach ($items as $item): ?>
                <input type="hidden" name="qty[<?php echo $item['id_tiket']; ?>]" value="<?php echo $item['qty']; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="voucher_code" value="<?php echo htmlspecialchars($voucher_code); ?>">
                <div class="d-flex justify-content-between">
                    <a href="detail_event.php?id=<?php echo $id_event; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-credit-card me-1"></i> Proses Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
