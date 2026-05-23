<?php
session_start();
include '../config/database.php';

if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
    $error = urlencode("Semua field harus diisi!");
    header("Location: ../register.php?error=" . $error);
    exit();
}

$username = $_POST['username'];
$email    = $_POST['email'];
$password = $_POST['password'];

$checkQuery = "SELECT * FROM users WHERE email = ?";
$checkStmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($checkStmt, 's', $email);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    $error = urlencode("Email sudah digunakan!");
    header("Location: ../register.php?error=" . $error);
    mysqli_stmt_close($checkStmt);
    exit();
}
mysqli_stmt_close($checkStmt);

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users (nama, email, password) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPassword);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    $success = urlencode("Registrasi berhasil! Silakan login.");
    header("Location: ../login.php?success=" . $success);
} else {
    $error = urlencode("Registrasi gagal, silakan coba lagi.");
    header("Location: ../register.php?error=" . $error);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();
