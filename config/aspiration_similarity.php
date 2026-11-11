<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambang deteksi aspirasi mirip
    |--------------------------------------------------------------------------
    | Deteksi memakai dua skor terpisah:
    | - location_threshold : kemiripan alamat/lokasi pelapor
    | - problem_threshold  : kemiripan isi dan inti permasalahan
    |
    | Sebuah aspirasi hanya diberi label "Aspirasi Mirip Ditemukan" bila KEDUA
    | skor mencapai ambangnya. Bila salah satu tidak tercapai, atau informasi
    | lokasi tidak cukup, aspirasi ditampilkan normal tanpa label.
    |
    | Nilai dapat diubah lewat .env tanpa mengubah kode.
    */
    'location_threshold' => (float) env('ASPIRATION_SIMILARITY_LOCATION_THRESHOLD', 0.70),

    'problem_threshold' => (float) env('ASPIRATION_SIMILARITY_PROBLEM_THRESHOLD', 0.60),

    /*
    | Ambang skor gabungan. Skor gabungan hanya dipakai sebagai ringkasan
    | angka; keputusan "kemiripan tinggi" tetap ditentukan oleh kedua ambang
    | di atas.
    */
    'high_threshold' => (float) env(
        'ASPIRATION_SIMILARITY_HIGH_THRESHOLD',
        env('ASPIRATION_SIMILARITY_THRESHOLD', 0.60)
    ),

    /*
    | Dipertahankan agar konfigurasi lama tetap terbaca. Fitur tidak lagi
    | memakai tingkat kemiripan sedang/rendah untuk memberi label.
    */
    'threshold' => (float) env('ASPIRATION_SIMILARITY_THRESHOLD', 0.60),

    'medium_threshold' => (float) env('ASPIRATION_SIMILARITY_MEDIUM_THRESHOLD', 0.55),

    /*
    | Bobot untuk skor gabungan yang ditampilkan sebagai persentase ringkas.
    */
    'weights' => [
        'problem' => (float) env('ASPIRATION_SIMILARITY_PROBLEM_WEIGHT', 0.60),
        'location' => (float) env('ASPIRATION_SIMILARITY_LOCATION_WEIGHT', 0.40),
    ],

    /*
    | Jumlah hasil pembanding yang disimpan dan ditampilkan. Hasil tetap
    | diurutkan dari kemiripan tertinggi.
    */
    'max_results' => (int) env('ASPIRATION_SIMILARITY_MAX_RESULTS', 10),
];
