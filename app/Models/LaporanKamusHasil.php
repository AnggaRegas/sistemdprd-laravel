<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hasil pemeriksaan Kamus Acuan yang tidak boleh berubah.
 *
 * Setiap kali pemeriksaan otomatis dijalankan, baris baru dibuat. Baris
 * lama tidak ditimpa sehingga riwayat hasil otomatis tetap utuh meskipun
 * versi kamus berganti.
 */
class LaporanKamusHasil extends Model
{
    use HasFactory;

    protected $table = 'laporan_kamus_hasil';

    public const STATUS_SESUAI = 'sesuai';

    public const STATUS_TIDAK_DITEMUKAN = 'tidak_ditemukan';

    public const STATUS_PERLU_VERIFIKASI = 'perlu_verifikasi';

    public const STATUS_BELUM_DIPERIKSA = 'belum_diperiksa';

    public const STATUS_LABELS = [
        self::STATUS_SESUAI => 'SESUAI KAMUS',
        self::STATUS_TIDAK_DITEMUKAN => 'TIDAK DITEMUKAN DALAM KAMUS',
        self::STATUS_PERLU_VERIFIKASI => 'PERLU VERIFIKASI',
        self::STATUS_BELUM_DIPERIKSA => 'BELUM DIPERIKSA',
    ];

    public const STATUS_TONES = [
        self::STATUS_SESUAI => 'success',
        self::STATUS_TIDAK_DITEMUKAN => 'danger',
        self::STATUS_PERLU_VERIFIKASI => 'warning',
        self::STATUS_BELUM_DIPERIKSA => 'neutral',
    ];

    public const METODE_OTOMATIS = 'otomatis';

    public const METODE_MANUAL = 'manual';

    protected $fillable = [
        'laporan_masyarakat_id',
        'kamus_acuan_versi_id',
        'tahun_kamus',
        'status',
        'metode',
        'teks_diperiksa',
        'alasan',
        'kandidat',
        'entri_utama_id',
        'entri_utama_snapshot',
        'prioritas_rekomendasi',
        'jumlah_kandidat',
        'diperiksa_at',
        'diperiksa_oleh',
    ];

    protected $casts = [
        'tahun_kamus' => 'integer',
        'kandidat' => 'array',
        'entri_utama_snapshot' => 'array',
        'jumlah_kandidat' => 'integer',
        'diperiksa_at' => 'datetime',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanVersi::class, 'kamus_acuan_versi_id');
    }

    public function entriUtama(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanEntri::class, 'entri_utama_id')->withTrashed();
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    public function verifikasi(): HasMany
    {
        return $this->hasMany(LaporanKamusVerifikasi::class, 'laporan_kamus_hasil_id');
    }

    public function scopeStatus(Builder $query, string|array|null $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->status] ?? 'neutral';
    }

    public function getMetodeLabelAttribute(): string
    {
        return match ($this->metode) {
            'kamus_acuan_whole_word_v1' => 'Pencocokan kata/frasa utuh',
            null, '' => '—',
            default => ucfirst(str_replace('_', ' ', (string) $this->metode)),
        };
    }

    public function getEntriLabelAttribute(): string
    {
        $snapshot = $this->entri_utama_snapshot;

        if (is_array($snapshot) && ! empty($snapshot['kode'])) {
            return trim(($snapshot['kode'] ?? '').' — '.($snapshot['klasifikasi'] ?? ''), ' —');
        }

        return $this->entriUtama?->kode_label ?: 'Tidak ada entri utama';
    }

    public function getTahunLabelAttribute(): string
    {
        return $this->tahun_kamus ? (string) $this->tahun_kamus : '—';
    }

    public function getPrioritasLabelAttribute(): string
    {
        return $this->prioritas_rekomendasi ?: 'Belum ditetapkan';
    }

    /**
     * Kandidat terurut berdasarkan skor untuk ditampilkan di UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getKandidatUrutAttribute(): array
    {
        $kandidat = is_array($this->kandidat) ? $this->kandidat : [];

        usort($kandidat, static fn (array $a, array $b) => ($b['skor'] ?? 0) <=> ($a['skor'] ?? 0));

        return $kandidat;
    }
}
