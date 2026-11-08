<?php
declare(strict_types=1);

/**
 * Definisi tabel yang dipakai halaman tabel.php, tambah.php, edit.php, dan hapus.php.
 *
 * - primary    : kolom primary key (bisa lebih dari satu)
 * - fields     : kolom form; 'auto' => true tidak tampil di form,
 *                'form' => false hanya tampil di halaman daftar (hasil join)
 * - list.sql   : query daftar; wajib mengembalikan kolom primary key
 * - list.columns: urutan kolom yang ditampilkan setelah kolom "No"
 */
return [
    'supplier' => [
        'title' => 'Data Master Supplier',
        'short' => 'Supplier',
        'name' => 'supplier',
        'table' => 'supplier',
        'primary' => ['id'],
        'fields' => [
            'id' => ['label' => 'ID', 'auto' => true],
            'nama' => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Nama'],
            'telp' => ['label' => 'Telp', 'type' => 'text', 'required' => true, 'max' => 20, 'placeholder' => 'telp'],
            'alamat' => ['label' => 'Alamat', 'type' => 'text', 'required' => true, 'max' => 255, 'placeholder' => 'alamat'],
        ],
        'list' => [
            'sql' => 'SELECT id, nama, telp, alamat FROM supplier ORDER BY nama',
            'columns' => ['nama', 'telp', 'alamat'],
        ],
    ],

    'pelanggan' => [
        'title' => 'Data Master Pelanggan',
        'short' => 'Pelanggan',
        'name' => 'pelanggan',
        'table' => 'pelanggan',
        'primary' => ['id'],
        'fields' => [
            'id' => ['label' => 'ID', 'auto' => true],
            'nama' => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Nama'],
            'jenis_kelamin' => [
                'label' => 'Jenis Kelamin',
                'type' => 'select',
                'required' => true,
                'options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'],
            ],
            'telp' => ['label' => 'Telp', 'type' => 'text', 'required' => true, 'max' => 20, 'placeholder' => 'telp'],
            'alamat' => ['label' => 'Alamat', 'type' => 'text', 'required' => true, 'max' => 255, 'placeholder' => 'alamat'],
        ],
        'list' => [
            'sql' => 'SELECT id, nama, jenis_kelamin, telp, alamat FROM pelanggan ORDER BY nama',
            'columns' => ['nama', 'jenis_kelamin', 'telp', 'alamat'],
        ],
    ],

    'barang' => [
        'title' => 'Data Master Barang',
        'short' => 'Barang',
        'name' => 'barang',
        'table' => 'barang',
        'primary' => ['id'],
        'fields' => [
            'id' => ['label' => 'ID', 'auto' => true],
            'kode_barang' => ['label' => 'Kode Barang', 'type' => 'text', 'required' => true, 'max' => 20, 'placeholder' => 'kode_barang'],
            'nama_barang' => ['label' => 'Nama Barang', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'nama_barang'],
            'harga' => ['label' => 'Harga', 'type' => 'money', 'required' => true, 'min' => 0],
            'stok' => ['label' => 'Stok', 'type' => 'number', 'required' => true, 'min' => 0],
            'supplier_id' => [
                'label' => 'Supplier',
                'type' => 'select',
                'required' => true,
                'options_sql' => 'SELECT id AS nilai, nama AS teks FROM supplier ORDER BY nama',
            ],
            'supplier' => ['label' => 'Supplier', 'form' => false],
        ],
        'list' => [
            'sql' => 'SELECT b.id, b.kode_barang, b.nama_barang, b.harga, b.stok, s.nama AS supplier
                      FROM barang b
                      JOIN supplier s ON s.id = b.supplier_id
                      ORDER BY b.kode_barang',
            'columns' => ['kode_barang', 'nama_barang', 'harga', 'stok', 'supplier'],
        ],
    ],

    'user' => [
        'title' => 'Data Master User',
        'short' => 'User',
        'name' => 'user',
        'table' => 'user',
        'primary' => ['id_user'],
        'fields' => [
            'id_user' => ['label' => 'ID User', 'auto' => true],
            'username' => ['label' => 'Username', 'type' => 'text', 'required' => true, 'max' => 50, 'placeholder' => 'username'],
            'password' => [
                'label' => 'Password',
                'type' => 'password',
                'required' => true,
                'max' => 255,
                'keep_if_empty' => true,
            ],
            'nama' => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Nama'],
            'alamat' => ['label' => 'Alamat', 'type' => 'text', 'required' => true, 'max' => 255, 'placeholder' => 'alamat'],
            'hp' => ['label' => 'HP', 'type' => 'text', 'required' => true, 'max' => 20, 'placeholder' => 'hp'],
            'level' => [
                'label' => 'Level',
                'type' => 'select',
                'required' => true,
                'options' => ['admin' => 'admin', 'kasir' => 'kasir', 'gudang' => 'gudang'],
            ],
        ],
        'list' => [
            'sql' => 'SELECT id_user, username, nama, alamat, hp, level FROM `user` ORDER BY nama',
            'columns' => ['username', 'nama', 'alamat', 'hp', 'level'],
        ],
    ],

    'transaksi' => [
        'title' => 'Data Transaksi',
        'short' => 'Transaksi',
        'name' => 'transaksi',
        'table' => 'transaksi',
        'primary' => ['id'],
        'fields' => [
            'id' => ['label' => 'Kode', 'auto' => true],
            'waktu_transaksi' => ['label' => 'Waktu Transaksi', 'type' => 'datetime', 'required' => true],
            'keterangan' => ['label' => 'Keterangan', 'type' => 'text', 'max' => 255, 'placeholder' => 'keterangan'],
            'total' => ['label' => 'Total', 'type' => 'money', 'required' => true, 'min' => 0],
            'pelanggan_id' => [
                'label' => 'Pelanggan',
                'type' => 'select',
                'required' => true,
                'options_sql' => "SELECT id AS nilai, CONCAT(nama, ' - ', telp) AS teks FROM pelanggan ORDER BY nama",
            ],
            'pelanggan' => ['label' => 'Pelanggan', 'form' => false],
        ],
        'list' => [
            'sql' => 'SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, p.nama AS pelanggan
                      FROM transaksi t
                      JOIN pelanggan p ON p.id = t.pelanggan_id
                      ORDER BY t.waktu_transaksi DESC',
            'columns' => ['id', 'waktu_transaksi', 'keterangan', 'total', 'pelanggan'],
        ],
    ],

    'transaksi_detail' => [
        'title' => 'Data Detail Transaksi',
        'short' => 'Detail Transaksi',
        'name' => 'detail transaksi',
        'table' => 'transaksi_detail',
        'primary' => ['transaksi_id', 'barang_id'],
        'fields' => [
            'transaksi_id' => [
                'label' => 'Transaksi',
                'type' => 'select',
                'required' => true,
                'options_sql' => "SELECT t.id AS nilai,
                                          CONCAT('#', t.id, ' - ', DATE_FORMAT(t.waktu_transaksi, '%d-%m-%Y %H:%i'), ' - ', p.nama) AS teks
                                   FROM transaksi t
                                   JOIN pelanggan p ON p.id = t.pelanggan_id
                                   ORDER BY t.waktu_transaksi DESC",
            ],
            'barang_id' => [
                'label' => 'Barang',
                'type' => 'select',
                'required' => true,
                'options_sql' => "SELECT id AS nilai, CONCAT(kode_barang, ' - ', nama_barang) AS teks FROM barang ORDER BY kode_barang",
            ],
            'harga' => ['label' => 'Harga', 'type' => 'money', 'required' => true, 'min' => 0],
            'qty' => ['label' => 'Qty', 'type' => 'number', 'required' => true, 'min' => 1],
            'kode_barang' => ['label' => 'Kode', 'form' => false],
            'nama_barang' => ['label' => 'Barang', 'form' => false],
        ],
        'list' => [
            'sql' => 'SELECT d.transaksi_id, d.barang_id, d.harga, d.qty, b.kode_barang, b.nama_barang
                      FROM transaksi_detail d
                      JOIN barang b ON b.id = d.barang_id
                      ORDER BY d.transaksi_id, b.kode_barang',
            'columns' => ['transaksi_id', 'kode_barang', 'nama_barang', 'harga', 'qty'],
        ],
    ],

    'pembayaran' => [
        'title' => 'Data Pembayaran',
        'short' => 'Pembayaran',
        'name' => 'pembayaran',
        'table' => 'pembayaran',
        'primary' => ['id'],
        'fields' => [
            'id' => ['label' => 'ID', 'auto' => true],
            'waktu_bayar' => ['label' => 'Waktu Bayar', 'type' => 'datetime', 'required' => true],
            'total' => ['label' => 'Total', 'type' => 'money', 'required' => true, 'min' => 0],
            'metode' => [
                'label' => 'Metode',
                'type' => 'select',
                'required' => true,
                'options' => ['Tunai' => 'Tunai', 'Transfer' => 'Transfer', 'EDC' => 'EDC'],
            ],
            'transaksi_id' => [
                'label' => 'Transaksi',
                'type' => 'select',
                'required' => true,
                'options_sql' => "SELECT t.id AS nilai,
                                          CONCAT('#', t.id, ' - ', DATE_FORMAT(t.waktu_transaksi, '%d-%m-%Y %H:%i'), ' - ', c.nama) AS teks
                                   FROM transaksi t
                                   JOIN pelanggan c ON c.id = t.pelanggan_id
                                   ORDER BY t.waktu_transaksi DESC",
            ],
            'pelanggan' => ['label' => 'Pelanggan', 'form' => false],
        ],
        'list' => [
            'sql' => 'SELECT b.id, b.waktu_bayar, b.total, b.metode, b.transaksi_id, c.nama AS pelanggan
                      FROM pembayaran b
                      JOIN transaksi t ON t.id = b.transaksi_id
                      JOIN pelanggan c ON c.id = t.pelanggan_id
                      ORDER BY b.waktu_bayar DESC',
            'columns' => ['waktu_bayar', 'total', 'metode', 'transaksi_id', 'pelanggan'],
        ],
    ],
];
