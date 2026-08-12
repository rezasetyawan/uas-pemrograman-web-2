<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// get id barang dan aksi dari parameter GET
$id_barang = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action    = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($id_barang > 0 && in_array($action, ['diambil', 'tersimpan'])) {

    // proses update status barang
    if ($action === 'diambil') {
        $status_baru = 'Diambil';
        $tgl_keluar  = date('Y-m-d H:i:s');
        $query = "UPDATE barang SET status = ?, tanggal_keluar = ? WHERE id_barang = ?";
        $stmt  = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $status_baru, $tgl_keluar, $id_barang);
    } else {
        $status_baru = 'Tersimpan';
        $query = "UPDATE barang SET status = ?, tanggal_keluar = NULL WHERE id_barang = ?";
        $stmt  = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "si", $status_baru, $id_barang);
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['pesan_sukses'] = ($status_baru === 'Diambil') ? "Barang berhasil ditandai telah Diambil!" : "Status barang dikembalikan ke Tersimpan!";
    } else {
        $_SESSION['pesan_error'] = "Gagal memperbarui status barang!";
    }
    mysqli_stmt_close($stmt);
}

// redirect kembali ke halaman utama
header("Location: index.php");
exit;
?>
