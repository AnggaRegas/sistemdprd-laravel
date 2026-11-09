<?php

namespace App\Console\Commands;

use App\Services\KamusAcuanService;
use Illuminate\Console\Command;

class PeriksaKamusAcuan extends Command
{
    protected $signature = 'kamus:periksa
        {--tahun= : Tahun versi kamus yang dipakai (default: versi aktif)}
        {--only-outdated : Hanya periksa laporan yang belum diperiksa atau memakai versi lama}
        {--limit= : Batas jumlah laporan yang diperiksa}';

    protected $description = 'Periksa aspirasi masyarakat terhadap Kamus Acuan dan simpan hasil otomatis';

    public function handle(KamusAcuanService $kamus): int
    {
        $tahun = $this->option('tahun');
        $limit = $this->option('limit');

        if ($tahun !== null && (! ctype_digit((string) $tahun) || (int) $tahun < 2000)) {
            $this->error('Opsi --tahun harus berupa tahun yang valid.');

            return self::FAILURE;
        }

        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('Opsi --limit harus berupa bilangan bulat positif.');

            return self::FAILURE;
        }

        $result = $kamus->periksaMassal(
            null,
            (bool) $this->option('only-outdated'),
            $limit !== null ? (int) $limit : null,
            $tahun !== null ? (int) $tahun : null
        );

        if ($result['tanpa_versi']) {
            $this->error('Tidak ada versi Kamus Acuan yang cocok. Tetapkan versi aktif terlebih dahulu.');

            return self::FAILURE;
        }

        $this->info('Pemeriksaan Kamus Acuan selesai.');
        $this->line('Laporan diperiksa: '.$result['diperiksa']);
        $this->line('Laporan dilewati: '.$result['dilewati']);

        return self::SUCCESS;
    }
}
