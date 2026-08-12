<?php

// konfigurasi koneksi server mysql
$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$port = 3306;

// koneksi ke mysql server
$koneksi = mysqli_connect($host, $user, $pass, null, $port);

// cek koneksi mysql
if (!$koneksi) {
    die("Koneksi ke server MySQL gagal: " . mysqli_connect_error());
}

// baca isi file sql skema dan seeding data
$sql_file = __DIR__ . '/db_simpan_barang.sql';

if (!file_exists($sql_file)) {
    die("File 'db_simpan_barang.sql' tidak ditemukan!");
}

$sql = file_get_contents($sql_file);

// eksekusi query sql
if (mysqli_multi_query($koneksi, $sql)) {
    do {
        if ($result = mysqli_store_result($koneksi)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
    echo "Setup database 'db_simpan_barang' dan seeding data berhasil!";
} else {
    echo "Setup database gagal: " . mysqli_error($koneksi);
}

mysqli_close($koneksi);
?>
