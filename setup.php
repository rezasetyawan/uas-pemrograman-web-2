<?php

// matikan laporan error mysqli otomatis
mysqli_report(MYSQLI_REPORT_OFF);

// ambil konfigurasi kredensial dari POST atau default
$host = $_POST['host'] ?? '127.0.0.1';
$user = $_POST['user'] ?? 'root';
$pass = $_POST['pass'] ?? 'root';
$port = intval($_POST['port'] ?? 3306);

// opsi argumen CLI
if (php_sapi_name() === 'cli') {
    $options = getopt("", ["host::", "user::", "pass::", "port::"]);
    if (isset($options['host'])) $host = $options['host'];
    if (isset($options['user'])) $user = $options['user'];
    if (isset($options['pass'])) $pass = $options['pass'];
    if (isset($options['port'])) $port = intval($options['port']);
}

$is_submitted = (php_sapi_name() === 'cli') || isset($_POST['submit_setup']);
$error_koneksi = '';
$logs = [];
$sukses = false;

// eksekusi proses setup database saat disubmit atau via CLI
if ($is_submitted) {

    // koneksi awal ke mysql server
    $koneksi = mysqli_connect($host, $user, $pass, null, $port);

    if (!$koneksi) {
        $error_koneksi = "Gagal terhubung ke MySQL. Error: " . mysqli_connect_error();
    } else {

        // buat database jika belum ada
        $query_db = "CREATE DATABASE IF NOT EXISTS `db_inventaris` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if (mysqli_query($koneksi, $query_db)) {
            $logs[] = "[SUKSES] Database 'db_inventaris' siap.";
        } else {
            $logs[] = "[GAGAL] Gagal membuat database: " . mysqli_error($koneksi);
        }

        // pilih database db_inventaris
        mysqli_select_db($koneksi, "db_inventaris");

        // buat tabel users
        $query_users = "CREATE TABLE IF NOT EXISTS `users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `nama_lengkap` VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($koneksi, $query_users) ? $logs[] = "[SUKSES] Tabel 'users' siap." : $logs[] = "[GAGAL] Tabel 'users': " . mysqli_error($koneksi);

        // buat tabel kategori
        $query_kategori = "CREATE TABLE IF NOT EXISTS `kategori` (
          `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
          `nama_kategori` VARCHAR(100) NOT NULL,
          `keterangan` VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($koneksi, $query_kategori) ? $logs[] = "[SUKSES] Tabel 'kategori' siap." : $logs[] = "[GAGAL] Tabel 'kategori': " . mysqli_error($koneksi);

        // buat tabel barang
        $query_barang = "CREATE TABLE IF NOT EXISTS `barang` (
          `id_barang` INT AUTO_INCREMENT PRIMARY KEY,
          `kode_barang` VARCHAR(30) NOT NULL UNIQUE,
          `nama_barang` VARCHAR(150) NOT NULL,
          `id_kategori` INT NOT NULL,
          `jumlah` INT NOT NULL DEFAULT 0,
          `satuan` VARCHAR(30) NOT NULL,
          `kondisi` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL DEFAULT 'Baik',
          `foto` VARCHAR(255) DEFAULT NULL,
          `tanggal_masuk` DATE NOT NULL,
          FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($koneksi, $query_barang) ? $logs[] = "[SUKSES] Tabel 'barang' siap." : $logs[] = "[GAGAL] Tabel 'barang': " . mysqli_error($koneksi);

        // seeding akun admin default
        $cek_user = mysqli_query($koneksi, "SELECT id FROM users WHERE username = 'admin'");
        if (mysqli_num_rows($cek_user) == 0) {
            $pass_hash = password_hash('admin123', PASSWORD_DEFAULT);
            mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap) VALUES ('admin', '$pass_hash', 'Administrator Inventaris')");
            $logs[] = "[SEEDING] User 'admin' dibuat (Password: admin123).";
        } else {
            $logs[] = "[INFO] User 'admin' sudah ada.";
        }

        // seeding data awal kategori
        $cek_kat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kategori");
        if (mysqli_fetch_assoc($cek_kat)['total'] == 0) {
            mysqli_query($koneksi, "INSERT INTO kategori (id_kategori, nama_kategori, keterangan) VALUES
                (1, 'Elektronik', 'Peralatan elektronik dan gadget kantor'),
                (2, 'Furniture', 'Meja, kursi, dan perlengkapan kayu/besi'),
                (3, 'Alat Tulis Kantor', 'Kertas, pena, dan perlengkapan ATK')");
            $logs[] = "[SEEDING] Data awal kategori ditambahkan.";
        } else {
            $logs[] = "[INFO] Data kategori sudah ada.";
        }

        // seeding data awal barang (20 items)
        $cek_brg = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang");
        if (mysqli_fetch_assoc($cek_brg)['total'] < 20) {

            mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=0");
            mysqli_query($koneksi, "TRUNCATE TABLE barang");
            mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=1");

            $query_seed_20 = "INSERT INTO barang (kode_barang, nama_barang, id_kategori, jumlah, satuan, kondisi, foto, tanggal_masuk) VALUES
                ('BRG-001', 'Laptop Asus Vivobook 14', 1, 10, 'Unit', 'Baik', 'default.png', '2026-01-15'),
                ('BRG-002', 'Proyektor Epson EB-X400', 1, 3, 'Unit', 'Baik', 'default.png', '2026-02-10'),
                ('BRG-003', 'Kursi Kerja Ergonomis', 2, 25, 'Buah', 'Baik', 'default.png', '2026-03-01'),
                ('BRG-004', 'Meja Rapat Kayu Jati', 2, 4, 'Buah', 'Rusak Ringan', 'default.png', '2026-03-12'),
                ('BRG-005', 'Printer HP LaserJet Pro', 1, 5, 'Unit', 'Baik', 'default.png', '2026-04-05'),
                ('BRG-006', 'Monitor Dell 24 Inch', 1, 8, 'Unit', 'Baik', 'default.png', '2026-04-10'),
                ('BRG-007', 'Keyboard Mechanical Logitech', 1, 15, 'Unit', 'Baik', 'default.png', '2026-04-15'),
                ('BRG-008', 'Mouse Wireless Lenovo', 1, 20, 'Unit', 'Baik', 'default.png', '2026-04-18'),
                ('BRG-009', 'Lemari Arsip Besi', 2, 6, 'Buah', 'Baik', 'default.png', '2026-04-20'),
                ('BRG-010', 'Papan Tulis Whiteboard 120x240', 3, 3, 'Buah', 'Baik', 'default.png', '2026-04-22'),
                ('BRG-011', 'Paper Shredder Krisbow', 1, 2, 'Unit', 'Rusak Ringan', 'default.png', '2026-04-25'),
                ('BRG-012', 'AC Split Panasonic 1.5 PK', 1, 4, 'Unit', 'Baik', 'default.png', '2026-05-01'),
                ('BRG-013', 'Kipas Angin Dinding Sekai', 1, 6, 'Unit', 'Rusak Berat', 'default.png', '2026-05-03'),
                ('BRG-014', 'Meja Kubikal Staff', 2, 12, 'Buah', 'Baik', 'default.png', '2026-05-05'),
                ('BRG-015', 'Sofa Tamu Kantor 3 Seater', 2, 2, 'Set', 'Baik', 'default.png', '2026-05-10'),
                ('BRG-016', 'Mesin Fotokopi Canon IR2006', 1, 1, 'Unit', 'Baik', 'default.png', '2026-05-12'),
                ('BRG-017', 'Stapler Besar Heavy Duty', 3, 5, 'Buah', 'Baik', 'default.png', '2026-05-15'),
                ('BRG-018', 'Peruncing Pensil Elektrik', 3, 4, 'Buah', 'Rusak Ringan', 'default.png', '2026-05-18'),
                ('BRG-019', 'Dispenser Air Galon Mito', 1, 3, 'Unit', 'Baik', 'default.png', '2026-05-20'),
                ('BRG-020', 'Filing Cabinet 4 Laci', 2, 5, 'Buah', 'Baik', 'default.png', '2026-05-22')";

            if (mysqli_query($koneksi, $query_seed_20)) {
                $logs[] = "[SEEDING] 20 Data barang awal berhasil disemaikan.";
            } else {
                $logs[] = "[GAGAL] Seeding barang: " . mysqli_error($koneksi);
            }
        } else {
            $logs[] = "[INFO] Data barang (20 items) sudah lengkap.";
        }

        // buat / perbarui file config/koneksi.php otomatis
        $config_code = "<?php\n"
            . "// mulai session jika belum berjalan\n"
            . "if (session_status() === PHP_SESSION_NONE) {\n"
            . "    session_start();\n"
            . "}\n\n"
            . "// matikan laporan error mysqli otomatis\n"
            . "mysqli_report(MYSQLI_REPORT_OFF);\n\n"
            . "// konfigurasi koneksi database\n"
            . "\$host = " . var_export($host, true) . ";\n"
            . "\$user = " . var_export($user, true) . ";\n"
            . "\$pass = " . var_export($pass, true) . ";\n"
            . "\$db   = 'db_inventaris';\n"
            . "\$port = " . var_export($port, true) . ";\n\n"
            . "// koneksi ke database mysql\n"
            . "\$koneksi = mysqli_connect(\$host, \$user, \$pass, \$db, \$port);\n\n"
            . "// cek koneksi ke database\n"
            . "if (!\$koneksi) {\n"
            . "    die(\"Koneksi ke database gagal: \" . mysqli_connect_error());\n"
            . "}\n\n"
            . "// fungsi untuk cek status login user\n"
            . "function cek_login() {\n"
            . "    if (!isset(\$_SESSION['login']) || \$_SESSION['login'] !== true) {\n"
            . "        header(\"Location: login.php\");\n"
            . "        exit;\n"
            . "    }\n"
            . "}\n\n"
            . "// fungsi helper untuk sanitasi output HTML (mencegah XSS)\n"
            . "function e(\$string) {\n"
            . "    return htmlspecialchars(\$string ?? '', ENT_QUOTES, 'UTF-8');\n"
            . "}\n"
            . "?>";

        file_put_contents('config/koneksi.php', $config_code);
        $logs[] = "[SUKSES] Konfigurasi 'config/koneksi.php' berhasil diperbarui.";
        $sukses = true;
        mysqli_close($koneksi);
    }
}

// output untuk eksekusi via mode CLI
if (php_sapi_name() === 'cli') {
    if (!empty($error_koneksi)) {
        echo "[ERROR] " . $error_koneksi . "\n";
        echo "Gunakan opsi kredensial: php setup.php --user=USERNAME --pass=PASSWORD --host=HOST --port=PORT\n";
        exit(1);
    } else {
        foreach ($logs as $log) {
            echo $log . "\n";
        }
        echo "[BERHASIL] Setup selesai dengan 20 data barang.\n";
        exit(0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database & Migrasi Project</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0">Setup Project - Database & Seeding (20 Data)</h5>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($error_koneksi)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Gagal Terhubung ke MySQL!</strong><br>
                            <?= htmlspecialchars($error_koneksi); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sukses): ?>
                        <div class="alert alert-success" role="alert">
                            <strong>Setup Berhasil!</strong> Database, tabel, dan 20 data awal telah disemaikan.
                        </div>
                        <div class="bg-dark text-light p-3 rounded mb-4 font-monospace" style="max-height: 250px; overflow-y: auto;">
                            <?php foreach ($logs as $log): ?>
                                <div class="mb-1"><?= htmlspecialchars($log); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">User: <strong>admin</strong> | Pass: <strong>admin123</strong></span>
                            <a href="login.php" class="btn btn-success">Buka Halaman Login</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Masukkan kredensial server MySQL Anda untuk menyemaikan 20 data barang dan migrasi database.</p>

                        <form action="setup.php" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="host" class="form-label font-semibold">Host MySQL</label>
                                    <input type="text" name="host" id="host" class="form-control" value="<?= htmlspecialchars($host); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="port" class="form-label">Port</label>
                                    <input type="number" name="port" id="port" class="form-control" value="<?= htmlspecialchars($port); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user" class="form-label">Username MySQL</label>
                                <input type="text" name="user" id="user" class="form-control" value="<?= htmlspecialchars($user); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="pass" class="form-label">Password MySQL</label>
                                <input type="password" name="pass" id="pass" class="form-control" placeholder="Kosongkan jika tanpa password">
                            </div>

                            <button type="submit" name="submit_setup" class="btn btn-primary w-100 py-2">
                                Jalankan Setup (20 Data Seeding)
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
