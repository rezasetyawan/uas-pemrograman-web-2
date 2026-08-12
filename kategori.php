<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

$pesan_sukses = '';
$pesan_error = '';

// proses tambah kategori
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $keterangan    = trim($_POST['keterangan']);

    if (!empty($nama_kategori)) {
        $query = "INSERT INTO kategori (nama_kategori, keterangan) VALUES (?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ss", $nama_kategori, $keterangan);

        if (mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan kategori.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $pesan_error = "Nama kategori wajib diisi!";
    }
}

// proses edit kategori
if (isset($_POST['edit_kategori'])) {
    $id_kategori   = intval($_POST['id_kategori']);
    $nama_kategori = trim($_POST['nama_kategori']);
    $keterangan    = trim($_POST['keterangan']);

    if (!empty($nama_kategori) && $id_kategori > 0) {
        $query = "UPDATE kategori SET nama_kategori = ?, keterangan = ? WHERE id_kategori = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $nama_kategori, $keterangan, $id_kategori);

        if (mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui kategori.";
        }
        mysqli_stmt_close($stmt);
    }
}

// proses hapus kategori
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    if ($id_hapus > 0) {
        $query = "DELETE FROM kategori WHERE id_kategori = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_hapus);

        if (mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus kategori.";
        }
        mysqli_stmt_close($stmt);
    }
}

// pagination kategori
$limit = 5;
$halaman = isset($_GET['halaman']) ? max(1, intval($_GET['halaman'])) : 1;
$offset = ($halaman - 1) * $limit;

// hitung total data
$res_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kategori");
$total_data = mysqli_fetch_assoc($res_count)['total'];
$total_halaman = ceil($total_data / $limit);

// query data kategori
$stmt = mysqli_prepare($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$result_kategori = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Sistem Inventaris</title>

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
          <a class="nav-link" href="index.php">Data Barang</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="kategori.php">Data Kategori</a>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Data Kategori Barang</h3>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Kategori</button>
    </div>
    <hr>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success py-2"><?= e($pesan_sukses); ?></div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert alert-danger py-2"><?= e($pesan_error); ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th width="50" class="text-center">No</th>
                <th>Nama Kategori</th>
                <th>Keterangan</th>
                <th width="150" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = $offset + 1;
            if (mysqli_num_rows($result_kategori) > 0):
                while ($kat = mysqli_fetch_assoc($result_kategori)):
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= e($kat['nama_kategori']); ?></td>
                <td><?= e($kat['keterangan']); ?></td>
                <td class="text-center">
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $kat['id_kategori']; ?>">Edit</button>
                    <a href="kategori.php?hapus=<?= $kat['id_kategori']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
                </td>
            </tr>

            <div class="modal fade" id="modalEdit<?= $kat['id_kategori']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form action="kategori.php" method="POST">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit Kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="id_kategori" value="<?= $kat['id_kategori']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" value="<?= e($kat['nama_kategori']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" value="<?= e($kat['keterangan']); ?>">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_kategori" class="btn btn-primary">Simpan</button>
                      </div>
                  </form>
                </div>
              </div>
            </div>

            <?php
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="4" class="text-center">Belum ada data kategori.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_halaman > 1): ?>
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $halaman; ?> dari <?= $total_halaman; ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="kategori.php?halaman=<?= $halaman - 1; ?>">Sebelumnya</a>
            </li>
            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <li class="page-item <?= ($i == $halaman) ? 'active' : ''; ?>">
                    <a class="page-link" href="kategori.php?halaman=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                <a class="page-link" href="kategori.php?halaman=<?= $halaman + 1; ?>">Selanjutnya</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="kategori.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Kategori Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Elektronik" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" placeholder="Keterangan kategori">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="tambah_kategori" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
