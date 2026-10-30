<?php

namespace App\Support;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;

/**
 * Mengubah model database menjadi bentuk array yang dipakai komponen Blade
 * Tahap 1, sehingga tampilan tetap konsisten tanpa memakai data dummy.
 */
class PortalPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function archive(Arsip $archive): array
    {
        return [
            'id' => $archive->id,
            'nomor' => $archive->nomor ?: '—',
            'nama' => $archive->nama ?: 'Arsip tanpa judul',
            'kategori' => $archive->kategoriArsip?->nama ?? 'Tanpa kategori',
            'tanggal' => $archive->tanggal?->toDateString(),
            'anggota' => $archive->member?->nama_lengkap ?? 'Sekretariat DPRD',
            'status' => $archive->status_label,
            'keterangan' => $archive->deskripsi ?: 'Tidak ada keterangan.',
            'tone' => PortalData::toneForStatus($archive->status_label),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function member(DprdMember $member): array
    {
        return [
            'id' => $member->id,
            'nama' => $member->nama_lengkap,
            'jabatan' => $member->jabatan ?: 'Anggota DPRD',
            'partai' => $member->partai ?: '—',
            'dapil' => $member->dapil ?: '—',
            'komisi' => $member->komisi ?: '—',
            'staf' => $member->staff?->name ?? 'Belum ditugaskan',
            'inisial' => PortalData::initials($member->nama_lengkap),
            'tone' => self::toneForId($member->id),
            'arsip' => (int) ($member->arsips_count ?? 0),
            'laporan' => (int) ($member->laporan_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function report(LaporanMasyarakat $report): array
    {
        return [
            'id' => $report->id,
            'tiket' => $report->nomor_tiket,
            'pelapor' => $report->pelapor_nama,
            'kontak' => $report->pelapor_kontak ?: '—',
            'ringkasan' => $report->ringkasan,
            'judul' => $report->ringkasan,
            'kategori' => $report->kategori ?: 'Tanpa kategori',
            'kecamatan' => $report->kecamatan ?: 'Belum ditentukan',
            'tanggal' => $report->tanggal_laporan?->toDateString(),
            'tanggal_masuk' => $report->created_at?->toIso8601String(),
            'anggota' => $report->member?->nama_lengkap ?? 'Belum ditentukan',
            'staf' => $report->assignee?->name ?? 'Belum ditugaskan',
            'status' => $report->status_label,
            'status_value' => $report->status,
            'klasifikasi_status' => $report->klasifikasi_status,
            'klasifikasi_status_label' => $report->klasifikasi_status_label,
            'klasifikasi_komisi' => $report->klasifikasiKomisi?->name,
            'klasifikasi_skor' => $report->klasifikasi_skor,
            'komisi_final' => $report->komisiFinal?->name,
            'komisi_efektif' => $report->komisi_final?->name ?? $report->klasifikasiKomisi?->name,
            'klasifikasi_alasan' => $report->klasifikasi_alasan,
            'isi' => $report->isi ?: $report->ringkasan,
            'catatan' => $report->catatan_penyelesaian,
            'lampiran' => $report->hasLampiran()
                ? [
                    'nama' => $report->lampiran_nama ?: 'Lampiran',
                    'mime' => $report->lampiran_mime,
                    'ukuran' => $report->lampiran_size_label,
                ]
                : null,
            'tone' => PortalData::toneForStatus($report->status_label),
        ];
    }

    /**
     * Ubah label status dari query string menjadi nilai database.
     *
     * @param  array<string, string>  $labels
     */
    public static function statusValue(?string $label, array $labels): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }

        $value = array_search($label, $labels, true);

        return $value === false ? null : $value;
    }

    private static function toneForId(int $id): string
    {
        $tones = ['emerald', 'gold', 'blue', 'rose', 'slate', 'violet'];

        return $tones[$id % count($tones)];
    }
}
