<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// get id barang dari parameter GET
$id_barang = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_barang > 0) {

    // mengambil data foto barang sebelum dihapus
    $query_foto = "SELECT foto FROM barang WHERE id_barang = ?";
    $stmt_foto = mysqli_prepare($koneksi, $query_foto);
    mysqli_stmt_bind_param($stmt_foto, "i", $id_barang);
    mysqli_stmt_execute($stmt_foto);
    $res_foto = mysqli_stmt_get_result($stmt_foto);

    if ($row = mysqli_fetch_assoc($res_foto)) {

        // hapus file foto jika ada
        if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) {
            unlink('uploads/' . $row['foto']);
        }
    }
    mysqli_stmt_close($stmt_foto);

    // hapus data barang dari database
    $query_del = "DELETE FROM barang WHERE id_barang = ?";
    $stmt_del = mysqli_prepare($koneksi, $query_del);
    mysqli_stmt_bind_param($stmt_del, "i", $id_barang);

    if (mysqli_stmt_execute($stmt_del)) {
        $_SESSION['pesan_sukses'] = "Data barang berhasil dihapus!";
    } else {
        $_SESSION['pesan_error'] = "Gagal menghapus data barang!";
    }
    mysqli_stmt_close($stmt_del);
}

// redirect ke index
header("Location: index.php");
exit;
?>
