<?php

/*
|--------------------------------------------------------------------------
| Kamus Acuan
|--------------------------------------------------------------------------
|
| Pencocokan aspirasi dilakukan dengan kata kunci/frasa utuh yang tersimpan
| pada entri kamus. Bobot hanya menunjukkan kekuatan pencocokan, bukan
| prioritas tindak lanjut. Prioritas tetap diambil dari kolom prioritas pada
| entri dan tidak pernah dibuat otomatis.
|
*/

return [
    'method' => 'kamus_acuan_whole_word_v1',

    // Skor minimal agar satu kandidat tunggal dapat dinyatakan sesuai kamus.
    'minimum_score' => 4,

    // Jumlah kemunculan satu istilah yang dihitung per pemeriksaan.
    'max_occurrences' => 3,

    /*
     * Teks yang terlalu pendek atau hanya terdiri dari sedikit kata dianggap
     * belum jelas. Hasil seperti ini tidak dipaksa menjadi "sesuai".
     */
    'minimum_text_length' => 12,
    'minimum_tokens' => 3,

    // Batas jumlah baris impor CSV per unggahan.
    'import_max_rows' => 5000,

    /*
     * Batas unggahan Kamus Acuan (CSV/XLSX/XLS/PDF). Nilai ini adalah batas
     * aplikasi; batas PHP (upload_max_filesize/post_max_size) dan web server
     * tetap berlaku dan ditampilkan transparan di halaman unggah.
     */
    'upload_max_kb' => (int) env('KAMUS_UPLOAD_MAX_KB', 20480),
    'upload_max_rows' => (int) env('KAMUS_UPLOAD_MAX_ROWS', 10000),
];
