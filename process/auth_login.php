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

$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $error = urlencode("Terjadi kesalahan sistem.");
    header("Location: ../login.php?error=" . $error);
    exit();
}
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0 ){
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        $success = urlencode("Login Berhasil");

        if ($user['role'] == 'admin'){
            header("Location: ../admin/dashboard.php?success=" . $success);
        } else {
            header("Location: ../user/index.php?success=" . $success);
        }
    } else {
        $error = urlencode("Password Salah!");
        header("Location: ../login.php?error=" . $error);
    }
}
else {
    $error = urlencode("Akun tidak ditemukan, silakan daftar!");
    header("Location: ../login.php?error=" . $error);
    mysqli_stmt_close($stmt);
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();