<?php

namespace Database\Seeders;

use App\Models\KategoriArsip;
use Illuminate\Database\Seeder;

class KategoriArsipSeeder extends Seeder
{
    /**
     * Kategori awal yang dipakai pada antarmuka arsip. Seeder bersifat
     * idempotent dan tidak menimpa kategori yang sudah ada.
     */
    public function run(): void
    {
        $categories = [
            [
                'nama' => 'HASIL RESES',
                'kode' => 'RESES',
                'deskripsi' => 'Arsip resmi dokumen hasil kegiatan reses anggota DPRD.',
            ],
            [
                'nama' => 'POKOK-POKOK PIKIRAN',
                'kode' => 'POKIR',
                'deskripsi' => 'Arsip resmi dokumen pokok-pokok pikiran anggota DPRD.',
            ],
        ];

        foreach ($categories as $category) {
            /*
             * withTrashed() mencegah seeder membuat baris baru ketika
             * kategori pernah dinonaktifkan atau dihapus secara lunak.
             * Data yang sudah ada tidak ditimpa.
             */
            KategoriArsip::withTrashed()->firstOrCreate(
                ['nama' => $category['nama']],
                [
                    'kode' => $category['kode'],
                    'deskripsi' => $category['deskripsi'],
                    'is_active' => true,
                ]
            );
        }
    }
}
