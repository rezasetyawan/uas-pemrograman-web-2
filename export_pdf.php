<?php

// panggil koneksi db, dompdf, dan cek login
require_once 'config/koneksi.php';
require_once 'vendor/autoload.php';
cek_login();

use Dompdf\Dompdf;

// get keyword pencarian, status & parameter filter tanggal
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

// susun filter WHERE
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

// query buat ambil data barang
$query = "SELECT b.*, k.nama_kategori
          FROM barang b
          JOIN kategori k ON b.id_kategori = k.id_kategori
          $where
          ORDER BY b.kode_barang ASC";

$result = mysqli_query($koneksi, $query);

// tampung html laporan ke buffer
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>laporan data simpan barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 5px;
        }
        th {
            background-color: #f2f2f2;
        }
        .ttd {
            margin-top: 30px;
            float: right;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h3 style="margin: 0;">SISTEM INFORMASI SIMPAN BARANG (PENITIPAN)</h3>
        <p style="margin: 3px 0;">
            LAPORAN DATA BARANG SIMPANAN
            <?php if (!empty($tgl_awal) && !empty($tgl_akhir)): ?>
                (PERIODE: <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?>)
            <?php else: ?>
                KESELURUHAN
            <?php endif; ?>
        </p>
        <small>Tanggal Cetak: <?php echo date('d-m-Y H:i:s'); ?> | Petugas: <?php echo $_SESSION['nama_lengkap']; ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Kode Titip</th>
                <th>Nama Barang</th>
                <th>Pemilik / Kontak</th>
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
            if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['nama_pemilik']; ?><br><small><?php echo $row['kontak_pemilik']; ?></small></td>
                <td><?php echo $row['nama_kategori']; ?></td>
                <td class="text-center"><?php echo $row['nomor_loker']; ?></td>
                <td class="text-center"><?php echo $row['kondisi']; ?></td>
                <td class="text-center"><?php echo date('d-m-Y H:i', strtotime($row['tanggal_masuk'])); ?></td>
                <td class="text-center"><?php echo !empty($row['tanggal_keluar']) ? date('d-m-Y H:i', strtotime($row['tanggal_keluar'])) : '-'; ?></td>
                <td class="text-center"><?php echo $row['status']; ?></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data barang simpanan yang tersedia.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd">
        <p style="margin-bottom: 50px;">Mengetahui,<br><b>Petugas Penitipan</b></p>
        <p><b><u><?php echo $_SESSION['nama_lengkap']; ?></u></b><br>Administrator</p>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();

// buat pdf pakai dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// tampilkan pdf di browser
$dompdf->stream("Laporan_Simpan_Barang_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);
exit;
?>
