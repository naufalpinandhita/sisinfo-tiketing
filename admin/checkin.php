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

$result = null;
$error  = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $kode = sanitize($_POST['kode_tiket'] ?? '');

    if ($kode === '') {
        $error = 'Kode tiket harus diisi.';
    } else {
        $stmt = $conn->prepare("
            SELECT a.*, od.qty, o.id_order, u.nama as nama_user, e.nama_event, t.nama_tiket
            FROM attendee a
            JOIN order_detail od ON a.id_detail = od.id_detail
            JOIN orders o ON od.id_order = o.id_order
            JOIN users u ON o.id_user = u.id_user
            JOIN tiket t ON od.id_tiket = t.id_tiket
            JOIN event e ON t.id_event = e.id_event
            WHERE a.kode_tiket = ?
        ");
        $stmt->execute([$kode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $error = 'Kode tiket tidak valid.';
        } elseif ($result['status_checkin'] === 'sudah') {
            $error = 'Tiket sudah check-in sebelumnya, tidak bisa digunakan lagi.';
        } else {
            $upd = $conn->prepare("UPDATE attendee SET status_checkin = 'sudah', waktu_checkin = NOW() WHERE id_attendee = ?");
            $upd->execute([$result['id_attendee']]);
            $success = 'Check-in berhasil!';
            $result['status_checkin'] = 'sudah';
            $result['waktu_checkin'] = date('Y-m-d H:i:s');
        }
    }
}

$page_title = 'Check-in';
$active_menu = 'checkin';
include 'header.php';
include 'sidebar.php';
?>

<h4 class="mb-4 fw-brand">Check-in Tiket</h4>

<div class="card card-clean p-4 form-max-w-sm">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3">
            <label for="kode_tiket" class="form-label">Kode Tiket <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="kode_tiket" name="kode_tiket" placeholder="Masukkan kode tiket" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-qr-code-scan me-1"></i> Cek Tiket</button>
    </form>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mt-4 form-max-w-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success && $result): ?>
<div class="alert alert-success mt-4 form-max-w">
    <h6 class="fw-bold mb-2"><i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($success); ?></h6>
    <table class="table table-sm table-borderless mb-0">
        <tr><td class="fw-bold" width="120">Kode Tiket</td><td><?php echo htmlspecialchars($result['kode_tiket']); ?></td></tr>
        <tr><td class="fw-bold">Nama</td><td><?php echo htmlspecialchars($result['nama_user']); ?></td></tr>
        <tr><td class="fw-bold">Event</td><td><?php echo htmlspecialchars($result['nama_event']); ?></td></tr>
        <tr><td class="fw-bold">Tiket</td><td><?php echo htmlspecialchars($result['nama_tiket']); ?></td></tr>
        <tr><td class="fw-bold">Status</td><td><span class="badge bg-success">Sudah Check-in</span></td></tr>
        <tr><td class="fw-bold">Waktu</td><td><?php echo htmlspecialchars($result['waktu_checkin']); ?></td></tr>
    </table>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
