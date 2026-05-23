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

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $error = urlencode("Email sudah digunakan!");
    header("Location: ../register.php?error=" . $error);
    exit();
}

$stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $hashedPassword]);

$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

$success = urlencode("Registrasi berhasil! Silakan login.");
header("Location: ../login.php?success=" . $success);
exit();
