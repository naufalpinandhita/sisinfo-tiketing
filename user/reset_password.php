<?php
include '../../config/database.php';
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php?login=failed");
    exit;
}

if (isset($_POST['submit'])){
    $username = $_SESSION['username'];
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $result = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($result) == 0) {
        $error = "Username tidak ditemukan!";
    } elseif ($new_pass != $confirm_pass) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $hash = md5($old_pass);
        $user = mysqli_fetch_assoc($result);
        if ($user['password'] != $hash) {
            $error = "Password lama salah!";
        } else {
            $hash_new = md5($new_pass);
            mysqli_query($koneksi, "UPDATE users SET password = '$hash_new' WHERE username = '$username'");
            $success = "Password berhasil direset. Silakan login kembali."; // Hapus sesi dan arahkan ke halaman login
            session_destroy();
            header("Location: ../login.php?success=" . urlencode($success));
            exit;
        }
    }
}

$error = isset($_GET['error']) ? $_GET['error'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/style.css">
    <title>Reset Password</title>
</head>

<body>
<div class="container mt-5">
        <h1 class="mb-4">Reset Password</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="mb-3">
                <label for="old_password" class="form-label">Old Password</label>
                <input type="password" class="form-control" id="old_password" name="old_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" name="submit" class="btn btn-secondary">Reset Password</button>
            <a href="../../index.php" class="btn btn-secondary text-white" style="text-decoration: none;">Kembali</a>
        </form>
    </div>
</body>

</html>