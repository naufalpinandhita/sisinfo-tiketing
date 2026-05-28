<?php
if (!isset($page_title))  $page_title  = 'Event Ticketing';
if (!isset($active_menu)) $active_menu = '';
$_user_nama  = htmlspecialchars($_SESSION['nama']  ?? 'User');
$_user_email = htmlspecialchars($_SESSION['email'] ?? '');
$_search_val = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Event Ticketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/sisinfo-tiketing/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body class="has-bottom-nav">

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="position-fixed top-0 start-50 translate-middle-x mt-3 px-3 flash-toast">
    <div class="alert alert-<?php echo $_SESSION['flash_type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show shadow-sm">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Announcement Bar (desktop) -->
<div class="announcement-bar d-none d-lg-block">
    Daftarkan Eventmu Sekarang &mdash; Platform tiket event terpercaya
</div>

<!-- White Header -->
<header class="user-header">
    <div class="container-fluid px-3 px-lg-4">
        <div class="header-inner">
            <a href="/sisinfo-tiketing/user/home.php" class="header-logo me-3">
                event<span class="logo-dark">Ticketing</span>
            </a>
            <div class="header-search-wrap">
                <form method="GET" action="/sisinfo-tiketing/user/home.php">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama acara atau tempat" value="<?php echo $_search_val; ?>">
                        <button type="submit" class="btn-search"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
            <nav class="header-nav">
                <a href="/sisinfo-tiketing/user/history.php" class="nav-link-yp <?php echo $active_menu === 'history' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span class="d-none d-lg-inline">Transaksi</span>
                </a>
                <a href="/sisinfo-tiketing/user/tickets.php" class="nav-link-yp <?php echo $active_menu === 'tickets' ? 'active' : ''; ?>">
                    <i class="bi bi-ticket"></i>
                    <span class="d-none d-lg-inline">Tiket</span>
                </a>
                <div class="dropdown">
                    <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-lg-inline"><?php echo $_user_nama; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm dropdown-menu-hdr">
                        <li><span class="dropdown-item-text small text-muted py-2"><?php echo $_user_email; ?></span></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item small py-2" href="/sisinfo-tiketing/user/history.php"><i class="bi bi-receipt me-2 text-muted"></i>Transaksi Saya</a></li>
                        <li><a class="dropdown-item small py-2" href="/sisinfo-tiketing/user/tickets.php"><i class="bi bi-ticket me-2 text-muted"></i>Tiket Saya</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item small py-2" href="/sisinfo-tiketing/process/auth_logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Keluar</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
</header>

<!-- Mobile Bottom Navigation -->
<nav class="bottom-nav">
    <a class="nav-item <?php echo $active_menu === 'home' ? 'active' : ''; ?>" href="/sisinfo-tiketing/user/home.php">
        <i class="bi bi-house"></i>
        <span>Beranda</span>
    </a>
    <a class="nav-item <?php echo $active_menu === 'history' ? 'active' : ''; ?>" href="/sisinfo-tiketing/user/history.php">
        <i class="bi bi-receipt"></i>
        <span>Transaksi</span>
    </a>
    <a class="nav-item <?php echo $active_menu === 'tickets' ? 'active' : ''; ?>" href="/sisinfo-tiketing/user/tickets.php">
        <i class="bi bi-ticket"></i>
        <span>Tiket</span>
    </a>
    <div class="dropdown">
        <a class="nav-item" data-bs-toggle="dropdown" href="#">
            <i class="bi bi-person-circle"></i>
            <span>Profil</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm mb-2 dropdown-menu-profile">
            <li><a class="dropdown-item small py-2" href="/sisinfo-tiketing/process/auth_logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Keluar</a></li>
        </ul>
    </div>
</nav>

<main class="user-main">
