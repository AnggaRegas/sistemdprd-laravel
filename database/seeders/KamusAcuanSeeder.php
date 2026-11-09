<?php

namespace Database\Seeders;

use App\Models\DprdCommission;
use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KamusAcuanSeeder extends Seeder
{
    /**
     * Membuat versi Kamus Acuan awal dari struktur komisi yang sudah ada.
     *
     * Tidak ada klasifikasi baru yang dikarang. Setiap entri memakai bidang,
     * deskripsi, dan kata kunci komisi sebagai sumber awal. Prioritas dibiarkan
     * kosong karena kebijakan prioritas belum ditetapkan dalam data yang ada.
     */
    public function run(): void
    {
        $tahun = (int) now()->format('Y');

        DB::transaction(function () use ($tahun) {
            $version = KamusAcuanVersi::query()->where('tahun', $tahun)->first();

            if (! $version) {
                $isActive = ! KamusAcuanVersi::query()->where('is_active', true)->exists();

                $version = KamusAcuanVersi::query()->create([
                    'tahun' => $tahun,
                    'nama' => 'Kamus Acuan '.$tahun,
                    'keterangan' => 'Data awal dibangun dari bidang dan kata kunci komisi DPRD yang sudah tersimpan.',
                    'is_active' => $isActive,
                    'ditetapkan_at' => $isActive ? now() : null,
                ]);
            }

            $commissions = DprdCommission::query()
                ->active()
                ->orderBy('id')
                ->get();

            foreach ($commissions as $commission) {
                $kode = 'KOM-'.str_pad((string) $commission->id, 2, '0', STR_PAD_LEFT);

                $exists = KamusAcuanEntri::withTrashed()
                    ->where('kamus_acuan_versi_id', $version->id)
                    ->where('kode', $kode)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $keywords = $commission->classificationKeywords();

                $entry = KamusAcuanEntri::query()->create([
                    'kamus_acuan_versi_id' => $version->id,
                    'dprd_commission_id' => $commission->id,
                    'kode' => $kode,
                    'klasifikasi' => $commission->field ?: $commission->name,
                    'uraian' => $commission->description,
                    'kata_kunci' => $keywords,
                    'sinonim' => [],
                    'kriteria' => null,
                    'prioritas' => null,
                    'tindak_lanjut' => null,
                    'is_active' => true,
                ]);

                KamusAcuanRiwayat::query()->create([
                    'kamus_acuan_versi_id' => $version->id,
                    'kamus_acuan_entri_id' => $entry->id,
                    'aksi' => 'entri_dibuat',
                    'sebelum' => null,
                    'sesudah' => $entry->fresh(['versi', 'commission'])?->toSnapshot(),
                    'catatan' => 'Data awal dari konfigurasi komisi.',
                    'user_id' => null,
                ]);
            }
        });
    }
}
