<?php
declare(strict_types=1);

return [
    'barang' => [
        'key'     => 'barang',
        'table'   => 'barang',
        'primary' => 'id',
        'title'   => 'Data Barang',
        'short'   => 'Barang',
        'name'    => 'barang',
        'fields'  => [
            'id' => [
                'label' => 'ID',
                'auto'  => true,
            ],
            'kode_barang' => [
                'label'       => 'Kode Barang',
                'type'        => 'text',
                'required'    => true,
                'max'         => 20,
                'unique'      => true,
                'placeholder' => 'Contoh: BRG-011',
            ],
            'nama_barang' => [
                'label'       => 'Nama Barang',
                'type'        => 'text',
                'required'    => true,
                'max'         => 100,
                'placeholder' => 'Nama barang',
            ],
            'harga' => [
                'label'    => 'Harga',
                'type'     => 'money',
                'required' => true,
                'min'      => 0,
                'max_int'  => 2147483647,
                'align'    => 'right',
            ],
            'stok' => [
                'label'    => 'Stok',
                'type'     => 'number',
                'required' => true,
                'min'      => 0,
                'max_int'  => 2147483647,
                'align'    => 'right',
            ],
            'supplier_id' => [
                'label'       => 'Supplier',
                'type'        => 'select',
                'required'    => true,
                'value_type'  => 'int',
                'options_sql' => 'SELECT id AS nilai, nama AS teks FROM supplier ORDER BY nama ASC',
            ],
            'supplier' => [
                'label' => 'Supplier',
                'form'  => false,
            ],
        ],
        'list' => [
            'sql'     => 'SELECT b.id, b.kode_barang, b.nama_barang, b.harga, b.stok, s.nama AS supplier
                          FROM barang b
                          INNER JOIN supplier s ON s.id = b.supplier_id
                          ORDER BY b.kode_barang ASC, b.id ASC',
            'columns' => ['kode_barang', 'nama_barang', 'harga', 'stok', 'supplier'],
        ],
    ],

    'supplier' => [
        'key'     => 'supplier',
        'table'   => 'supplier',
        'primary' => 'id',
        'title'   => 'Data Supplier',
        'short'   => 'Supplier',
        'name'    => 'supplier',
        'fields'  => [
            'id' => [
                'label' => 'ID',
                'auto'  => true,
            ],
            'nama' => [
                'label'       => 'Nama Supplier',
                'type'        => 'text',
                'required'    => true,
                'max'         => 100,
                'placeholder' => 'Nama supplier',
            ],
            'telp' => [
                'label'       => 'Telepon',
                'type'        => 'text',
                'required'    => true,
                'max'         => 20,
                'placeholder' => 'Contoh: 031-5551001',
            ],
            'alamat' => [
                'label'       => 'Alamat',
                'type'        => 'textarea',
                'required'    => true,
                'max'         => 65535,
                'placeholder' => 'Alamat lengkap supplier',
            ],
        ],
        'list' => [
            'sql'     => 'SELECT id, nama, telp, alamat FROM supplier ORDER BY nama ASC, id ASC',
            'columns' => ['nama', 'telp', 'alamat'],
        ],
    ],

    'pelanggan' => [
        'key'     => 'pelanggan',
        'table'   => 'pelanggan',
        'primary' => 'id',
        'title'   => 'Data Pelanggan',
        'short'   => 'Pelanggan',
        'name'    => 'pelanggan',
        'fields'  => [
            'id' => [
                'label' => 'ID',
                'auto'  => true,
            ],
            'nama' => [
                'label'       => 'Nama Pelanggan',
                'type'        => 'text',
                'required'    => true,
                'max'         => 100,
                'placeholder' => 'Nama pelanggan',
            ],
            'jenis_kelamin' => [
                'label'    => 'Jenis Kelamin',
                'type'     => 'select',
                'required' => true,
                'options'  => [
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                ],
            ],
            'telp' => [
                'label'       => 'Telepon',
                'type'        => 'text',
                'required'    => true,
                'max'         => 20,
                'placeholder' => 'Contoh: 081234567001',
            ],
            'alamat' => [
                'label'       => 'Alamat',
                'type'        => 'textarea',
                'required'    => true,
                'max'         => 65535,
                'placeholder' => 'Alamat lengkap pelanggan',
            ],
        ],
        'list' => [
            'sql'     => 'SELECT id, nama, jenis_kelamin, telp, alamat FROM pelanggan ORDER BY nama ASC, id ASC',
            'columns' => ['nama', 'jenis_kelamin', 'telp', 'alamat'],
        ],
    ],

    'user' => [
        'key'     => 'user',
        'table'   => 'user',
        'primary' => 'id_user',
        'title'   => 'Data User',
        'short'   => 'User',
        'name'    => 'user',
        'fields'  => [
            'id_user' => [
                'label' => 'ID User',
                'auto'  => true,
            ],
            'username' => [
                'label'       => 'Username',
                'type'        => 'text',
                'required'    => true,
                'max'         => 30,
                'unique'      => true,
                'placeholder' => 'Username login (maks. 30 karakter)',
            ],
            'password' => [
                'label'         => 'Password',
                'type'          => 'password',
                'required'      => true,
                'max'           => 255,
                'keep_if_empty' => true,
                'hash'          => true,
                'no_trim'       => true,
                'placeholder'   => 'Masukkan password',
            ],
            'nama' => [
                'label'       => 'Nama Lengkap',
                'type'        => 'text',
                'required'    => true,
                'max'         => 50,
                'placeholder' => 'Nama lengkap pengguna',
            ],
            'alamat' => [
                'label'       => 'Alamat',
                'type'        => 'textarea',
                'required'    => true,
                'max'         => 150,
                'placeholder' => 'Alamat lengkap (maks. 150 karakter)',
            ],
            'hp' => [
                'label'       => 'No. HP',
                'type'        => 'text',
                'required'    => true,
                'max'         => 20,
                'placeholder' => 'Contoh: 081111111001',
            ],
            'level' => [
                'label'      => 'Level',
                'type'       => 'select',
                'required'   => true,
                'value_type' => 'int',
                'options'    => [
                    '1' => 'Owner (Level 1)',
                    '2' => 'Kasir (Level 2)',
                ],
            ],
        ],
        'list' => [
            'sql'     => 'SELECT id_user, username, nama, alamat, hp, level FROM `user` ORDER BY level ASC, nama ASC, id_user ASC',
            'columns' => ['username', 'nama', 'alamat', 'hp', 'level'],
        ],
    ],
];
