<?php
include '../config/database.php';
session_start();
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$success = "Berhasil Logout";
session_destroy();
header("Location: ../index.php?success=" . $success);
exit;