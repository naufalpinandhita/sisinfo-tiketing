<?php
include '../database.php';
session_start();
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

session_destroy();
header("Location: ../index.php?logout+successful");
exit;