<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { die('Akses ditolak'); }

// Stat cards
$totalUsers     = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalOrders    = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue   = (int)$conn->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'paid'")->fetchColumn();
$totalTiketSold = (int)$conn->query("SELECT COALESCE(SUM(qty), 0) FROM order_detail od JOIN orders o ON od.id_order = o.id_order WHERE o.status = 'paid'")->fetchColumn();
$totalCheckin   = (int)$conn->query("SELECT COUNT(*) FROM attendee WHERE status_checkin = 'sudah'")->fetchColumn();

// Revenue chart (monthly, last 12 months)
$revenueMonthly = $conn->query("
    SELECT DATE_FORMAT(MIN(tanggal_order), '%b %Y') as bulan, SUM(total) as revenue
    FROM orders WHERE status = 'paid'
    GROUP BY DATE_FORMAT(tanggal_order, '%Y-%m')
    ORDER BY MIN(tanggal_order) ASC LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels  = array_column($revenueMonthly, 'bulan');
$chartRevenue = array_map('intval', array_column($revenueMonthly, 'revenue'));

// Bar chart: tiket terjual per event (top 10)
$tiketPerEvent = $conn->query("
    SELECT e.nama_event, COALESCE(SUM(od.qty), 0) as terjual
    FROM event e
    LEFT JOIN tiket t ON t.id_event = e.id_event
    LEFT JOIN order_detail od ON od.id_tiket = t.id_tiket
    LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
    GROUP BY e.id_event, e.nama_event
    ORDER BY terjual DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$eventLabels = array_column($tiketPerEvent, 'nama_event');
$eventTiket  = array_map('intval', array_column($tiketPerEvent, 'terjual'));

// Order status distribution
$statusDist = $conn->query("
    SELECT status, COUNT(*) as jumlah FROM orders GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);
$statusLabels = ['paid' => 'Paid', 'pending' => 'Pending', 'cancel' => 'Canceled'];
$statusColors = ['paid' => '#22c55e', 'pending' => '#f59e0b', 'cancel' => '#ef4444'];
$statusData   = [];
$statusLabArr = [];
$statusClrArr = [];
foreach (['paid', 'pending', 'cancel'] as $s) {
    $statusLabArr[] = $statusLabels[$s];
    $statusClrArr[] = $statusColors[$s];
    $statusData[]   = (int)($statusDist[$s] ?? 0);
}

// Recent 10 orders
$recentOrders = $conn->query("
    SELECT o.id_order, o.tanggal_order, o.total, o.status,
           u.nama as nama_user,
           MIN(e.nama_event) as nama_event,
           COALESCE(SUM(od.qty), 0) as qty
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    LEFT JOIN order_detail od ON o.id_order = od.id_order
    LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
    LEFT JOIN event e ON t.id_event = e.id_event
    GROUP BY o.id_order, u.nama
    ORDER BY o.tanggal_order DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
include 'header.php';
include 'sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-brand">Dashboard</h4>
    <span class="text-muted small">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></span>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="card card-clean p-3 h-100">
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
    <div class="col-6 col-md-4 col-xl">
        <div class="card card-clean p-3 h-100">
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
    <div class="col-6 col-md-4 col-xl">
        <div class="card card-clean p-3 h-100">
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
    <div class="col-6 col-md-4 col-xl">
        <div class="card card-clean p-3 h-100">
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
    <div class="col-6 col-md-4 col-xl">
        <div class="card card-clean p-3 h-100">
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

<!-- Charts Row 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-clean p-4 h-100">
            <h6 class="fw-brand mb-3"><i class="bi bi-graph-up me-2"></i>Pendapatan per Bulan</h6>
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-clean p-4 h-100">
            <h6 class="fw-brand mb-3"><i class="bi bi-pie-chart me-2"></i>Distribusi Status Order</h6>
            <div class="chart-wrap chart-doughnut"><canvas id="statusChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-clean p-4">
            <h6 class="fw-brand mb-3"><i class="bi bi-bar-chart me-2"></i>Tiket Terjual per Event (Top 10)</h6>
            <div class="chart-wrap chart-wide"><canvas id="tiketChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card card-clean">
    <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-2">
        <h6 class="fw-brand mb-0"><i class="bi bi-clock-history me-2"></i>Order Terbaru</h6>
        <a href="laporan.php" class="btn btn-sm btn-outline-primary">Lihat Semua &rsaquo;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recentOrders) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada order.</td></tr>
                <?php else: ?>
                <?php foreach ($recentOrders as $r): ?>
                <tr>
                    <td><code>#<?php echo str_pad($r['id_order'], 5, '0', STR_PAD_LEFT); ?></code></td>
                    <td class="small"><?php echo date('d M Y H:i', strtotime($r['tanggal_order'])); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_user']); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_event'] ?: '-'); ?></td>
                    <td><?php echo $r['qty']; ?></td>
                    <td>Rp <?php echo number_format($r['total']); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo match($r['status']) {
                            'paid'    => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'cancel'  => 'bg-danger',
                            default   => 'bg-secondary'
                        }; ?>"><?php echo ucfirst($r['status']); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    const fontFamily = "'Segoe UI', system-ui, sans-serif";

    // ── 1. Revenue Line Chart (with gradient fill) ─────────────────────────
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    const revGrad = revCtx.createLinearGradient(0, 0, 0, 320);
    revGrad.addColorStop(0, 'rgba(25,50,185,0.30)');
    revGrad.addColorStop(1, 'rgba(25,50,185,0.01)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?php echo json_encode($chartRevenue); ?>,
                borderColor: '#1932b9',
                backgroundColor: revGrad,
                fill: true,
                tension: 0.45,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1932b9',
                pointBorderWidth: 2.5,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1932b9',
                    titleFont: { size: 13, family: fontFamily },
                    bodyFont:  { size: 13, family: fontFamily },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: ctx => 'Rp ' + Number(ctx.raw).toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 12, family: fontFamily } } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#f3f4f6' },
                    ticks: {
                        font: { size: 11, family: fontFamily },
                        callback: v => 'Rp ' + Number(v).toLocaleString('id-ID', { notation: 'compact', maximumFractionDigits: 0 })
                    }
                }
            }
        }
    });

    // ── 2. Status Doughnut Chart ───────────────────────────────────────────
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($statusLabArr); ?>,
            datasets: [{
                data: <?php echo json_encode($statusData); ?>,
                backgroundColor: <?php echo json_encode($statusClrArr); ?>,
                borderColor: '#fff',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: { size: 12, family: fontFamily }
                    }
                },
                tooltip: {
                    backgroundColor: '#111827',
                    titleFont: { size: 13, family: fontFamily },
                    bodyFont:  { size: 13, family: fontFamily },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ' ' + ctx.label + ': ' + ctx.raw + ' order'
                    }
                }
            }
        },
        plugins: [{
            id: 'centerText',
            beforeDraw(chart) {
                const { ctx, width, height } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 22px ' + fontFamily;
                ctx.fillStyle = '#111827';
                const total = <?php echo json_encode(array_sum($statusData)); ?>;
                ctx.fillText(total, width / 2, height / 2 - 8);
                ctx.font = '12px ' + fontFamily;
                ctx.fillStyle = '#6b7280';
                ctx.fillText('Total Order', width / 2, height / 2 + 14);
                ctx.restore();
            }
        }]
    });

    // ── 3. Tiket per Event Bar Chart (with gradient + data labels) ──────────
    const tikCtx = document.getElementById('tiketChart').getContext('2d');
    const tikGrad = tikCtx.createLinearGradient(0, 0, 600, 0);
    tikGrad.addColorStop(0, 'rgba(37,87,214,0.85)');
    tikGrad.addColorStop(1, 'rgba(25,50,185,0.40)');

    new Chart(tikCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($eventLabels); ?>,
            datasets: [{
                label: 'Tiket Terjual',
                data: <?php echo json_encode($eventTiket); ?>,
                backgroundColor: tikGrad,
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 22,
                maxBarThickness: 28
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleFont: { size: 13, family: fontFamily },
                    bodyFont:  { size: 13, family: fontFamily },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ' ' + ctx.raw + ' tiket terjual'
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#f3f4f6' },
                    ticks: { stepSize: 1, font: { size: 11, family: fontFamily } }
                },
                y: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: {
                        font: { size: 12, family: fontFamily, weight: '600' },
                        color: '#374151'
                    }
                }
            }
        },
        plugins: [{
            id: 'barLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets.forEach((ds, dsIdx) => {
                    const meta = chart.getDatasetMeta(dsIdx);
                    meta.data.forEach(bar => {
                        const val = ds.data[meta.data.indexOf(bar)];
                        if (val > 0) {
                            ctx.save();
                            ctx.font = 'bold 12px ' + fontFamily;
                            ctx.fillStyle = '#1932b9';
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(val, bar.x + 8, bar.y);
                            ctx.restore();
                        }
                    });
                });
            }
        }]
    });
})();
</script>

<?php include 'footer.php'; ?>