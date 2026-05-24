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

$totalUsers     = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalOrders    = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue   = (int)$conn->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'paid'")->fetchColumn();
$totalTiketSold = (int)$conn->query("SELECT COALESCE(SUM(qty), 0) FROM order_detail")->fetchColumn();
$totalCheckin   = (int)$conn->query("SELECT COUNT(*) FROM attendee WHERE status_checkin = 'sudah'")->fetchColumn();

$revenueMonthly = $conn->query("
    SELECT DATE_FORMAT(tanggal_order, '%Y-%m') as bulan, SUM(total) as revenue
    FROM orders WHERE status = 'paid'
    GROUP BY DATE_FORMAT(tanggal_order, '%Y-%m')
    ORDER BY bulan ASC LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data   = [];
foreach ($revenueMonthly as $row) {
    $labels[] = $row['bulan'];
    $data[]   = (int)$row['revenue'];
}

$page_title = 'Dashboard';
$active_menu = 'dashboard';
include 'header.php';
include 'sidebar.php';
?>

<h4 class="mb-4 fw-brand">Dashboard</h4>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card card-clean p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 icon-circle">
                    <i class="bi bi-people-fill text-brand fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-muted small">User Terdaftar</div>
                    <div class="fw-bold fs-5"><?php echo number_format($totalUsers); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card card-clean p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 icon-circle">
                    <i class="bi bi-cart-fill text-brand fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-muted small">Total Order</div>
                    <div class="fw-bold fs-5"><?php echo number_format($totalOrders); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card card-clean p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 icon-circle">
                    <i class="bi bi-cash-stack text-brand fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-muted small">Pendapatan</div>
                    <div class="fw-bold fs-5">Rp <?php echo number_format($totalRevenue); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card card-clean p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 icon-circle">
                    <i class="bi bi-ticket-perforated-fill text-brand fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-muted small">Tiket Terjual</div>
                    <div class="fw-bold fs-5"><?php echo number_format($totalTiketSold); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card card-clean p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 icon-circle">
                    <i class="bi bi-qr-code-scan text-brand fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-muted small">Check-in</div>
                    <div class="fw-bold fs-5"><?php echo number_format($totalCheckin); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-clean p-4">
    <h5 class="fw-brand mb-3">Revenue per Bulan</h5>
    <canvas id="revenueChart" height="80"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#1932b9',
                backgroundColor: 'rgba(25,50,185,0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#1932b9'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rp ' + v.toLocaleString(); } } }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>