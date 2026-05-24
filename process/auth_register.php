<?php
session_start();
require_once '../config/helper.php';
require_once '../config/database.php';

validate_csrf();

if (empty($_POST['nama']) || empty($_POST['email']) || empty($_POST['password'])) {
    flash_message('error', 'Semua field harus diisi!');
    header('Location: ../register.php');
    exit();
}

$nama     = sanitize($_POST['nama']);
$email    = sanitize($_POST['email']);
$password = $_POST['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_message('error', 'Format email tidak valid!');
    header('Location: ../register.php');
    exit();
}

if (strlen($password) < 6) {
    flash_message('error', 'Password minimal 6 karakter!');
    header('Location: ../register.php');
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    flash_message('error', 'Email sudah digunakan!');
    header('Location: ../register.php');
    exit();
}

$stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'user')");
$stmt->execute([$nama, $email, $hashedPassword]);

flash_message('success', 'Registrasi berhasil! Silakan login.');
header('Location: ../login.php');
exit();
