DROP DATABASE IF EXISTS `db_simpan_barang`;
CREATE DATABASE `db_simpan_barang`;
USE `db_simpan_barang`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `kategori` (
  `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `barang` (
  `id_barang` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_barang` VARCHAR(30) NOT NULL UNIQUE,
  `nama_barang` VARCHAR(150) NOT NULL,
  `nama_pemilik` VARCHAR(100) NOT NULL,
  `kontak_pemilik` VARCHAR(30) NOT NULL,
  `id_kategori` INT NOT NULL,
  `nomor_loker` VARCHAR(30) NOT NULL,
  `kondisi` ENUM('Baik', 'Ada Lecet', 'Rusak') NOT NULL DEFAULT 'Baik',
  `foto` VARCHAR(255) DEFAULT NULL,
  `tanggal_masuk` DATETIME NOT NULL,
  `tanggal_keluar` DATETIME DEFAULT NULL,
  `status` ENUM('Tersimpan', 'Diambil') NOT NULL DEFAULT 'Tersimpan',
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Penitipan');

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES
(1, 'Elektronik', 'Laptop, smartphone, tablet, dan perangkat elektronik'),
(2, 'Tas & Pakaian', 'Tas ransel, jaket, helm, dan perlengkapan pribadi'),
(3, 'Dokumen & Berkas', 'Map ijazah, dokumen penting, dan surat-surat');

INSERT INTO `barang` (`id_barang`, `kode_barang`, `nama_barang`, `nama_pemilik`, `kontak_pemilik`, `id_kategori`, `nomor_loker`, `kondisi`, `foto`, `tanggal_masuk`, `tanggal_keluar`, `status`) VALUES
(1, 'TP-001', 'Helm NHK Fullface Hitam', 'Budi Santoso', '081234567891', 2, 'Loker A-01', 'Baik', NULL, '2026-08-10 08:30:00', NULL, 'Tersimpan'),
(2, 'TP-002', 'Tas Ransel Eiger 30L', 'Siti Aminah', '085712345678', 2, 'Loker A-05', 'Baik', NULL, '2026-08-11 09:15:00', NULL, 'Tersimpan'),
(3, 'TP-003', 'Laptop Asus ROG + Charger', 'Rizky Pratama', '081987654321', 1, 'Loker B-02', 'Baik', NULL, '2026-08-12 10:00:00', NULL, 'Tersimpan'),
(4, 'TP-004', 'Jaket Kulit Hitam', 'Deni Kurniawan', '082134567890', 2, 'Loker C-01', 'Ada Lecet', NULL, '2026-08-11 13:00:00', '2026-08-11 17:30:00', 'Diambil'),
(5, 'TP-005', 'Dokumen Map Ijazah & Berkas', 'Anita Wijaya', '083812345678', 3, 'Loker D-04', 'Baik', NULL, '2026-08-12 14:20:00', NULL, 'Tersimpan');
