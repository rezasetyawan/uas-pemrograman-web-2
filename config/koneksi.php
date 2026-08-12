<?php
// mulai session jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// matikan laporan error mysqli otomatis
mysqli_report(MYSQLI_REPORT_OFF);

// konfigurasi koneksi database
$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$db   = 'db_simpan_barang';
$port = 3306;

// koneksi ke database mysql
$koneksi = mysqli_connect($host, $user, $pass, $db, $port);

// cek koneksi ke database
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// fungsi untuk cek status login user
function cek_login() {
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// fungsi helper untuk sanitasi output HTML (mencegah XSS)
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>