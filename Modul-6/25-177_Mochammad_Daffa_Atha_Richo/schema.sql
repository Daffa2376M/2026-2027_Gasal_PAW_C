CREATE TABLE supplier (
 id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, telp VARCHAR(12), alamat TEXT
) ENGINE=InnoDB;
CREATE TABLE pelanggan (
 id VARCHAR(20) PRIMARY KEY, nama VARCHAR(100) NOT NULL, jenis_kelamin ENUM('L','P') NOT NULL, telp VARCHAR(12), alamat TEXT
) ENGINE=InnoDB;
CREATE TABLE barang (
 id INT AUTO_INCREMENT PRIMARY KEY, kode_barang VARCHAR(10) NOT NULL UNIQUE, nama_barang VARCHAR(100) NOT NULL,
 harga INT NOT NULL, stok INT NOT NULL, supplier_id INT NOT NULL,
 FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE transaksi (
 id INT AUTO_INCREMENT PRIMARY KEY, waktu_transaksi DATE NOT NULL, keterangan TEXT,
 total INT NOT NULL DEFAULT 0, pelanggan_id VARCHAR(20) NOT NULL,
 FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE transaksi_detail (
 transaksi_id INT NOT NULL, barang_id INT NOT NULL, harga INT NOT NULL, qty INT NOT NULL,
 PRIMARY KEY (transaksi_id, barang_id),
 FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE RESTRICT,
 FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE pembayaran (
 id INT AUTO_INCREMENT PRIMARY KEY, waktu_bayar DATETIME NOT NULL, total INT NOT NULL,
 metode ENUM('TUNAI','TRANSFER','EDC') NOT NULL, transaksi_id INT NOT NULL,
 FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE user (
 id_user TINYINT PRIMARY KEY, username VARCHAR(30) NOT NULL UNIQUE, password VARCHAR(35) NOT NULL,
 nama VARCHAR(50) NOT NULL, alamat VARCHAR(150), hp VARCHAR(20), level TINYINT NOT NULL
) ENGINE=InnoDB;
INSERT INTO supplier (nama,telp,alamat) VALUES
 ('Sumber Alat Tulis','08110000001','Yogyakarta'),('Mitra Kertas','08110000002','Sleman'),('Prima Stationery','08110000003','Bantul'),('Pena Jaya','08110000004','Solo'),('Kertas Sejahtera','08110000005','Semarang');
INSERT INTO pelanggan VALUES
 ('P001','Andi Pratama','L','08120000001','Yogyakarta'),('P002','Budi Santoso','L','08120000002','Sleman'),('P003','Citra Dewi','P','08120000003','Bantul'),('P004','Deni Setiawan','L','08120000004','Solo'),('P005','Eka Rahmawati','P','08120000005','Semarang');
INSERT INTO barang (kode_barang,nama_barang,harga,stok,supplier_id) VALUES
 ('B001','Buku Tulis A5',5000,100,1),('B002','Pulpen Gel Hitam',4000,100,2),('B003','Pensil 2B',2500,100,3),('B004','Penghapus Karet',1500,100,4),('B005','Penggaris 30cm',3000,100,5),('B006','Correction Tape',6000,100,1),('B007','Spidol Whiteboard',8500,100,2),('B008','Map Folio',2000,100,3),('B009','Sticky Notes',7500,100,4),('B010','Gunting Kertas',9000,100,5),('B011','Stapler',12000,20,1);
INSERT INTO transaksi (waktu_transaksi,keterangan,pelanggan_id) VALUES
 ('2026-11-01','Penjualan 1','P001'),('2026-11-01','Penjualan 2','P002'),('2026-11-02','Penjualan 3','P003'),('2026-11-02','Penjualan 4','P004'),('2026-11-03','Penjualan 5','P005'),('2026-11-03','Penjualan 6','P001'),('2026-11-04','Penjualan 7','P002'),('2026-11-04','Penjualan 8','P003'),('2026-11-04','Penjualan 9','P004'),('2026-11-04','Penjualan 10','P005');
INSERT INTO transaksi_detail VALUES
 (1,1,5000,3),(1,2,4000,2),(1,4,1500,1),(2,3,2500,4),(2,5,3000,1),
 (3,6,6000,2),(3,7,8500,2),(3,9,7500,1),(4,10,9000,1),(4,8,2000,5),
 (5,1,5000,5),(5,3,2500,2),(6,7,8500,3),(6,4,1500,2),(7,2,4000,3),
 (7,9,7500,2),(7,5,3000,2),(8,8,2000,10),(8,6,6000,1),(9,10,9000,2),
 (9,1,5000,2),(10,2,4000,5),(10,3,2500,4),(10,7,8500,1);
UPDATE transaksi t SET total = (SELECT COALESCE(SUM(d.harga*d.qty),0) FROM transaksi_detail d WHERE d.transaksi_id=t.id);
INSERT INTO pembayaran (waktu_bayar,total,metode,transaksi_id) SELECT CONCAT(waktu_transaksi,' 12:00:00'),total,'TUNAI',id FROM transaksi;
INSERT INTO user VALUES
 (1,'admin1','contoh1','Admin Satu','Yogyakarta','08130000001',1),
 (2,'admin2','contoh2','Admin Dua','Sleman','08130000002',1),
 (3,'kasir1','contoh3','Kasir Satu','Bantul','08130000003',2),
 (4,'kasir2','contoh4','Kasir Dua','Solo','08130000004',2),
 (5,'kasir3','contoh5','Kasir Tiga','Semarang','08130000005',2);
