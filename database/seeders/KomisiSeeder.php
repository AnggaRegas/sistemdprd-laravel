<?php

namespace Database\Seeders;

use App\Models\DprdCommission;
use Illuminate\Database\Seeder;

class KomisiSeeder extends Seeder
{
    /**
     * Seed tiga komisi awal DPRD Kota Parepare.
     *
     * Seeder ini idempoten: data dicocokkan berdasarkan slug sehingga aman
     * dijalankan berulang kali tanpa membuat duplikasi. Tidak ada RDP atau
     * dokumen palsu yang dibuat di sini.
     */
    public function run(): void
    {
        $commissions = [
            [
                'name' => 'Komisi 1',
                'slug' => 'komisi-1',
                'field' => 'Pemerintahan, Hukum, dan Ketertiban',
                'description' => 'Membidangi urusan pemerintahan, hukum, peraturan daerah, serta ketertiban umum dan perlindungan masyarakat.',
                'is_active' => true,
            ],
            [
                'name' => 'Komisi 2',
                'slug' => 'komisi-2',
                'field' => 'Ekonomi, Keuangan, dan Pembangunan',
                'description' => 'Membidangi urusan ekonomi, keuangan, anggaran daerah, investasi, dan pembangunan infrastruktur.',
                'is_active' => true,
            ],
            [
                'name' => 'Komisi 3',
                'slug' => 'komisi-3',
                'field' => 'Kesejahteraan Rakyat dan Pendidikan',
                'description' => 'Membidangi urusan kesejahteraan rakyat, pendidikan, kesehatan, sosial, dan pemberdayaan masyarakat.',
                'is_active' => true,
            ],
        ];

        foreach ($commissions as $commission) {
            $commission['keywords'] = config('aspiration_classification.defaults.'.$commission['slug'], []);

            $existing = DprdCommission::withTrashed()
                ->where('slug', $commission['slug'])
                ->first();

            if ($existing) {
                if (! $existing->trashed() && $existing->keywords === null) {
                    $existing->update(['keywords' => $commission['keywords']]);
                }

                continue;
            }

            DprdCommission::query()->create($commission);
        }
    }
}
