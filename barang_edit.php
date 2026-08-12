<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

$error = '';
$id_barang = isset($_GET['id']) ? intval($_GET['id']) : 0;

// get data barang berdasarkan id_barang
$query_get = "SELECT * FROM barang WHERE id_barang = ?";
$stmt_get = mysqli_prepare($koneksi, $query_get);
mysqli_stmt_bind_param($stmt_get, "i", $id_barang);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$data = mysqli_fetch_assoc($result_get);
mysqli_stmt_close($stmt_get);

if (!$data) {
    header("Location: index.php");
    exit;
}

// get data kategori untuk dropdown
$result_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// proses update data barang saat form disubmit
if (isset($_POST['update'])) {
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
        // cek unik kode barang untuk data barang lain
        $cek_kode = mysqli_prepare($koneksi, "SELECT id_barang FROM barang WHERE kode_barang = ? AND id_barang != ?");
        mysqli_stmt_bind_param($cek_kode, "si", $kode_barang, $id_barang);
        mysqli_stmt_execute($cek_kode);
        $res_cek = mysqli_stmt_get_result($cek_kode);
        if (mysqli_num_rows($res_cek) > 0) {
            $error = "Kode barang '{$kode_barang}' sudah digunakan barang lain. Gunakan kode unik!";
        }
        mysqli_stmt_close($cek_kode);
    }

    $nama_foto = $data['foto'];

    // proses ganti foto barang jika ada foto baru diunggah
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

        // validasi format dan ukuran file foto baru
        if (!in_array($ext, $ekstensi_diizinkan) || !in_array($mime_type, $mime_diizinkan)) {
            $error = "Format foto tidak valid! Hanya file gambar JPG, JPEG, PNG, dan WEBP yang diperbolehkan.";
        } elseif ($file_size > $max_size) {
            $error = "Ukuran foto terlalu besar! Maksimal 2 MB.";
        } else {
            $nama_foto_baru = time() . '_' . uniqid() . '.' . $ext;
            $tujuan_upload  = 'uploads/' . $nama_foto_baru;

            if (move_uploaded_file($file_tmp, $tujuan_upload)) {
                // hapus foto lama jika ada
                if (!empty($data['foto']) && file_exists('uploads/' . $data['foto'])) {
                    unlink('uploads/' . $data['foto']);
                }
                $nama_foto = $nama_foto_baru;
            } else {
                $error = "Gagal mengunggah foto baru!";
            }
        }
    }

    // update data di database
    if (empty($error)) {
        $query_upd = "UPDATE barang SET kode_barang = ?, nama_barang = ?, id_kategori = ?, jumlah = ?, satuan = ?, kondisi = ?, foto = ?, tanggal_masuk = ? WHERE id_barang = ?";
        $stmt_upd = mysqli_prepare($koneksi, $query_upd);
        mysqli_stmt_bind_param($stmt_upd, "ssiissssi", $kode_barang, $nama_barang, $id_kategori, $jumlah, $satuan, $kondisi, $nama_foto, $tanggal_masuk, $id_barang);

        if (mysqli_stmt_execute($stmt_upd)) {
            $_SESSION['pesan_sukses'] = "Data barang berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Gagal memperbarui data di database!";
        }
        mysqli_stmt_close($stmt_upd);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - Sistem Inventaris</title>

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
                <div class="card-header bg-warning text-dark font-semibold">Form Edit Data Barang</div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2"><?= e($error); ?></div>
                    <?php endif; ?>

                    <form action="barang_edit.php?id=<?= $id_barang; ?>" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" value="<?= e($data['kode_barang']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" value="<?= e($data['nama_barang']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="id_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php while ($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                    <option value="<?= $kat['id_kategori']; ?>" <?= ($data['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                        <?= e($kat['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Stok)</label>
                                <input type="number" name="jumlah" class="form-control" min="0" value="<?= e($data['jumlah']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="satuan" class="form-control" value="<?= e($data['satuan']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kondisi</label>
                                <select name="kondisi" class="form-select" required>
                                    <option value="Baik" <?= ($data['kondisi'] === 'Baik') ? 'selected' : ''; ?>>Baik</option>
                                    <option value="Rusak Ringan" <?= ($data['kondisi'] === 'Rusak Ringan') ? 'selected' : ''; ?>>Rusak Ringan</option>
                                    <option value="Rusak Berat" <?= ($data['kondisi'] === 'Rusak Berat') ? 'selected' : ''; ?>>Rusak Berat</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk</label>
                                <input type="date" name="tanggal_masuk" class="form-control" value="<?= e($data['tanggal_masuk']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ganti Foto Barang (Opsional)</label>
                            <?php if (!empty($data['foto']) && file_exists('uploads/' . $data['foto'])): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?= e($data['foto']); ?>" alt="Foto" width="80" class="border">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <small class="text-muted">Kosongkan jika tidak diganti. Format: JPG, JPEG, PNG, WEBP (Max 2MB)</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="update" class="btn btn-warning">Update Data</button>
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
