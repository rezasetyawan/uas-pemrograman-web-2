<?php

// panggil koneksi db
require_once 'config/koneksi.php';

// kalau sudah login redirect ke index
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

// proses login saat form disubmit
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {

        // ambil data user dari database
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            // cek password
            if (password_verify($password, $user['password'])) {

                // simpan data ke session
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                // redirect ke index
                header("Location: index.php");
                exit;
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Silakan isi username dan password!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Inventaris Barang</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-secondary bg-opacity-10 py-5">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0">LOGIN SYSTEM</h5>
                    <small>Sistem Informasi Inventaris Barang</small>
                </div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 small" role="alert">
                            <?= e($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        </div>

                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <div class="card-footer text-center text-muted small">
                    Default: admin / admin123
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
