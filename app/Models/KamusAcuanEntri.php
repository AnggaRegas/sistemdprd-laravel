<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu entri Kamus Acuan pada satu versi tahun.
 *
 * Struktur kata kunci dan sinonim mengikuti sumber data yang sudah dipakai
 * aplikasi: daftar {term, weight}. Bobot bukan prioritas tindak lanjut,
 * melainkan kekuatan pencocokan kata kunci.
 */
class KamusAcuanEntri extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kamus_acuan_entri';

    protected $fillable = [
        'kamus_acuan_versi_id',
        'dprd_commission_id',
        'kode',
        'klasifikasi',
        'uraian',
        'kata_kunci',
        'sinonim',
        'kriteria',
        'prioritas',
        'tindak_lanjut',
        'is_active',
    ];

    protected $casts = [
        'kata_kunci' => 'array',
        'sinonim' => 'array',
        'is_active' => 'boolean',
    ];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanVersi::class, 'kamus_acuan_versi_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(DprdCommission::class, 'dprd_commission_id')->withTrashed();
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(KamusAcuanRiwayat::class, 'kamus_acuan_entri_id');
    }

    public function laporanHasil(): HasMany
    {
        return $this->hasMany(LaporanKamusHasil::class, 'entri_utama_id');
    }

    public function verifikasi(): HasMany
    {
        return $this->hasMany(LaporanKamusVerifikasi::class, 'entri_kamus_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getKodeLabelAttribute(): string
    {
        return trim($this->kode.' — '.$this->klasifikasi, ' —');
    }

    public function getPrioritasLabelAttribute(): string
    {
        return $this->prioritas ?: 'Belum ditetapkan';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getKataKunciCountAttribute(): int
    {
        return count($this->kata_kunci_list);
    }

    public function getSinonimCountAttribute(): int
    {
        return count($this->sinonim_list);
    }

    /**
     * @return array<int, array{term: string, weight: int}>
     */
    public function getKataKunciListAttribute(): array
    {
        return self::normalizeTerms($this->kata_kunci);
    }

    /**
     * @return array<int, array{term: string, weight: int}>
     */
    public function getSinonimListAttribute(): array
    {
        return self::normalizeTerms($this->sinonim);
    }

    public function getKataKunciTextAttribute(): string
    {
        return collect($this->kata_kunci_list)
            ->map(static fn (array $item) => $item['term'].'|'.$item['weight'])
            ->implode("\n");
    }

    public function getSinonimTextAttribute(): string
    {
        return collect($this->sinonim_list)
            ->map(static fn (array $item) => $item['term'].'|'.$item['weight'])
            ->implode("\n");
    }

    /**
     * Istilah yang benar-benar dipakai untuk mencocokkan aspirasi.
     * Uraian dan kriteria tidak dipecah menjadi kata karena berupa prosa dan
     * berpotensi menghasilkan kecocokan palsu.
     *
     * @return array<int, array{term: string, weight: int, sumber: string}>
     */
    public function matchingTerms(): array
    {
        $terms = [];

        foreach ([
            ['source' => 'Kata kunci', 'items' => $this->kata_kunci_list],
            ['source' => 'Sinonim', 'items' => $this->sinonim_list],
        ] as $group) {
            foreach ($group['items'] as $item) {
                $term = self::normalizeTerm($item['term']);

                if ($term === '' || mb_strlen($term) < 2) {
                    continue;
                }

                if (! isset($terms[$term]) || $terms[$term]['weight'] < $item['weight']) {
                    $terms[$term] = [
                        'term' => $term,
                        'weight' => $item['weight'],
                        'sumber' => $group['source'],
                    ];
                }
            }
        }

        $kode = self::normalizeTerm((string) $this->kode);

        if (mb_strlen($kode) >= 2 && ! isset($terms[$kode])) {
            $terms[$kode] = ['term' => $kode, 'weight' => 1, 'sumber' => 'Kode'];
        }

        $klasifikasi = self::normalizeTerm((string) $this->klasifikasi);

        if (mb_strlen($klasifikasi) >= 5 && ! isset($terms[$klasifikasi])) {
            $terms[$klasifikasi] = ['term' => $klasifikasi, 'weight' => 1, 'sumber' => 'Klasifikasi'];
        }

        return array_values($terms);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'kamus_acuan_versi_id' => $this->kamus_acuan_versi_id,
            'tahun' => $this->versi?->tahun,
            'dprd_commission_id' => $this->dprd_commission_id,
            'komisi' => $this->commission?->name,
            'kode' => $this->kode,
            'klasifikasi' => $this->klasifikasi,
            'uraian' => $this->uraian,
            'kata_kunci' => $this->kata_kunci_list,
            'sinonim' => $this->sinonim_list,
            'kriteria' => $this->kriteria,
            'prioritas' => $this->prioritas,
            'tindak_lanjut' => $this->tindak_lanjut,
            'is_active' => (bool) $this->is_active,
        ];
    }

    public static function normalizeTerm(string $term): string
    {
        $term = mb_strtolower(trim($term));
        $term = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $term) ?? '';
        $term = preg_replace('/\s+/u', ' ', $term) ?? '';

        return trim($term);
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{term: string, weight: int}>
     */
    public static function normalizeTerms(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $terms = [];

        foreach ($items as $item) {
            $term = '';
            $weight = 2;

            if (is_string($item)) {
                $term = $item;
            } elseif (is_array($item)) {
                $term = (string) ($item['term'] ?? $item['keyword'] ?? '');
                $weight = (int) ($item['weight'] ?? $item['bobot'] ?? 2);
            }

            $term = self::normalizeTerm($term);

            if ($term === '' || mb_strlen($term) > 150) {
                continue;
            }

            $weight = max(1, min(10, $weight));

            if (! isset($terms[$term]) || $terms[$term]['weight'] < $weight) {
                $terms[$term] = ['term' => $term, 'weight' => $weight];
            }
        }

        return array_values($terms);
    }

    /**
     * Format textarea: satu istilah per baris, opsional "|bobot".
     *
     * @return array{terms: array<int, array{term: string, weight: int}>, errors: array<int, string>}
     */
    public static function parseTermsText(?string $text): array
    {
        $terms = [];
        $errors = [];
        $lines = preg_split('/\R/u', (string) $text) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $term = self::normalizeTerm($parts[0] ?? '');
            $weight = 2;

            if ($term === '') {
                $errors[] = 'Baris '.($index + 1).': kata kunci tidak boleh kosong.';

                continue;
            }

            if (mb_strlen($term) > 150) {
                $errors[] = 'Baris '.($index + 1).': kata kunci maksimal 150 karakter.';

                continue;
            }

            if (count($parts) > 2) {
                $errors[] = 'Baris '.($index + 1).': gunakan format "kata kunci|bobot".';

                continue;
            }

            if (isset($parts[1]) && $parts[1] !== '') {
                if (! ctype_digit($parts[1]) || (int) $parts[1] < 1 || (int) $parts[1] > 10) {
                    $errors[] = 'Baris '.($index + 1).': bobot harus bilangan bulat 1 sampai 10.';

                    continue;
                }

                $weight = (int) $parts[1];
            }

            if (! isset($terms[$term]) || $terms[$term]['weight'] < $weight) {
                $terms[$term] = ['term' => $term, 'weight' => $weight];
            }
        }

        if (count($terms) > 250) {
            $errors[] = 'Maksimal 250 kata kunci atau sinonim per entri.';
        }

        return [
            'terms' => array_values($terms),
            'errors' => $errors,
        ];
    }
}
