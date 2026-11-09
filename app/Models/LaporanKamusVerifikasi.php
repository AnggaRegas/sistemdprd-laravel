<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keputusan manual Super Admin atas hasil pemeriksaan Kamus Acuan.
 * Baris ini tidak pernah dihapus agar jejak keputusan tetap tersedia.
 */
class LaporanKamusVerifikasi extends Model
{
    use HasFactory;

    protected $table = 'laporan_kamus_verifikasi';

    public const KEPUTUSAN_SESUAI = 'sesuai';

    public const KEPUTUSAN_TIDAK_DITEMUKAN = 'tidak_ditemukan';

    public const KEPUTUSAN_PERLU_VERIFIKASI = 'perlu_verifikasi';

    public const KEPUTUSAN_LABELS = [
        self::KEPUTUSAN_SESUAI => 'Ditandai sesuai kamus',
        self::KEPUTUSAN_TIDAK_DITEMUKAN => 'Ditandai tidak ditemukan',
        self::KEPUTUSAN_PERLU_VERIFIKASI => 'Ditandai perlu verifikasi',
    ];

    protected $fillable = [
        'laporan_masyarakat_id',
        'laporan_kamus_hasil_id',
        'status_sebelum',
        'status_sesudah',
        'entri_kamus_id',
        'entri_snapshot',
        'keputusan',
        'catatan',
        'diverifikasi_oleh',
        'diverifikasi_at',
    ];

    protected $casts = [
        'entri_snapshot' => 'array',
        'diverifikasi_at' => 'datetime',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function hasil(): BelongsTo
    {
        return $this->belongsTo(LaporanKamusHasil::class, 'laporan_kamus_hasil_id');
    }

    public function entri(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanEntri::class, 'entri_kamus_id')->withTrashed();
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function getKeputusanLabelAttribute(): string
    {
        return self::KEPUTUSAN_LABELS[$this->keputusan] ?? ucfirst(str_replace('_', ' ', (string) $this->keputusan));
    }

    public function getStatusSesudahLabelAttribute(): string
    {
        return LaporanKamusHasil::STATUS_LABELS[$this->status_sesudah]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status_sesudah));
    }
}
