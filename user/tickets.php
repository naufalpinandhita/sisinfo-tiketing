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

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['upcoming', 'past']) ? $_GET['tab'] : 'upcoming';

$sql = "
    SELECT o.id_order, MIN(e.id_event) as id_event, MIN(e.nama_event) as nama_event,
           MIN(e.tanggal) as tanggal, MIN(e.poster_url) as poster_url,
           COUNT(a.id_attendee) as total_tiket
    FROM orders o
    JOIN order_detail od ON o.id_order = od.id_order
    JOIN tiket t ON od.id_tiket = t.id_tiket
    JOIN event e ON t.id_event = e.id_event
    JOIN attendee a ON a.id_detail = od.id_detail
    WHERE o.id_user = ? AND o.status = 'paid'
";

if ($tab === 'upcoming') {
    $sql .= " AND e.tanggal >= CURDATE()";
} else {
    $sql .= " AND e.tanggal < CURDATE()";
}
$sql .= " GROUP BY o.id_order HAVING total_tiket > 0 ORDER BY MIN(e.tanggal) DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all attendee data for JS modal population
$stmtAll = $conn->prepare("
    SELECT o.id_order, a.id_attendee, a.kode_tiket, a.status_checkin,
           COALESCE(a.nama_attendee, '') as nama_attendee,
           t.nama_tiket, e.nama_event, e.tanggal
    FROM orders o
    JOIN order_detail od ON o.id_order = od.id_order
    JOIN attendee a ON a.id_detail = od.id_detail
    JOIN tiket t ON od.id_tiket = t.id_tiket
    JOIN event e ON t.id_event = e.id_event
    WHERE o.id_user = ? AND o.status = 'paid'
    ORDER BY o.id_order, t.nama_tiket, a.id_attendee
");
try {
    $stmtAll->execute([$user_id]);
    $allAttendees = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if nama_attendee column doesn't exist
    $stmtAll2 = $conn->prepare("
        SELECT o.id_order, a.id_attendee, a.kode_tiket, a.status_checkin, '' as nama_attendee,
               t.nama_tiket, e.nama_event, e.tanggal
        FROM orders o
        JOIN order_detail od ON o.id_order = od.id_order
        JOIN attendee a ON a.id_detail = od.id_detail
        JOIN tiket t ON od.id_tiket = t.id_tiket
        JOIN event e ON t.id_event = e.id_event
        WHERE o.id_user = ? AND o.status = 'paid'
        ORDER BY o.id_order, t.nama_tiket, a.id_attendee
    ");
    $stmtAll2->execute([$user_id]);
    $allAttendees = $stmtAll2->fetchAll(PDO::FETCH_ASSOC);
}

// Group by order_id for JS
$attendeeMap = [];
foreach ($allAttendees as $a) {
    $attendeeMap[$a['id_order']][] = $a;
}

$page_title  = 'Tiket';
$active_menu = 'tickets';
include 'header.php';
?>

<div class="container pb-5">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <h4 class="fw-bold mb-0">Tiket</h4>
        <a href="history.php" class="link-brand small">Riwayat Transaksi</a>
    </div>

    <!-- Tabs -->
    <div class="ticket-tabs-wrap">
        <button class="ticket-tab-btn <?php echo $tab === 'upcoming' ? 'active' : ''; ?>"
                onclick="location.href='?tab=upcoming'">Event Mendatang</button>
        <button class="ticket-tab-btn <?php echo $tab === 'past' ? 'active' : ''; ?>"
                onclick="location.href='?tab=past'">Event Berlalu</button>
    </div>

    <!-- Ticket List -->
    <?php if (count($tickets) === 0): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-ticket-perforated fs-1 mb-3 d-block"></i>
        <p>Tidak ada tiket untuk event <?php echo $tab === 'upcoming' ? 'mendatang' : 'yang telah berlalu'; ?>.</p>
    </div>
    <?php else: ?>
    <?php foreach ($tickets as $tk): ?>
    <div class="tk-card">
        <?php if (!empty($tk['poster_url'])): ?>
        <img src="/sisinfo-tiketing/assets/images/<?php echo htmlspecialchars($tk['poster_url']); ?>" class="tk-thumb" alt="">
        <?php else: ?>
        <div class="tk-thumb-ph"><i class="bi bi-image"></i></div>
        <?php endif; ?>
        <div class="tk-body">
            <div class="tk-title"><?php echo htmlspecialchars($tk['nama_event']); ?></div>
            <div class="tk-meta"><i class="bi bi-calendar-event me-1"></i><?php echo date('d M Y', strtotime($tk['tanggal'])); ?></div>
            <div class="tk-qty"><i class="bi bi-person me-1"></i><?php echo $tk['total_tiket']; ?> Tiket</div>
            <div class="tk-actions">
                <a href="order_confirm.php?id=<?php echo urlencode($tk['id_order']); ?>" class="btn-detail">Detail Transaksi</a>
                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#eTicketModal" onclick="showEticket(<?php echo $tk['id_order']; ?>)">
                    <i class="bi bi-qr-code me-1"></i> Lihat E-Tiket
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- E-Ticket Modal (Two-Panel) -->
<div class="modal fade eticket-modal" id="eTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">E-Tiket</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="eticket-two-panel" id="eticketTwoPanel">
                    <div class="eticket-attendee-list" id="eticketAttendeeList"></div>
                    <div class="eticket-main" id="eticketMain">
                        <div class="text-center py-4 text-muted">Memuat tiket...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const attendeeData = <?php echo json_encode($attendeeMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
let currentOrderId = null;

function escapeHtml(text) {
    var d = document.createElement('div'); d.textContent = String(text); return d.innerHTML;
}

function showEticket(orderId) {
    currentOrderId = orderId;
    const data = attendeeData[orderId];
    const list = document.getElementById('eticketAttendeeList');
    const main = document.getElementById('eticketMain');

    if (!data || data.length === 0) {
        list.innerHTML = '';
        main.innerHTML = '<div class="text-center py-5 text-muted">Tiket tidak ditemukan.</div>';
        return;
    }

    list.innerHTML = '';
    data.forEach(function(a, idx) {
        const item = document.createElement('div');
        item.className = 'att-item' + (idx === 0 ? ' active' : '');
        item.dataset.idx = idx;
        item.onclick = function() { selectAttendee(orderId, idx); };
        const displayName = a.nama_attendee || ('Tiket ' + (idx + 1));
        item.innerHTML = escapeHtml(displayName) +
            '<br><small class="att-sub">' + escapeHtml(a.nama_tiket) + '</small>';
        list.appendChild(item);
    });

    renderAttendeeDetail(data, 0);
}

function selectAttendee(orderId, idx) {
    const data = attendeeData[orderId];
    document.querySelectorAll('#eticketAttendeeList .att-item').forEach(function(el, i) {
        el.classList.toggle('active', i === idx);
    });
    renderAttendeeDetail(data, idx);
}

function renderAttendeeDetail(data, idx) {
    const a = data[idx];
    const main = document.getElementById('eticketMain');
    const checkinClass = a.status_checkin === 'sudah' ? 'paid' : 'pending';
    const checkinLbl   = a.status_checkin === 'sudah' ? 'Sudah Check-in' : 'Belum Check-in';

    main.innerHTML =
        '<div class="eticket-alert"><i class="bi bi-info-circle-fill flex-shrink-0"></i>' +
        'Tunjukkan QR Code ini kepada petugas saat memasuki venue.</div>' +
        '<div class="eticket-qr-wrap"><div class="qr-large" id="et-qr-' + a.id_attendee + '"></div></div>' +
        '<div class="eticket-code">' + escapeHtml(a.kode_tiket) + '</div>' +
        '<table class="eticket-info-table"><tbody>' +
        '<tr><td>Event</td><td>' + escapeHtml(a.nama_event) + '</td></tr>' +
        '<tr><td>Tanggal</td><td>' + escapeHtml(a.tanggal) + '</td></tr>' +
        '<tr><td>Nama</td><td>' + escapeHtml(a.nama_attendee || '-') + '</td></tr>' +
        '<tr><td>Kategori</td><td>' + escapeHtml(a.nama_tiket) + '</td></tr>' +
        '<tr><td>Status</td><td><span class="ic-status-badge ' + checkinClass + '">' + checkinLbl + '</span></td></tr>' +
        '</tbody></table>' +
        '<button class="btn-unduh" onclick="window.print()"><i class="bi bi-download me-2"></i>Unduh E-Tiket</button>';

    setTimeout(function() {
        var el = document.getElementById('et-qr-' + a.id_attendee);
        if (el && !el.querySelector('canvas')) {
            new QRCode(el, { text: a.kode_tiket, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.H });
        }
    }, 60);
}
</script>

<?php include 'footer.php'; ?>
