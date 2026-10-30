<?php

/*
|--------------------------------------------------------------------------
| Klasifikasi aspirasi berbasis aturan
|--------------------------------------------------------------------------
|
| Kata kunci disimpan per komisi di kolom dprd_commissions.keywords agar
| Administrator dapat memperbaikinya tanpa mengubah kode. Nilai di bawah
| hanya dipakai sebagai data awal untuk komisi yang sudah ada.
|
| Format setiap kata kunci: ['term' => 'frasa', 'weight' => 1..10].
| Bobot 3 dipakai untuk kata kunci inti, 2 untuk kata kunci pendukung,
| dan 1 untuk kata yang sangat umum.
|
*/

return [
    'method' => 'rule_based_weighted_keywords_v1',
    'minimum_score' => 4,
    'minimum_margin' => 2,
    'max_occurrences' => 3,

    'defaults' => [
        'komisi-1' => [
            ['term' => 'pemerintahan', 'weight' => 3],
            ['term' => 'pelayanan publik', 'weight' => 3],
            ['term' => 'pelayanan', 'weight' => 2],
            ['term' => 'administrasi', 'weight' => 2],
            ['term' => 'kependudukan', 'weight' => 3],
            ['term' => 'ktp', 'weight' => 3],
            ['term' => 'akta', 'weight' => 2],
            ['term' => 'izin', 'weight' => 2],
            ['term' => 'perizinan', 'weight' => 3],
            ['term' => 'hukum', 'weight' => 3],
            ['term' => 'peraturan daerah', 'weight' => 3],
            ['term' => 'perda', 'weight' => 3],
            ['term' => 'ketertiban', 'weight' => 3],
            ['term' => 'ketertiban umum', 'weight' => 3],
            ['term' => 'keamanan', 'weight' => 2],
            ['term' => 'perlindungan masyarakat', 'weight' => 3],
            ['term' => 'satpol pp', 'weight' => 3],
            ['term' => 'aparat', 'weight' => 2],
            ['term' => 'korupsi', 'weight' => 2],
            ['term' => 'pengawasan', 'weight' => 2],
            ['term' => 'transparansi', 'weight' => 2],
            ['term' => 'birokrasi', 'weight' => 2],
        ],

        'komisi-2' => [
            ['term' => 'ekonomi', 'weight' => 3],
            ['term' => 'keuangan', 'weight' => 3],
            ['term' => 'anggaran', 'weight' => 3],
            ['term' => 'apbd', 'weight' => 3],
            ['term' => 'pajak', 'weight' => 3],
            ['term' => 'retribusi', 'weight' => 3],
            ['term' => 'investasi', 'weight' => 3],
            ['term' => 'umkm', 'weight' => 3],
            ['term' => 'usaha', 'weight' => 2],
            ['term' => 'pasar', 'weight' => 3],
            ['term' => 'perdagangan', 'weight' => 3],
            ['term' => 'infrastruktur', 'weight' => 3],
            ['term' => 'pembangunan', 'weight' => 2],
            ['term' => 'jalan', 'weight' => 3],
            ['term' => 'jembatan', 'weight' => 3],
            ['term' => 'drainase', 'weight' => 3],
            ['term' => 'lampu jalan', 'weight' => 3],
            ['term' => 'penerangan jalan', 'weight' => 3],
            ['term' => 'banjir', 'weight' => 2],
            ['term' => 'air bersih', 'weight' => 2],
            ['term' => 'listrik', 'weight' => 2],
            ['term' => 'transportasi', 'weight' => 2],
            ['term' => 'terminal', 'weight' => 2],
            ['term' => 'taman', 'weight' => 2],
            ['term' => 'fasilitas umum', 'weight' => 2],
            ['term' => 'sampah', 'weight' => 2],
            ['term' => 'lingkungan', 'weight' => 2],
            ['term' => 'pengadaan', 'weight' => 2],
        ],

        'komisi-3' => [
            ['term' => 'pendidikan', 'weight' => 3],
            ['term' => 'sekolah', 'weight' => 3],
            ['term' => 'guru', 'weight' => 3],
            ['term' => 'siswa', 'weight' => 3],
            ['term' => 'murid', 'weight' => 2],
            ['term' => 'beasiswa', 'weight' => 3],
            ['term' => 'kurikulum', 'weight' => 2],
            ['term' => 'kesehatan', 'weight' => 3],
            ['term' => 'puskesmas', 'weight' => 3],
            ['term' => 'rumah sakit', 'weight' => 3],
            ['term' => 'dokter', 'weight' => 2],
            ['term' => 'tenaga kesehatan', 'weight' => 3],
            ['term' => 'obat', 'weight' => 2],
            ['term' => 'posyandu', 'weight' => 3],
            ['term' => 'stunting', 'weight' => 3],
            ['term' => 'gizi', 'weight' => 2],
            ['term' => 'sosial', 'weight' => 3],
            ['term' => 'kesejahteraan', 'weight' => 3],
            ['term' => 'bantuan sosial', 'weight' => 3],
            ['term' => 'bansos', 'weight' => 3],
            ['term' => 'anak', 'weight' => 2],
            ['term' => 'lansia', 'weight' => 2],
            ['term' => 'disabilitas', 'weight' => 2],
            ['term' => 'perempuan', 'weight' => 2],
            ['term' => 'pemberdayaan', 'weight' => 2],
            ['term' => 'bpjs', 'weight' => 2],
            ['term' => 'sanitasi', 'weight' => 2],
            ['term' => 'kesehatan lingkungan', 'weight' => 3],
        ],
    ],
];
