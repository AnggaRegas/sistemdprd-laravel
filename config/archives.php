<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maksimal Kapasitas Unggah Berkas Dokumen Arsip (dalam Megabytes)
    |--------------------------------------------------------------------------
    |
    | Pengaturan terpusat untuk batas ukuran berkas arsip dokumen DPRD.
    | Nilai default adalah 100 MB dan dapat diubah melalui variabel
    | lingkungan MAX_DOCUMENT_UPLOAD_MB tanpa harus mengedit banyak berkas.
    |
    */
    'max_upload_mb' => (int) env('MAX_DOCUMENT_UPLOAD_MB', 100),

    /*
    |--------------------------------------------------------------------------
    | Format Ekstensi yang Didukung
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'txt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Format MIME Types yang Diizinkan untuk Verifikasi Isi Berkas
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => [
        // PDF
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'applications/vnd.pdf',
        'text/pdf',
        'text/x-pdf',

        // Microsoft Word
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-word',

        // Microsoft Excel
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msexcel',
        'application/x-msexcel',

        // Microsoft PowerPoint
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/powerpoint',

        // Gambar
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp',

        // Teks
        'text/plain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ekstensi / Tipe Berkas Terlarang (Security Blacklist)
    |--------------------------------------------------------------------------
    */
    'blocked_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'dll', 'bat', 'cmd', 'sh', 'bash', 'bin', 'cgi', 'pl',
        'py', 'js', 'jar', 'vbs', 'ps1', 'msi', 'com', 'scr', 'hta',
    ],
];
