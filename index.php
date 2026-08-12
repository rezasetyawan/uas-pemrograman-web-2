<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// limit data per halaman
$limit = 5;

// halaman dan offset
$halaman = isset($_GET['halaman']) ? max(1, intval($_GET['halaman'])) : 1;
$offset = ($halaman - 1) * $limit;

// get keyword pencarian dan filter tanggal
$keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$tgl_awal = isset($_GET['tgl_awal']) ? trim($_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim($_GET['tgl_akhir']) : '';

// validasi filter tanggal
$pesan_error = '';
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    if ($tgl_awal > $tgl_akhir) {
        $pesan_error = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir!';
    }
}

// escape string untuk keamanan query
$keyword_esc = mysqli_real_escape_string($koneksi, $keyword);
$tgl_awal_esc = mysqli_real_escape_string($koneksi, $tgl_awal);
$tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tgl_akhir);

// klausa where untuk pencarian & tanggal
$where = "WHERE 1=1";
if (!empty($keyword_esc)) {
    $where .= " AND (b.nama_barang LIKE '%$keyword_esc%' OR b.kode_barang LIKE '%$keyword_esc%' OR k.nama_kategori LIKE '%$keyword_esc%')";
}

if (empty($pesan_error)) {
    if (!empty($tgl_awal_esc) && !empty($tgl_akhir_esc)) {
        $where .= " AND b.tanggal_masuk BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
    } elseif (!empty($tgl_awal_esc)) {
        $where .= " AND b.tanggal_masuk >= '$tgl_awal_esc'";
    } elseif (!empty($tgl_akhir_esc)) {
        $where .= " AND b.tanggal_masuk <= '$tgl_akhir_esc'";
    }
}

// query total data barang
$query_count = "SELECT COUNT(*) as total FROM barang b JOIN kategori k ON b.id_kategori = k.id_kategori $where";
$res_count = mysqli_query($koneksi, $query_count);
$total_data = mysqli_fetch_assoc($res_count)['total'];

// query data barang dan kategori
$query = "SELECT b.*, k.nama_kategori
          FROM barang b
          JOIN kategori k ON b.id_kategori = k.id_kategori
          $where
          ORDER BY b.id_barang DESC
          LIMIT $limit OFFSET $offset";
$result_barang = mysqli_query($koneksi, $query);

// hitung total halaman
$total_halaman = ceil($total_data / $limit);

// susun parameter URL untuk pagination
$param_url = "";
if (!empty($keyword)) $param_url .= "&cari=" . urlencode($keyword);
if (!empty($tgl_awal) && empty($pesan_error)) $param_url .= "&tgl_awal=" . urlencode($tgl_awal);
if (!empty($tgl_akhir) && empty($pesan_error)) $param_url .= "&tgl_akhir=" . urlencode($tgl_akhir);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem Inventaris</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Aplikasi Inventaris</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="index.php">Data Barang</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="kategori.php">Data Kategori</a>
        </li>
      </ul>
      <div class="d-flex align-items-center text-white">
        <span class="me-3">User: <?= e($_SESSION['nama_lengkap']); ?></span>
        <a href="logout.php" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin logout?')">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="container my-4">

    <h3>Data Inventaris Barang</h3>
    <hr>

    <?php if (isset($_SESSION['pesan_sukses'])): ?>
        <div class="alert alert-success py-2">
            <?= e($_SESSION['pesan_sukses']); ?>
        </div>
        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert alert-warning py-2">
            <?= e($pesan_error); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body py-2 bg-light">
            <form action="index.php" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 fw-bold">Tanggal Awal</label>
                    <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= e($tgl_awal); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 fw-bold">Tanggal Akhir</label>
                    <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= e($tgl_akhir); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm mb-1 fw-bold">Kata Kunci</label>
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama/kode barang..." value="<?= e($keyword); ?>">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm flex-fill">Filter</button>
                    <?php if (!empty($keyword) || !empty($tgl_awal) || !empty($tgl_akhir)): ?>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3">
        <a href="barang_tambah.php" class="btn btn-primary btn-sm">+ Tambah Barang</a>
        <a href="export_pdf.php?<?= ltrim($param_url, '&'); ?>" target="_blank" class="btn btn-secondary btn-sm">Cetak PDF</a>
        <a href="export_excel.php?<?= ltrim($param_url, '&'); ?>" target="_blank" class="btn btn-success btn-sm">Export Excel</a>
    </div>

    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th class="text-center" width="50">No</th>
                <th class="text-center" width="80">Foto</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-center">Jumlah</th>
                <th>Kondisi</th>
                <th>Tanggal Masuk</th>
                <th class="text-center" width="130">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = $offset + 1;
            if (mysqli_num_rows($result_barang) > 0):
                while ($row = mysqli_fetch_assoc($result_barang)):
                    $foto_path = (!empty($row['foto']) && file_exists('uploads/' . $row['foto']))
                                ? 'uploads/' . $row['foto']
                                : 'https://via.placeholder.com/80?text=No+Img';
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td class="text-center">
                    <img src="<?= e($foto_path); ?>" alt="Foto" width="50" height="50" style="object-fit: cover;">
                </td>
                <td><?= e($row['kode_barang']); ?></td>
                <td><?= e($row['nama_barang']); ?></td>
                <td><?= e($row['nama_kategori']); ?></td>
                <td class="text-center"><?= e($row['jumlah']); ?> <?= e($row['satuan']); ?></td>
                <td><?= e($row['kondisi']); ?></td>
                <td><?= e($row['tanggal_masuk']); ?></td>
                <td class="text-center">
                    <a href="barang_edit.php?id=<?= $row['id_barang']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="barang_hapus.php?id=<?= $row['id_barang']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                </td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="9" class="text-center">Data tidak ditemukan.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_halaman > 1): ?>
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $halaman; ?> dari <?= $total_halaman; ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="index.php?halaman=<?= $halaman - 1; ?><?= $param_url; ?>">Sebelumnya</a>
            </li>
            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <li class="page-item <?= ($i == $halaman) ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?halaman=<?= $i; ?><?= $param_url; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                <a class="page-link" href="index.php?halaman=<?= $halaman + 1; ?><?= $param_url; ?>">Selanjutnya</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

