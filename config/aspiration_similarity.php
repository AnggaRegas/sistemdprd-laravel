<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambang deteksi duplikasi aspirasi
    |--------------------------------------------------------------------------
    | Nilai 0.80 berarti aspirasi baru ditandai "Berpotensi Duplikat" bila
    | Cosine Similarity terhadap aspirasi sebelumnya (anggota DPRD sama)
    | mencapai 80%. Ubah lewat ASPIRATION_SIMILARITY_THRESHOLD di .env.
    */
    'threshold' => (float) env('ASPIRATION_SIMILARITY_THRESHOLD', 0.80),

    /*
    | Jumlah hasil pembanding yang disimpan dan ditampilkan. Hasil tetap
    | diurutkan dari kemiripan tertinggi.
    */
    'max_results' => (int) env('ASPIRATION_SIMILARITY_MAX_RESULTS', 5),
];
