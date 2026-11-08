-- Tugas Praktikum 5 - Modul Database
-- Skema database aplikasi penjualan sederhana.
--
-- Impor dari terminal : mysql -u root -p < database/tp5.sql
-- Impor dari phpMyAdmin: pilih tab Import, unggah file ini.

CREATE DATABASE IF NOT EXISTS tp5
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE tp5;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pembayaran;
DROP TABLE IF EXISTS transaksi_detail;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS pelanggan;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabel master

CREATE TABLE pelanggan (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama          VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L', 'P') NOT NULL,
  telp          VARCHAR(20) NOT NULL,
  alamat        VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE supplier (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama   VARCHAR(100) NOT NULL,
  telp   VARCHAR(20) NOT NULL,
  alamat VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE `user` (
  id_user  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama     VARCHAR(100) NOT NULL,
  alamat   VARCHAR(255) NOT NULL,
  hp       VARCHAR(20) NOT NULL,
  level    ENUM('admin', 'kasir', 'gudang') NOT NULL,
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_user_username (username)
) ENGINE = InnoDB;

CREATE TABLE barang (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_barang VARCHAR(20) NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  harga       DECIMAL(12, 2) NOT NULL,
  stok        INT NOT NULL,
  supplier_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_barang_kode (kode_barang),
  KEY idx_barang_supplier (supplier_id),
  CONSTRAINT fk_barang_supplier FOREIGN KEY (supplier_id) REFERENCES supplier (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Tabel transaksi

CREATE TABLE transaksi (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  waktu_transaksi  DATETIME NOT NULL,
  keterangan       VARCHAR(255) NULL,
  total            DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
  pelanggan_id     INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_transaksi_pelanggan (pelanggan_id),
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE transaksi_detail (
  transaksi_id INT UNSIGNED NOT NULL,
  barang_id    INT UNSIGNED NOT NULL,
  harga        DECIMAL(12, 2) NOT NULL,
  qty          INT NOT NULL,
  PRIMARY KEY (transaksi_id, barang_id),
  KEY idx_detail_barang (barang_id),
  CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_barang FOREIGN KEY (barang_id) REFERENCES barang (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE pembayaran (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  waktu_bayar  DATETIME NOT NULL,
  total        DECIMAL(14, 2) NOT NULL,
  metode       ENUM('Tunai', 'Transfer', 'EDC') NOT NULL,
  transaksi_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pembayaran_transaksi (transaksi_id),
  CONSTRAINT fk_pembayaran_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Data master (12 baris per tabel)

INSERT INTO pelanggan (nama, jenis_kelamin, telp, alamat) VALUES
('Budi Santoso', 'L', '081234567001', 'Jl. Raya Darmo 12, Surabaya'),
('Siti Aminah', 'P', '081234567002', 'Jl. Pahlawan 8, Bangkalan'),
('Agus Setiawan', 'L', '081234567003', 'Jl. Diponegoro 45, Surabaya'),
('Dewi Lestari', 'P', '081234567004', 'Jl. Soekarno Hatta 3, Malang'),
('Rudi Hartono', 'L', '081234567005', 'Jl. Gajah Mada 21, Sidoarjo'),
('Rina Wulandari', 'P', '081234567006', 'Jl. Ahmad Yani 77, Gresik'),
('Joko Prasetyo', 'L', '081234567007', 'Jl. Merdeka 9, Mojokerto'),
('Maya Sari', 'P', '081234567008', 'Jl. Kartini 18, Kediri'),
('Hendra Wijaya', 'L', '081234567009', 'Jl. Sudirman 33, Jakarta'),
('Lina Marlina', 'P', '081234567010', 'Jl. Pahlawan 5, Semarang'),
('Fajar Nugroho', 'L', '081234567011', 'Jl. Melati 14, Yogyakarta'),
('Wulan Sari', 'P', '081234567012', 'Jl. Kenanga 2, Bandung');

INSERT INTO supplier (nama, telp, alamat) VALUES
('PT Maju Bersama', '08123456', 'Surabaya'),
('PT Senang Sekali', '081515563', 'Bangkalan'),
('PT Segar Segar', '0845454663', 'Surabaya'),
('CV Sumber Makmur', '081234567890', 'Malang'),
('PT Sentosa Abadi', '082345678901', 'Jakarta'),
('CV Berkah Jaya', '083456789012', 'Sidoarjo'),
('PT Karya Utama', '084567890123', 'Gresik'),
('UD Makmur Sentosa', '085678901234', 'Mojokerto'),
('CV Tunas Baru', '086789012345', 'Kediri'),
('PT Bintang Terang', '087890123456', 'Semarang'),
('Toko Sumber Rejeki', '088901234567', 'Yogyakarta'),
('CV Mitra Sejati', '089012345678', 'Bandung');

-- Password seluruh data contoh di bawah adalah "password123" (data demo).
INSERT INTO `user` (username, password, nama, alamat, hp, level) VALUES
('admin', 'password123', 'Administrator', 'Jl. Raya Darmo 1, Surabaya', '081111111001', 'admin'),
('kasir1', 'password123', 'Rani Puspita', 'Jl. Pahlawan 2, Bangkalan', '081111111002', 'kasir'),
('kasir2', 'password123', 'Dedi Kurniawan', 'Jl. Diponegoro 7, Surabaya', '081111111003', 'kasir'),
('kasir3', 'password123', 'Nur Aisyah', 'Jl. Gajah Mada 9, Sidoarjo', '081111111004', 'kasir'),
('gudang1', 'password123', 'Sugianto', 'Jl. Ahmad Yani 3, Gresik', '081111111005', 'gudang'),
('gudang2', 'password123', 'Bambang Sutrisno', 'Jl. Merdeka 11, Mojokerto', '081111111006', 'gudang'),
('admin2', 'password123', 'Sri Wahyuni', 'Jl. Kartini 4, Kediri', '081111111007', 'admin'),
('kasir4', 'password123', 'Andi Firmansyah', 'Jl. Sudirman 20, Jakarta', '081111111008', 'kasir'),
('gudang3', 'password123', 'Yudi Hermawan', 'Jl. Pahlawan 6, Semarang', '081111111009', 'gudang'),
('kasir5', 'password123', 'Putri Handayani', 'Jl. Melati 3, Yogyakarta', '081111111010', 'kasir'),
('admin3', 'password123', 'Rizky Ramadhan', 'Jl. Kenanga 8, Bandung', '081111111011', 'admin'),
('kasir6', 'password123', 'Sari Dewanti', 'Jl. Raya Darmo 30, Surabaya', '081111111012', 'kasir');

INSERT INTO barang (kode_barang, nama_barang, harga, stok, supplier_id) VALUES
('BRG-001', 'Kemeja Flanel', 150000.00, 40, 1),
('BRG-002', 'Kaos Polos', 75000.00, 100, 1),
('BRG-003', 'Celana Jeans', 250000.00, 35, 2),
('BRG-004', 'Jaket Bomber', 300000.00, 20, 3),
('BRG-005', 'Kemeja Batik', 175000.00, 50, 3),
('BRG-006', 'Rok Plisket', 125000.00, 30, 4),
('BRG-007', 'Sandal Kulit', 90000.00, 60, 5),
('BRG-008', 'Sepatu Sneakers', 450000.00, 25, 6),
('BRG-009', 'Topi Baseball', 55000.00, 80, 11),
('BRG-010', 'Tas Ransel', 275000.00, 18, 12),
('BRG-011', 'Dompet Kulit', 110000.00, 45, 9),
('BRG-012', 'Ikat Pinggang', 85000.00, 55, 10);

-- Data transaksi (12 transaksi, 19 detail)

INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES
('2026-10-01 09:15:00', 'Pembelian seragam kantor', 0.00, 3),
('2026-10-02 10:40:00', 'Belanja harian', 0.00, 1),
('2026-10-05 13:20:00', 'Pesanan online', 0.00, 7),
('2026-10-08 08:55:00', 'Restock toko', 0.00, 5),
('2026-10-11 15:10:00', 'Pembelian hadiah ulang tahun', 0.00, 2),
('2026-10-14 11:05:00', 'Belanja keluarga', 0.00, 10),
('2026-10-17 16:30:00', 'Pembelian kaos komunitas', 0.00, 4),
('2026-10-20 09:45:00', 'Pesanan kantor cabang', 0.00, 9),
('2026-10-23 14:25:00', 'Belanja bulanan', 0.00, 6),
('2026-10-26 10:15:00', 'Pembelian seragam sekolah', 0.00, 8),
('2026-10-28 17:40:00', 'Belanja aksesoris', 0.00, 12),
('2026-10-31 12:00:00', 'Pembelian stok pribadi', 0.00, 11);

INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty)
SELECT d.transaksi_id, b.id, b.harga, d.qty
FROM (
  SELECT 1 AS transaksi_id, 'BRG-001' AS kode, 2 AS qty
  UNION ALL SELECT 1, 'BRG-002', 3
  UNION ALL SELECT 2, 'BRG-003', 1
  UNION ALL SELECT 3, 'BRG-004', 1
  UNION ALL SELECT 3, 'BRG-009', 2
  UNION ALL SELECT 4, 'BRG-005', 2
  UNION ALL SELECT 5, 'BRG-006', 1
  UNION ALL SELECT 5, 'BRG-007', 3
  UNION ALL SELECT 6, 'BRG-008', 1
  UNION ALL SELECT 7, 'BRG-002', 5
  UNION ALL SELECT 7, 'BRG-011', 2
  UNION ALL SELECT 8, 'BRG-010', 1
  UNION ALL SELECT 9, 'BRG-001', 1
  UNION ALL SELECT 9, 'BRG-003', 2
  UNION ALL SELECT 9, 'BRG-012', 2
  UNION ALL SELECT 10, 'BRG-006', 2
  UNION ALL SELECT 11, 'BRG-009', 4
  UNION ALL SELECT 12, 'BRG-005', 1
  UNION ALL SELECT 12, 'BRG-007', 1
) AS d
JOIN barang b ON b.kode_barang = d.kode;

-- Total transaksi dihitung dari seluruh baris detailnya.
UPDATE transaksi t
SET t.total = (
  SELECT COALESCE(SUM(d.harga * d.qty), 0)
  FROM transaksi_detail d
  WHERE d.transaksi_id = t.id
);

-- Satu pembayaran untuk setiap transaksi; totalnya sama dengan total transaksi.
INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id)
SELECT DATE_ADD(t.waktu_transaksi, INTERVAL (10 + t.id) MINUTE),
       t.total,
       ELT(1 + (t.id % 3), 'Tunai', 'Transfer', 'EDC'),
       t.id
FROM transaksi t;
