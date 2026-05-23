<?php
session_start();
include '../config/database.php';

if (empty($_POST['email']) || empty($_POST['password'])) {
    $error = urlencode("Email dan password harus diisi!");
    header("Location: ../login.php?error=" . $error);
    exit();
}


$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['role'] = $user['role'];

        $success = urlencode("Login Berhasil");

        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php?success=" . $success);
        } else {
            header("Location: ../user/index.php?success=" . $success);
        }
        exit();
    } else {
        $error = urlencode("Password Salah!");
        header("Location: ../login.php?error=" . $error);
        exit();
    }
} else {
    $error = urlencode("Akun tidak ditemukan, silakan daftar!");
    header("Location: ../login.php?error=" . $error);
    exit();
}