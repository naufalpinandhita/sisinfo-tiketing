<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

validate_csrf();

if (empty($_POST['email']) || empty($_POST['password'])) {
    flash_message('error', 'Email dan password harus diisi!');
    header('Location: ../login.php');
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_message('error', 'Format email tidak valid!');
    header('Location: ../login.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['nama'] = $user['nama'];

    flash_message('success', 'Login berhasil!');

    if ($user['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/home.php');
    }
    exit();
} else {
    flash_message('error', 'Email atau password salah!');
    header('Location: ../login.php');
    exit();
}