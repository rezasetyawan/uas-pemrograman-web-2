<?php

// panggil koneksi db dan cek login
require_once 'config/koneksi.php';
cek_login();

$error = '';

// get data kategori untuk dropdown
$result_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// proses simpan data barang titipan baru
if (isset($_POST['simpan'])) {
    $kode_barang    = trim($_POST['kode_barang']);
    $nama_barang    = trim($_POST['nama_barang']);
    $nama_pemilik   = trim($_POST['nama_pemilik']);
    $kontak_pemilik = trim($_POST['kontak_pemilik']);
    $id_kategori    = intval($_POST['id_kategori']);
    $nomor_loker    = trim($_POST['nomor_loker']);
    $kondisi        = trim($_POST['kondisi']);
    $tanggal_masuk  = trim($_POST['tanggal_masuk']);

    // validasi data input
    if (empty($kode_barang) || empty($nama_barang) || empty($nama_pemilik) || empty($kontak_pemilik) || $id_kategori <= 0 || empty($nomor_loker) || empty($tanggal_masuk)) {
        $error = "Harap isi semua kolom formulir yang wajib!";
    } else {
        // cek apakah kode barang sudah ada di database
        $cek_kode = mysqli_prepare($koneksi, "SELECT id_barang FROM barang WHERE kode_barang = ?");
        mysqli_stmt_bind_param($cek_kode, "s", $kode_barang);
        mysqli_stmt_execute($cek_kode);
        $res_cek = mysqli_stmt_get_result($cek_kode);
        if (mysqli_num_rows($res_cek) > 0) {
            $error = "Kode titip '{$kode_barang}' sudah ada di sistem. Gunakan kode lain!";
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
        $query = "INSERT INTO barang (kode_barang, nama_barang, nama_pemilik, kontak_pemilik, id_kategori, nomor_loker, kondisi, foto, tanggal_masuk, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Tersimpan')";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssissss", $kode_barang, $nama_barang, $nama_pemilik, $kontak_pemilik, $id_kategori, $nomor_loker, $kondisi, $nama_foto_baru, $tanggal_masuk);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Data barang simpanan berhasil ditambahkan!";
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
    <title>Tambah Barang - Aplikasi Simpan Barang</title>

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
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Tambah Data Barang Simpanan</h5>
                </div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="barang_tambah.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label for="kode_barang" class="form-label">Kode Titip / Tiket</label>
                            <input type="text" name="kode_barang" id="kode_barang" class="form-control" placeholder="Contoh: TP-006" value="<?= e($_POST['kode_barang'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="nama_barang" class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" id="nama_barang" class="form-control" placeholder="Contoh: Helm KYT Fullface Hitam" value="<?= e($_POST['nama_barang'] ?? ''); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nama_pemilik" class="form-label">Nama Pemilik / Penitip</label>
                                <input type="text" name="nama_pemilik" id="nama_pemilik" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= e($_POST['nama_pemilik'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="kontak_pemilik" class="form-label">No. HP / NIM Pemilik</label>
                                <input type="text" name="kontak_pemilik" id="kontak_pemilik" class="form-control" placeholder="Contoh: 08123456789" value="<?= e($_POST['kontak_pemilik'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_kategori" class="form-label">Kategori Barang</label>
                                <select name="id_kategori" id="id_kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while ($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                        <option value="<?= $kat['id_kategori']; ?>" <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                            <?= e($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nomor_loker" class="form-label">Nomor Loker / Rak</label>
                                <input type="text" name="nomor_loker" id="nomor_loker" class="form-control" placeholder="Contoh: Loker A-02" value="<?= e($_POST['nomor_loker'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="kondisi" class="form-label">Kondisi Barang</label>
                                <select name="kondisi" id="kondisi" class="form-select" required>
                                    <option value="Baik" <?= (isset($_POST['kondisi']) && $_POST['kondisi'] == 'Baik') ? 'selected' : ''; ?>>Baik</option>
                                    <option value="Ada Lecet" <?= (isset($_POST['kondisi']) && $_POST['kondisi'] == 'Ada Lecet') ? 'selected' : ''; ?>>Ada Lecet</option>
                                    <option value="Rusak" <?= (isset($_POST['kondisi']) && $_POST['kondisi'] == 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_masuk" class="form-label">Tanggal Dititipkan</label>
                                <input type="datetime-local" name="tanggal_masuk" id="tanggal_masuk" class="form-control" value="<?= e($_POST['tanggal_masuk'] ?? date('Y-m-d\TH:i')); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto Barang (Opsional)</label>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
