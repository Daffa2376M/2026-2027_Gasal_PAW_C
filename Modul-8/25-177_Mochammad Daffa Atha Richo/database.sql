-- Database Schema & Seed Data for Tugas Praktikum Modul 8 PAW
-- Database: tp8_penjualan (MariaDB 11 / MySQL 8, InnoDB, utf8mb4)

CREATE DATABASE IF NOT EXISTS tp8_penjualan
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE tp8_penjualan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pembayaran;
DROP TABLE IF EXISTS transaksi_detail;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS pelanggan;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE supplier (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(100) NOT NULL,
  telp VARCHAR(20) NOT NULL,
  alamat TEXT NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pelanggan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L', 'P') NOT NULL,
  telp VARCHAR(20) NOT NULL,
  alamat TEXT NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE barang (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_barang VARCHAR(20) NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  supplier_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_barang_kode (kode_barang),
  KEY idx_barang_supplier (supplier_id),
  CONSTRAINT fk_barang_supplier
    FOREIGN KEY (supplier_id) REFERENCES supplier(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user` (
  id_user INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(30) NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(50) NOT NULL,
  alamat VARCHAR(150) NOT NULL,
  hp VARCHAR(20) NOT NULL,
  level TINYINT NOT NULL COMMENT '1=Owner, 2=Kasir',
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_user_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transaksi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  waktu_transaksi DATE NOT NULL,
  keterangan TEXT NULL,
  total INT NOT NULL DEFAULT 0,
  pelanggan_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_transaksi_waktu (waktu_transaksi),
  KEY idx_transaksi_pelanggan (pelanggan_id),
  CONSTRAINT fk_transaksi_pelanggan
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transaksi_detail (
  transaksi_id INT UNSIGNED NOT NULL,
  barang_id INT UNSIGNED NOT NULL,
  harga INT NOT NULL,
  qty INT NOT NULL,
  PRIMARY KEY (transaksi_id, barang_id),
  KEY idx_detail_barang (barang_id),
  CONSTRAINT fk_detail_transaksi
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_barang
    FOREIGN KEY (barang_id) REFERENCES barang(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pembayaran (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  waktu_bayar DATETIME NOT NULL,
  total INT NOT NULL,
  metode ENUM('Tunai', 'Transfer', 'EDC') NOT NULL,
  transaksi_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pembayaran_transaksi (transaksi_id),
  CONSTRAINT fk_pembayaran_transaksi
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data: Users (password_hash(..., PASSWORD_DEFAULT))
-- owner / owner123 (level 1), kasir1 / kasir123 (level 2), owner2 / admin123 (level 1), kasir2 / kasir234 (level 2)
INSERT INTO `user` (id_user, username, password, nama, alamat, hp, level) VALUES
  (1, 'owner',  '$2y$12$8EFW1AO7i73S.BUmGTxIbeojlgDBEgOgsobpfLzXSW7XejQx6It/6', 'Budi Santoso', 'Jl. Raya Darmo No. 1, Surabaya',  '081111111001', 1),
  (2, 'kasir1', '$2y$12$1TDGzTVUMap4Vf1IT9rEpOEap.H/Ogt3iSlNS065E9n/1LfDk3eW6', 'Wati Rahayu',  'Jl. Pahlawan No. 2, Bangkalan',   '081111111002', 2),
  (3, 'owner2', '$2y$12$7T8JEyiRS/Gt5hCe3AIAXumBBv86/wqH4zfBf6Uimjiq2Io0qfpWm', 'Siti Aminah',  'Jl. Diponegoro No. 10, Surabaya', '081111111003', 1),
  (4, 'kasir2', '$2y$12$QO/.f9MDbFQYL/absLFWUus6VGsqart..vG2J8wCWcvcUyRaFXXjy', 'Andi Pratama', 'Jl. Gajah Mada No. 15, Sidoarjo', '081111111004', 2);

-- Seed Data: Suppliers (6 rows)
INSERT INTO supplier (id, nama, telp, alamat) VALUES
  (1, 'PT Sumber Alat Tulis',  '031-5551001', 'Jl. Basuki Rahmat No. 12, Surabaya'),
  (2, 'CV Mitra Kertas',       '031-5551002', 'Jl. Ahmad Yani No. 45, Sidoarjo'),
  (3, 'PT Prima Stationery',   '031-5551003', 'Jl. Pemuda No. 88, Surabaya'),
  (4, 'CV Pena Jaya',          '031-5551004', 'Jl. Veteran No. 21, Gresik'),
  (5, 'PT Kertas Sejahtera',   '031-5551005', 'Jl. Raya Telang No. 5, Bangkalan'),
  (6, 'UD Aksara Nusantara',   '031-5551006', 'Jl. Panglima Sudirman No. 33, Malang');

-- Seed Data: Pelanggan (6 rows)
INSERT INTO pelanggan (id, nama, jenis_kelamin, telp, alamat) VALUES
  (1, 'Andi Wijaya',    'L', '081234567001', 'Jl. Kenjeran No. 14, Surabaya'),
  (2, 'Citra Dewi',     'P', '081234567002', 'Jl. Trunojoyo No. 7, Bangkalan'),
  (3, 'Deni Setiawan',  'L', '081234567003', 'Jl. Pahlawan No. 29, Sidoarjo'),
  (4, 'Eka Rahmawati',  'P', '081234567004', 'Jl. Mulyosari No. 63, Surabaya'),
  (5, 'Fajar Nugroho',  'L', '081234567005', 'Jl. Kamal Raya No. 18, Bangkalan'),
  (6, 'Rina Wulandari', 'P', '081234567006', 'Jl. Dharmawangsa No. 40, Surabaya');

-- Seed Data: Barang (10 rows)
INSERT INTO barang (id, kode_barang, nama_barang, harga, stok, supplier_id) VALUES
  (1,  'BRG-001', 'Buku Tulis Sinar Dunia 38 Lembar', 4500,  120, 1),
  (2,  'BRG-002', 'Pulpen Pilot G2 0.5mm Hitam',      18000,  75, 4),
  (3,  'BRG-003', 'Kertas HVS A4 80gsm (1 Rim)',      52000,  40, 2),
  (4,  'BRG-004', 'Pensil Faber-Castell 2B',           5000, 150, 1),
  (5,  'BRG-005', 'Penghapus Steadler Putih',          4000, 100, 3),
  (6,  'BRG-006', 'Spidol Whiteboard Snowman',         9500,  65, 4),
  (7,  'BRG-007', 'Map Plastik Kancing Folio',         3500, 200, 5),
  (8,  'BRG-008', 'Binder Clip Joyko 155 (1 Box)',    12000,  55, 3),
  (9,  'BRG-009', 'Stapler Kangaro HD-10',            24000,  35, 3),
  (10, 'BRG-010', 'Sticky Notes Post-It 76x76mm',     15000,  80, 5);

-- Seed Data: Transaksi (12 rows across 9 distinct dates in recent 30-day window)
INSERT INTO transaksi (id, waktu_transaksi, keterangan, total, pelanggan_id) VALUES
  (1,  '2026-11-05', 'Pembelian perlengkapan kantor awal bulan',     0, 1),
  (2,  '2026-11-05', 'Belanja alat tulis rutin mahasiswa',           0, 2),
  (3,  '2026-11-08', 'Pengadaan kertas dan spidol kelas',            0, 3),
  (4,  '2026-11-10', 'Pembelian buku dan pensil ujian',              0, 1),
  (5,  '2026-11-12', 'Belanja ATK kebutuhan administrasi',           0, 4),
  (6,  '2026-11-12', 'Pembelian map dokumen dan binder clip',        0, 5),
  (7,  '2026-11-15', 'Stok alat tulis mingguan',                     0, 2),
  (8,  '2026-11-17', 'Pengadaan kertas HVS dan pulpen',              0, 3),
  (9,  '2026-11-19', 'Belanja perlengkapan seminar',                 0, 4),
  (10, '2026-11-19', 'Pembelian spidol dan penghapus papan',         0, 1),
  (11, '2026-11-21', 'Belanja ATK akhir pekan',                      0, 5),
  (12, '2026-11-22', 'Pembelian buku tulis dan perlengkapan arsip',  0, 2);

-- Seed Data: Transaksi Detail (25 rows; harga diambil langsung dari master barang)
INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty)
SELECT 1,  id, harga, 3  FROM barang WHERE id = 3  UNION ALL
SELECT 1,  id, harga, 5  FROM barang WHERE id = 2  UNION ALL
SELECT 2,  id, harga, 10 FROM barang WHERE id = 1  UNION ALL
SELECT 2,  id, harga, 6  FROM barang WHERE id = 4  UNION ALL
SELECT 3,  id, harga, 4  FROM barang WHERE id = 3  UNION ALL
SELECT 3,  id, harga, 8  FROM barang WHERE id = 6  UNION ALL
SELECT 4,  id, harga, 12 FROM barang WHERE id = 1  UNION ALL
SELECT 4,  id, harga, 10 FROM barang WHERE id = 4  UNION ALL
SELECT 4,  id, harga, 8  FROM barang WHERE id = 5  UNION ALL
SELECT 5,  id, harga, 2  FROM barang WHERE id = 3  UNION ALL
SELECT 5,  id, harga, 4  FROM barang WHERE id = 8  UNION ALL
SELECT 6,  id, harga, 15 FROM barang WHERE id = 7  UNION ALL
SELECT 6,  id, harga, 3  FROM barang WHERE id = 8  UNION ALL
SELECT 7,  id, harga, 4  FROM barang WHERE id = 2  UNION ALL
SELECT 7,  id, harga, 5  FROM barang WHERE id = 5  UNION ALL
SELECT 8,  id, harga, 5  FROM barang WHERE id = 3  UNION ALL
SELECT 8,  id, harga, 3  FROM barang WHERE id = 2  UNION ALL
SELECT 9,  id, harga, 20 FROM barang WHERE id = 7  UNION ALL
SELECT 9,  id, harga, 6  FROM barang WHERE id = 6  UNION ALL
SELECT 10, id, harga, 4  FROM barang WHERE id = 6  UNION ALL
SELECT 10, id, harga, 5  FROM barang WHERE id = 4  UNION ALL
SELECT 11, id, harga, 6  FROM barang WHERE id = 1  UNION ALL
SELECT 11, id, harga, 2  FROM barang WHERE id = 8  UNION ALL
SELECT 12, id, harga, 8  FROM barang WHERE id = 1  UNION ALL
SELECT 12, id, harga, 10 FROM barang WHERE id = 7;

-- Hitung ulang transaksi.total agar konsisten 100% dengan transaksi_detail
UPDATE transaksi t
SET t.total = (
  SELECT COALESCE(SUM(d.harga * d.qty), 0)
  FROM transaksi_detail d
  WHERE d.transaksi_id = t.id
);

-- Seed Data: Pembayaran (1 row per transaksi)
INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id)
SELECT
  CONCAT(t.waktu_transaksi, ' 12:00:00'),
  t.total,
  ELT(1 + (t.id % 3), 'Tunai', 'Transfer', 'EDC'),
  t.id
FROM transaksi t
ORDER BY t.id ASC;
