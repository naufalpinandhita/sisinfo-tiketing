<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
header('Location: home.php');
exit;
