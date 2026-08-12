<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

// limit data per halaman
$limit = 5;

// halaman dan offset
$halaman = isset($_GET['halaman']) ? max(1, intval($_GET['halaman'])) : 1;
$offset  = ($halaman - 1) * $limit;

// get keyword pencarian, status, dan filter tanggal
$keyword   = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$status    = isset($_GET['status']) ? trim($_GET['status']) : '';
$tgl_awal  = isset($_GET['tgl_awal']) ? trim($_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim($_GET['tgl_akhir']) : '';

// validasi filter tanggal
$pesan_error = '';
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    if ($tgl_awal > $tgl_akhir) {
        $pesan_error = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir!';
    }
}

// escape string untuk query
$keyword_esc   = mysqli_real_escape_string($koneksi, $keyword);
$status_esc    = mysqli_real_escape_string($koneksi, $status);
$tgl_awal_esc  = mysqli_real_escape_string($koneksi, $tgl_awal);
$tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tgl_akhir);

// where untuk pencarian, status & tanggal
$where = "WHERE 1=1";
if (!empty($keyword_esc)) {
    $where .= " AND (b.nama_barang LIKE '%$keyword_esc%' OR b.kode_barang LIKE '%$keyword_esc%' OR b.nama_pemilik LIKE '%$keyword_esc%' OR b.nomor_loker LIKE '%$keyword_esc%' OR k.nama_kategori LIKE '%$keyword_esc%')";
}

if (!empty($status_esc) && in_array($status_esc, ['Tersimpan', 'Diambil'])) {
    $where .= " AND b.status = '$status_esc'";
}

if (empty($pesan_error)) {
    if (!empty($tgl_awal_esc) && !empty($tgl_akhir_esc)) {
        $where .= " AND DATE(b.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
    } elseif (!empty($tgl_awal_esc)) {
        $where .= " AND DATE(b.tanggal_masuk) >= '$tgl_awal_esc'";
    } elseif (!empty($tgl_akhir_esc)) {
        $where .= " AND DATE(b.tanggal_masuk) <= '$tgl_akhir_esc'";
    }
}

// query total data barang penitipan
$query_count = "SELECT COUNT(*) as total FROM barang b JOIN kategori k ON b.id_kategori = k.id_kategori $where";
$res_count   = mysqli_query($koneksi, $query_count);
$total_data  = mysqli_fetch_assoc($res_count)['total'];

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

// susun parameter URL untuk pagination dan ekspor
$param_url = "";
if (!empty($keyword)) $param_url .= "&cari=" . urlencode($keyword);
if (!empty($status)) $param_url  .= "&status=" . urlencode($status);
if (!empty($tgl_awal) && empty($pesan_error)) $param_url .= "&tgl_awal=" . urlencode($tgl_awal);
if (!empty($tgl_akhir) && empty($pesan_error)) $param_url .= "&tgl_akhir=" . urlencode($tgl_akhir);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Simpan Barang - Aplikasi Simpan Barang</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Aplikasi Simpan Barang</a>
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

    <!-- notifikasi pesan sukses / error -->
    <?php if (isset($_SESSION['pesan_sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['pesan_sukses']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['pesan_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= $pesan_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h2 class="mb-3">Data Simpan Barang</h2>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="barang_tambah.php" class="btn btn-primary">Tambah Data Barang</a>
        <div>
            <a href="export_excel.php?v=1<?= $param_url; ?>" class="btn btn-success">Export Excel</a>
            <a href="export_pdf.php?v=1<?= $param_url; ?>" class="btn btn-danger" target="_blank">Export PDF</a>
        </div>
    </div>

    <!-- form filter pencarian & tanggal -->
    <form action="index.php" method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama barang, kode, pemilik..." value="<?= e($keyword); ?>">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">-- Semua Status --</option>
                <option value="Tersimpan" <?= ($status === 'Tersimpan') ? 'selected' : ''; ?>>Tersimpan</option>
                <option value="Diambil" <?= ($status === 'Diambil') ? 'selected' : ''; ?>>Diambil</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="tgl_awal" class="form-control" value="<?= e($tgl_awal); ?>" title="Tanggal awal">
        </div>
        <div class="col-md-2">
            <input type="date" name="tgl_akhir" class="form-control" value="<?= e($tgl_akhir); ?>" title="Tanggal akhir">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Cari</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <!-- tabel daftar barang simpanan -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>Kode Titip</th>
                    <th>Nama Barang</th>
                    <th>Nama Pemilik</th>
                    <th>No. HP / NIM</th>
                    <th>Kategori</th>
                    <th>No. Loker</th>
                    <th>Kondisi</th>
                    <th>Tgl Dititipkan</th>
                    <th>Status</th>
                    <th class="text-center text-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_barang) > 0): ?>
                    <?php $no = $offset + 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result_barang)): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])): ?>
                                    <img src="uploads/<?= e($row['foto']); ?>" alt="Foto" width="50">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['kode_barang']); ?></td>
                            <td><?= e($row['nama_barang']); ?></td>
                            <td><?= e($row['nama_pemilik']); ?></td>
                            <td><?= e($row['kontak_pemilik']); ?></td>
                            <td><?= e($row['nama_kategori']); ?></td>
                            <td><?= e($row['nomor_loker']); ?></td>
                            <td><?= e($row['kondisi']); ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($row['tanggal_masuk'])); ?></td>
                            <td class="text-center">
                                <?php if ($row['status'] === 'Tersimpan'): ?>
                                    <span class="badge bg-success">Tersimpan</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Diambil</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <?php if ($row['status'] === 'Tersimpan'): ?>
                                    <a href="barang_status.php?id=<?= $row['id_barang']; ?>&action=diambil" class="btn btn-sm btn-success" onclick="return confirm('Tandai barang telah Diambil?')">Ambil</a>
                                <?php else: ?>
                                    <a href="barang_status.php?id=<?= $row['id_barang']; ?>&action=tersimpan" class="btn btn-sm btn-secondary" onclick="return confirm('Kembalikan status barang?')">Reset</a>
                                <?php endif; ?>
                                <a href="barang_edit.php?id=<?= $row['id_barang']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="barang_hapus.php?id=<?= $row['id_barang']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center">Tidak ada data barang.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- pagination data -->
    <?php if ($total_halaman > 1): ?>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Halaman <?= $halaman; ?> dari <?= $total_halaman; ?></small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?halaman=<?= ($halaman - 1) . $param_url; ?>">Sebelumnya</a>
                </li>
                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                    <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="index.php?halaman=<?= $i . $param_url; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?halaman=<?= ($halaman + 1) . $param_url; ?>">Selanjutnya</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
