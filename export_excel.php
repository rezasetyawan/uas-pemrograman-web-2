<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// header untuk download file sebagai excel (.xls)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Simpan_Barang_" . date('Ymd_His') . ".xls");

// get keyword pencarian, status & parameter filter tanggal dari GET
$keyword   = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$status    = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, trim($_GET['status'])) : '';
$tgl_awal  = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_awal'])) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_akhir'])) : '';

// normalkan tanggal jika tanggal awal lebih besar dari tanggal akhir
if (!empty($tgl_awal) && !empty($tgl_akhir) && $tgl_awal > $tgl_akhir) {
    $temp      = $tgl_awal;
    $tgl_awal  = $tgl_akhir;
    $tgl_akhir = $temp;
}

// susun WHERE berdasarkan filter pencarian, status, dan tanggal
$where = "WHERE 1=1";
if (!empty($keyword)) {
    $where .= " AND (b.nama_barang LIKE '%$keyword%' OR b.kode_barang LIKE '%$keyword%' OR b.nama_pemilik LIKE '%$keyword%' OR b.nomor_loker LIKE '%$keyword%' OR k.nama_kategori LIKE '%$keyword%')";
}
if (!empty($status) && in_array($status, ['Tersimpan', 'Diambil'])) {
    $where .= " AND b.status = '$status'";
}
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where .= " AND DATE(b.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif (!empty($tgl_awal)) {
    $where .= " AND DATE(b.tanggal_masuk) >= '$tgl_awal'";
} elseif (!empty($tgl_akhir)) {
    $where .= " AND DATE(b.tanggal_masuk) <= '$tgl_akhir'";
}

// query buat ambil data barang simpanan
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
    <title>laporan simpan barang</title>
</head>
<body>

    <h2>LAPORAN REKAP SIMPAN BARANG (PENITIPAN)</h2>
    <?php if (!empty($tgl_awal) && !empty($tgl_akhir)): ?>
        <p>Periode: <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?></p>
    <?php endif; ?>
    <p>Tanggal Unduh: <?php echo date('d-m-Y H:i:s'); ?></p>

    <table border="1">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>No</th>
                <th>Kode Titip</th>
                <th>Nama Barang</th>
                <th>Nama Pemilik</th>
                <th>Kontak Pemilik</th>
                <th>Kategori</th>
                <th>No. Loker</th>
                <th>Kondisi</th>
                <th>Tgl Dititipkan</th>
                <th>Tgl Diambil</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['nama_pemilik']; ?></td>
                <td><?php echo $row['kontak_pemilik']; ?></td>
                <td><?php echo $row['nama_kategori']; ?></td>
                <td><?php echo $row['nomor_loker']; ?></td>
                <td><?php echo $row['kondisi']; ?></td>
                <td align="center"><?php echo date('d-m-Y H:i', strtotime($row['tanggal_masuk'])); ?></td>
                <td align="center"><?php echo !empty($row['tanggal_keluar']) ? date('d-m-Y H:i', strtotime($row['tanggal_keluar'])) : '-'; ?></td>
                <td><?php echo $row['status']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
