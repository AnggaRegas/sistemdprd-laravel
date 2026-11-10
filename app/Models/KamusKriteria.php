<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kriteria/bobot perhitungan pada satu versi Kamus Acuan.
 *
 * Baris di sini bukan sekadar catatan: KamusAcuanService membaca kriteria
 * aktif untuk menghitung skor pencocokan (SPK). Karena itu perubahan bobot
 * langsung memengaruhi hasil pemeriksaan berikutnya.
 */
class KamusKriteria extends Model
{
    use HasFactory;

    protected $table = 'kamus_kriteria';

    /**
     * Faktor perhitungan yang didukung. Kode dipakai sebagai kunci map
     * perhitungan sehingga tidak boleh berubah sembarangan.
     *
     * @var array<string, array{nama: string, bobot: float, satuan: string, keterangan: string}>
     */
    public const FAKTORS = [
        'bobot_kata_kunci' => [
            'nama' => 'Bobot kata kunci',
            'bobot' => 1.0,
            'satuan' => 'pengali',
            'keterangan' => 'Pengali untuk istilah yang berasal dari kolom kata kunci entri kamus.',
        ],
        'bobot_sinonim' => [
            'nama' => 'Bobot sinonim',
            'bobot' => 0.8,
            'satuan' => 'pengali',
            'keterangan' => 'Pengali untuk istilah yang berasal dari kolom sinonim entri kamus.',
        ],
        'bobot_frasa' => [
            'nama' => 'Bonus frasa',
            'bobot' => 1.25,
            'satuan' => 'pengali',
            'keterangan' => 'Pengali tambahan bila istilah cocok berupa frasa (lebih dari satu kata).',
        ],
        'maks_kemunculan' => [
            'nama' => 'Batas kemunculan istilah',
            'bobot' => 3,
            'satuan' => 'kali',
            'keterangan' => 'Jumlah maksimum kemunculan satu istilah yang ikut dihitung per pemeriksaan.',
        ],
        'skor_minimum' => [
            'nama' => 'Skor minimum sesuai kamus',
            'bobot' => 4,
            'satuan' => 'skor',
            'keterangan' => 'Skor minimal agar satu kandidat tunggal dinyatakan sesuai kamus.',
        ],
        'panjang_minimum_teks' => [
            'nama' => 'Panjang minimum teks',
            'bobot' => 12,
            'satuan' => 'karakter',
            'keterangan' => 'Teks aspirasi di bawah batas ini dianggap belum jelas dan perlu verifikasi.',
        ],
        'minimal_jumlah_kata' => [
            'nama' => 'Jumlah minimum kata',
            'bobot' => 3,
            'satuan' => 'kata',
            'keterangan' => 'Jumlah kata minimum agar teks aspirasi dianggap cukup jelas.',
        ],
    ];

    protected $fillable = [
        'kamus_acuan_versi_id',
        'kode',
        'nama',
        'bobot',
        'satuan',
        'keterangan',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'bobot' => 'float',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanVersi::class, 'kamus_acuan_versi_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('id');
    }

    public function getFaktorLabelAttribute(): string
    {
        return self::FAKTORS[$this->kode]['nama'] ?? $this->nama;
    }

    /**
     * Daftar kriteria bawaan untuk versi baru.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        $rows = [];
        $urutan = 1;

        foreach (self::FAKTORS as $kode => $faktor) {
            $rows[] = [
                'kode' => $kode,
                'nama' => $faktor['nama'],
                'bobot' => $faktor['bobot'],
                'satuan' => $faktor['satuan'],
                'keterangan' => $faktor['keterangan'],
                'is_active' => true,
                'urutan' => $urutan++,
            ];
        }

        return $rows;
    }

    /**
     * Nilai bawaan dalam bentuk map kode => bobot.
     *
     * @return array<string, float>
     */
    public static function defaultValues(): array
    {
        return collect(self::FAKTORS)
            ->map(fn (array $faktor) => (float) $faktor['bobot'])
            ->all();
    }
}
