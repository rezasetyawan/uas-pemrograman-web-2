<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

$error = '';
$id_barang = isset($_GET['id']) ? intval($_GET['id']) : 0;

// get data barang berdasarkan id_barang
$query_get = "SELECT * FROM barang WHERE id_barang = ?";
$stmt_get  = mysqli_prepare($koneksi, $query_get);
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
    $kode_barang    = trim($_POST['kode_barang']);
    $nama_barang    = trim($_POST['nama_barang']);
    $nama_pemilik   = trim($_POST['nama_pemilik']);
    $kontak_pemilik = trim($_POST['kontak_pemilik']);
    $id_kategori    = intval($_POST['id_kategori']);
    $nomor_loker    = trim($_POST['nomor_loker']);
    $kondisi        = trim($_POST['kondisi']);
    $tanggal_masuk  = trim($_POST['tanggal_masuk']);
    $status         = trim($_POST['status']);

    // set tanggal_keluar berdasarkan status
    $tanggal_keluar = ($status === 'Diambil') ? (!empty($data['tanggal_keluar']) ? $data['tanggal_keluar'] : date('Y-m-d H:i:s')) : null;

    // validasi data input
    if (empty($kode_barang) || empty($nama_barang) || empty($nama_pemilik) || empty($kontak_pemilik) || $id_kategori <= 0 || empty($nomor_loker) || empty($tanggal_masuk)) {
        $error = "Harap isi semua kolom formulir yang wajib!";
    } else {
        // cek unik kode barang untuk data barang lain
        $cek_kode = mysqli_prepare($koneksi, "SELECT id_barang FROM barang WHERE kode_barang = ? AND id_barang != ?");
        mysqli_stmt_bind_param($cek_kode, "si", $kode_barang, $id_barang);
        mysqli_stmt_execute($cek_kode);
        $res_cek = mysqli_stmt_get_result($cek_kode);
        if (mysqli_num_rows($res_cek) > 0) {
            $error = "Kode titip '{$kode_barang}' sudah digunakan barang lain. Gunakan kode unik!";
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
                $error = "Gagal mengunggah foto baru ke server!";
            }
        }
    }

    // simpan perubahan ke database
    if (empty($error)) {
        $query = "UPDATE barang SET kode_barang = ?, nama_barang = ?, nama_pemilik = ?, kontak_pemilik = ?, id_kategori = ?, nomor_loker = ?, kondisi = ?, foto = ?, tanggal_masuk = ?, tanggal_keluar = ?, status = ? WHERE id_barang = ?";
        $stmt  = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssissssssi", $kode_barang, $nama_barang, $nama_pemilik, $kontak_pemilik, $id_kategori, $nomor_loker, $kondisi, $nama_foto, $tanggal_masuk, $tanggal_keluar, $status, $id_barang);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Data titipan barang berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Gagal memperbarui data di database!";
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
    <title>Edit Barang - Aplikasi Simpan Barang</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Aplikasi Simpan Barang</a>
    <div class="collapse navbar-collapse">
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
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Data Barang Simpanan</h5>
                </div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="barang_edit.php?id=<?= $id_barang; ?>" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label for="kode_barang" class="form-label">Kode Titip / Tiket</label>
                            <input type="text" name="kode_barang" id="kode_barang" class="form-control" value="<?= e($data['kode_barang']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="nama_barang" class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" id="nama_barang" class="form-control" value="<?= e($data['nama_barang']); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nama_pemilik" class="form-label">Nama Pemilik / Penitip</label>
                                <input type="text" name="nama_pemilik" id="nama_pemilik" class="form-control" value="<?= e($data['nama_pemilik']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="kontak_pemilik" class="form-label">No. HP / NIM Pemilik</label>
                                <input type="text" name="kontak_pemilik" id="kontak_pemilik" class="form-control" value="<?= e($data['kontak_pemilik']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_kategori" class="form-label">Kategori Barang</label>
                                <select name="id_kategori" id="id_kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while ($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                        <option value="<?= $kat['id_kategori']; ?>" <?= ($data['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                            <?= e($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nomor_loker" class="form-label">Nomor Loker / Rak</label>
                                <input type="text" name="nomor_loker" id="nomor_loker" class="form-control" value="<?= e($data['nomor_loker']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="kondisi" class="form-label">Kondisi Barang</label>
                                <select name="kondisi" id="kondisi" class="form-select" required>
                                    <option value="Baik" <?= ($data['kondisi'] == 'Baik') ? 'selected' : ''; ?>>Baik</option>
                                    <option value="Ada Lecet" <?= ($data['kondisi'] == 'Ada Lecet') ? 'selected' : ''; ?>>Ada Lecet</option>
                                    <option value="Rusak" <?= ($data['kondisi'] == 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="tanggal_masuk" class="form-label">Tanggal Dititipkan</label>
                                <input type="datetime-local" name="tanggal_masuk" id="tanggal_masuk" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($data['tanggal_masuk'])); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status Barang</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="Tersimpan" <?= ($data['status'] == 'Tersimpan') ? 'selected' : ''; ?>>Tersimpan</option>
                                    <option value="Diambil" <?= ($data['status'] == 'Diambil') ? 'selected' : ''; ?>>Diambil</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto Barang (Opsional)</label>
                            <?php if (!empty($data['foto']) && file_exists('uploads/' . $data['foto'])): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?= e($data['foto']); ?>" alt="Foto" width="80">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" name="update" class="btn btn-warning">Simpan Perubahan</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
