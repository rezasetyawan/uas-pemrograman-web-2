<?php

// panggil koneksi db, dompdf, dan cek login
require_once 'config/koneksi.php';
require_once 'vendor/autoload.php';
cek_login();

use Dompdf\Dompdf;

// get keyword pencarian & parameter filter tanggal
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$tgl_awal = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_awal'])) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_akhir'])) : '';

// normalkan tanggal jika tanggal awal lebih besar dari tanggal akhir
if (!empty($tgl_awal) && !empty($tgl_akhir) && $tgl_awal > $tgl_akhir) {
    $temp = $tgl_awal;
    $tgl_awal = $tgl_akhir;
    $tgl_akhir = $temp;
}

// susun filter klausa WHERE
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

// tampung html laporan ke buffer
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>laporan data inventaris barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            padding: 6px;
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
        <h3 style="margin: 0;">SISTEM INFORMASI INVENTARIS BARANG</h3>
        <p style="margin: 3px 0;">
            LAPORAN DATA INVENTARIS
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
                <th style="width: 30px;">No</th>
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
            if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    $total_stok += $row['jumlah'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['nama_kategori']; ?></td>
                <td class="text-center"><?php echo $row['jumlah']; ?></td>
                <td><?php echo $row['satuan']; ?></td>
                <td><?php echo $row['kondisi']; ?></td>
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['tanggal_masuk'])); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="4" class="text-right">TOTAL SELURUH BARANG:</td>
                <td class="text-center"><?php echo $total_stok; ?></td>
                <td colspan="3"></td>
            </tr>
            <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">Tidak ada data barang yang tersedia.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd">
        <p style="margin-bottom: 50px;">Mengetahui,<br><b>Petugas Inventaris</b></p>
        <p><b><u><?php echo $_SESSION['nama_lengkap']; ?></u></b><br>Administrator</p>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();

// buat pdf pakai dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// tampilkan pdf di browser
$dompdf->stream("Laporan_Inventaris_Barang_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);
exit;

