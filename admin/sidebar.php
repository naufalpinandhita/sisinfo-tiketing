<?php
if (!isset($active_menu)) $active_menu = '';
?>
        <aside class="admin-sidebar" id="adminSidebar">
            <nav class="nav flex-column py-2">
                <a class="nav-link <?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link <?php echo $active_menu === 'venue' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/venue/index.php">
                    <i class="bi bi-geo-alt"></i> Venue
                </a>
                <a class="nav-link <?php echo $active_menu === 'event' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/event/index.php">
                    <i class="bi bi-calendar-event"></i> Event
                </a>
                <a class="nav-link <?php echo $active_menu === 'tiket' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/tiket/index.php">
                    <i class="bi bi-ticket"></i> Tiket
                </a>
                <a class="nav-link <?php echo $active_menu === 'voucher' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/voucher/index.php">
                    <i class="bi bi-tag"></i> Voucher
                </a>
                <a class="nav-link <?php echo $active_menu === 'checkin' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/checkin.php">
                    <i class="bi bi-qr-code-scan"></i> Check-in
                </a>
                <a class="nav-link <?php echo $active_menu === 'laporan' ? 'active' : ''; ?>" href="/sisinfo-tiketing/admin/laporan.php">
                    <i class="bi bi-file-earmark-text"></i> Laporan
                </a>
                <div class="mt-auto border-top pt-2">
                    <a class="nav-link text-danger" href="/sisinfo-tiketing/process/auth_logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>
        <main class="admin-content">
