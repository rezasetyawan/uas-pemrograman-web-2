<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

$error = '';

// get data kategori untuk dropdown
$result_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// proses simpan data barang baru
if (isset($_POST['simpan'])) {
    $kode_barang   = trim($_POST['kode_barang']);
    $nama_barang   = trim($_POST['nama_barang']);
    $id_kategori   = intval($_POST['id_kategori']);
    $jumlah        = intval($_POST['jumlah']);
    $satuan        = trim($_POST['satuan']);
    $kondisi       = trim($_POST['kondisi']);
    $tanggal_masuk = trim($_POST['tanggal_masuk']);

    // validasi data input
    if (empty($kode_barang) || empty($nama_barang) || $id_kategori <= 0 || empty($satuan) || empty($tanggal_masuk)) {
        $error = "Harap isi semua kolom formulir yang wajib!";
    } else {
        // cek apakah kode barang sudah ada di database
        $cek_kode = mysqli_prepare($koneksi, "SELECT id_barang FROM barang WHERE kode_barang = ?");
        mysqli_stmt_bind_param($cek_kode, "s", $kode_barang);
        mysqli_stmt_execute($cek_kode);
        $res_cek = mysqli_stmt_get_result($cek_kode);
        if (mysqli_num_rows($res_cek) > 0) {
            $error = "Kode barang '{$kode_barang}' sudah ada di sistem. Gunakan kode lain!";
        }
        mysqli_stmt_close($cek_kode);
    }

    // upload file foto jika diunggah
    $nama_foto_baru = null;
    if (empty($error) && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_size = $_FILES['foto']['size'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size = 2 * 1024 * 1024;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $mime_diizinkan = ['image/jpeg', 'image/png', 'image/webp'];

        // validasi format dan ukuran file foto
        if (!in_array($ext, $ekstensi_diizinkan) || !in_array($mime_type, $mime_diizinkan)) {
            $error = "Format foto tidak diizinkan! Hanya file gambar JPG, JPEG, PNG, dan WEBP yang diperbolehkan.";
        } elseif ($file_size > $max_size) {
            $error = "Ukuran foto terlalu besar! Maksimal ukuran file adalah 2 MB.";
        } else {
            $nama_foto_baru = time() . '_' . uniqid() . '.' . $ext;
            $tujuan_upload  = 'uploads/' . $nama_foto_baru;

            if (!move_uploaded_file($file_tmp, $tujuan_upload)) {
                $error = "Gagal mengunggah gambar ke server!";
            }
        }
    }

    // simpan data ke database jika validasi sukses
    if (empty($error)) {
        $query = "INSERT INTO barang (kode_barang, nama_barang, id_kategori, jumlah, satuan, kondisi, foto, tanggal_masuk)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssiissss", $kode_barang, $nama_barang, $id_kategori, $jumlah, $satuan, $kondisi, $nama_foto_baru, $tanggal_masuk);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Data barang berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Gagal menyimpan data ke database!";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang - Sistem Inventaris</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Aplikasi Inventaris</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="index.php">Data Barang</a></li>
        <li class="nav-item"><a class="nav-link" href="kategori.php">Data Kategori</a></li>
      </ul>
      <span class="text-white me-3">User: <?= e($_SESSION['nama_lengkap']); ?></span>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header bg-primary text-white">Form Tambah Data Barang</div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2"><?= e($error); ?></div>
                    <?php endif; ?>

                    <form action="barang_tambah.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" placeholder="Contoh: BRG-006" value="<?= e($_POST['kode_barang'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" placeholder="Nama barang" value="<?= e($_POST['nama_barang'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="id_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php while ($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                    <option value="<?= $kat['id_kategori']; ?>">
                                        <?= e($kat['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Stok)</label>
                                <input type="number" name="jumlah" class="form-control" min="0" value="<?= e($_POST['jumlah'] ?? '1'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="satuan" class="form-control" placeholder="Unit, Buah, Pcs" value="<?= e($_POST['satuan'] ?? 'Unit'); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kondisi</label>
                                <select name="kondisi" class="form-select" required>
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                    <option value="Rusak Berat">Rusak Berat</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk</label>
                                <input type="date" name="tanggal_masuk" class="form-control" value="<?= e($_POST['tanggal_masuk'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Barang</label>
                            <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <small class="text-muted">Format: JPG, JPEG, PNG, WEBP (Max 2MB)</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
