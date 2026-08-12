CREATE DATABASE IF NOT EXISTS `db_inventaris`;
USE `db_inventaris`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kategori` (
  `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `barang` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Inventaris');

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES
(1, 'Elektronik', 'Peralatan elektronik dan gadget kantor'),
(2, 'Furniture', 'Meja, kursi, dan perlengkapan kayu/besi'),
(3, 'Alat Tulis Kantor', 'Kertas, pena, dan perlengkapan ATK');

INSERT INTO `barang` (`id_barang`, `kode_barang`, `nama_barang`, `id_kategori`, `jumlah`, `satuan`, `kondisi`, `foto`, `tanggal_masuk`) VALUES
(1, 'BRG-001', 'Laptop Asus Vivobook 14', 1, 10, 'Unit', 'Baik', 'default.png', '2026-01-15'),
(2, 'BRG-002', 'Proyektor Epson EB-X400', 1, 3, 'Unit', 'Baik', 'default.png', '2026-02-10'),
(3, 'BRG-003', 'Kursi Kerja Ergonomis', 2, 25, 'Buah', 'Baik', 'default.png', '2026-03-01'),
(4, 'BRG-004', 'Meja Rapat Kayu Jati', 2, 4, 'Buah', 'Rusak Ringan', 'default.png', '2026-03-12'),
(5, 'BRG-005', 'Printer HP LaserJet Pro', 1, 5, 'Unit', 'Baik', 'default.png', '2026-04-05'),
(6, 'BRG-006', 'Monitor Dell 24 Inch', 1, 8, 'Unit', 'Baik', 'default.png', '2026-04-10'),
(7, 'BRG-007', 'Keyboard Mechanical Logitech', 1, 15, 'Unit', 'Baik', 'default.png', '2026-04-15'),
(8, 'BRG-008', 'Mouse Wireless Lenovo', 1, 20, 'Unit', 'Baik', 'default.png', '2026-04-18'),
(9, 'BRG-009', 'Lemari Arsip Besi', 2, 6, 'Buah', 'Baik', 'default.png', '2026-04-20'),
(10, 'BRG-010', 'Papan Tulis Whiteboard 120x240', 3, 3, 'Buah', 'Baik', 'default.png', '2026-04-22'),
(11, 'BRG-011', 'Paper Shredder Krisbow', 1, 2, 'Unit', 'Rusak Ringan', 'default.png', '2026-04-25'),
(12, 'BRG-012', 'AC Split Panasonic 1.5 PK', 1, 4, 'Unit', 'Baik', 'default.png', '2026-05-01'),
(13, 'BRG-013', 'Kipas Angin Dinding Sekai', 1, 6, 'Unit', 'Rusak Berat', 'default.png', '2026-05-03'),
(14, 'BRG-014', 'Meja Kubikal Staff', 2, 12, 'Buah', 'Baik', 'default.png', '2026-05-05'),
(15, 'BRG-015', 'Sofa Tamu Kantor 3 Seater', 2, 2, 'Set', 'Baik', 'default.png', '2026-05-10'),
(16, 'BRG-016', 'Mesin Fotokopi Canon IR2006', 1, 1, 'Unit', 'Baik', 'default.png', '2026-05-12'),
(17, 'BRG-017', 'Stapler Besar Heavy Duty', 3, 5, 'Buah', 'Baik', 'default.png', '2026-05-15'),
(18, 'BRG-018', 'Peruncing Pensil Elektrik', 3, 4, 'Buah', 'Rusak Ringan', 'default.png', '2026-05-18'),
(19, 'BRG-019', 'Dispenser Air Galon Mito', 1, 3, 'Unit', 'Baik', 'default.png', '2026-05-20'),
(20, 'BRG-020', 'Filing Cabinet 4 Laci', 2, 5, 'Buah', 'Baik', 'default.png', '2026-05-22');
