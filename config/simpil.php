<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk penyimpanan dokumen (naskah, revisi, PDF final)
    |--------------------------------------------------------------------------
    | Harus disk PRIVAT (bukan 'public'), sesuai SRS NF-04: berkas tidak diakses langsung
    | sebagai public asset, melainkan lewat rute terotorisasi (ManuscriptFileController).
    |   local : storage/app/private        (pengembangan / VPS tunggal)
    |   s3    : bucket S3 privat           (AWS)
    */
    'manuscript_disk' => env('MANUSCRIPT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Identitas jurnal (dipakai pada kutipan artikel)
    |--------------------------------------------------------------------------
    */
    'journal' => [
        'name' => env('JOURNAL_NAME', 'SIMPIL BRIDA Kota Makassar'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jaringan (di belakang load balancer / reverse proxy)
    |--------------------------------------------------------------------------
    | TRUSTED_PROXIES: '*' (semua, mis. ALB di VPC privat), atau daftar IP dipisah koma.
    | FORCE_HTTPS    : paksa URL https (SRS NF-02) — aktifkan di produksi.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),
    'force_https'     => (bool) env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Batas ukuran unggahan (KB)
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'manuscript_kb' => 10240, // 10 MB — naskah & revisi (SRS NF-01)
        'final_kb'      => 20480, // 20 MB — PDF final layout
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis dokumen yang diterima (validasi ekstensi + MIME asli + struktur isi)
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'pdf' => [
            'mimes' => ['application/pdf'],
            'label' => 'PDF',
        ],
        'docx' => [
            // finfo sering melaporkan DOCX sebagai zip; struktur isinya diperiksa terpisah.
            'mimes' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream',
            ],
            'label' => 'DOCX',
        ],
    ],

    /** Segmen nama berkas yang tidak boleh muncul (mencegah double extension: shell.php.pdf). */
    'blocked_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht', 'inc',
        'exe', 'bat', 'cmd', 'com', 'sh', 'bash', 'js', 'mjs', 'html', 'htm', 'svg',
        'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'rb', 'dll', 'msi', 'jar', 'htaccess',
    ],
];
