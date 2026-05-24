<?php
if (!isset($page_title)) $page_title = 'Event Ticketing';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Event Ticketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/sisinfo-tiketing/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-brand-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/sisinfo-tiketing/user/home.php">
                <i class="bi bi-ticket-perforated-fill me-2"></i>Event Ticketing
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
                <i class="bi bi-list fs-lg text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="userNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active_menu ?? '') === 'home' ? 'active' : ''; ?>" href="/sisinfo-tiketing/user/home.php">Event</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active_menu ?? '') === 'history' ? 'active' : ''; ?>" href="/sisinfo-tiketing/user/history.php">Riwayat</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <span class="nav-link d-inline-flex align-items-center gap-1">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="/sisinfo-tiketing/process/auth_logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="user-main">
