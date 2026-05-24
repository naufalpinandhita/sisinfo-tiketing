<?php
if (!isset($page_title)) $page_title = 'Admin Panel';
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
    <nav class="navbar navbar-expand admin-navbar sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-md-none me-2" id="sidebarToggle" type="button">
                <i class="bi bi-list fs-lg"></i>
            </button>
            <a class="navbar-brand" href="/sisinfo-tiketing/admin/dashboard.php">
                <i class="bi bi-ticket-perforated-fill me-2"></i>Event Ticketing
            </a>
            <div class="ms-auto">
                <span class="nav-link d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?>
                </span>
            </div>
        </div>
    </nav>
    <div class="admin-wrapper">
