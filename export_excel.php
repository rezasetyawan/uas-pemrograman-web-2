<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// header untuk download file sebagai excel (.xls)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Inventaris_Barang_" . date('Ymd_His') . ".xls");

// get keyword pencarian & parameter filter tanggal dari GET
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$tgl_awal = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_awal'])) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_akhir'])) : '';

// normalkan tanggal jika tanggal awal lebih besar dari tanggal akhir
if (!empty($tgl_awal) && !empty($tgl_akhir) && $tgl_awal > $tgl_akhir) {
    $temp = $tgl_awal;
    $tgl_awal = $tgl_akhir;
    $tgl_akhir = $temp;
}

// susun klausa WHERE berdasarkan filter pencarian dan tanggal
$where = "WHERE 1=1";
if (!empty($keyword)) {
    $where .= " AND (b.nama_barang LIKE '%$keyword%' OR b.kode_barang LIKE '%$keyword%' OR k.nama_kategori LIKE '%$keyword%')";
}
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where .= " AND b.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif (!empty($tgl_awal)) {
    $where .= " AND b.tanggal_masuk >= '$tgl_awal'";
} elseif (!empty($tgl_akhir)) {
    $where .= " AND b.tanggal_masuk <= '$tgl_akhir'";
}

// query buat ambil data barang
$query = "SELECT b.*, k.nama_kategori
          FROM barang b
          JOIN kategori k ON b.id_kategori = k.id_kategori
          $where
          ORDER BY b.kode_barang ASC";

$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>laporan inventaris barang</title>
</head>
<body>

    <h2>LAPORAN DATA INVENTARIS BARANG</h2>
    <?php if (!empty($tgl_awal) && !empty($tgl_akhir)): ?>
        <p>Periode: <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?></p>
    <?php endif; ?>
    <p>Tanggal Unduh: <?php echo date('d-m-Y H:i:s'); ?></p>

    <table border="1">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Kondisi</th>
                <th>Tanggal Masuk</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_stok = 0;
            while ($row = mysqli_fetch_assoc($result)) :
                $total_stok += $row['jumlah'];
            ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['nama_kategori']; ?></td>
                <td align="right"><?php echo $row['jumlah']; ?></td>
                <td><?php echo $row['satuan']; ?></td>
                <td><?php echo $row['kondisi']; ?></td>
                <td align="center"><?php echo date('d-m-Y', strtotime($row['tanggal_masuk'])); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="font-weight: bold;">
                <td colspan="4" align="right">TOTAL STOK</td>
                <td align="right"><?php echo $total_stok; ?></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

</body>
</html>

