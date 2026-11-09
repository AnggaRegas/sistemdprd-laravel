<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan perubahan kamus, baik pada tingkat versi maupun entri.
 * Data sebelum dan sesudah disimpan sebagai snapshot agar audit tetap
 * lengkap meskipun entri kemudian dihapus secara lunak.
 */
class KamusAcuanRiwayat extends Model
{
    use HasFactory;

    protected $table = 'kamus_acuan_riwayat';

    public const AKSI_LABELS = [
        'versi_dibuat' => 'Versi dibuat',
        'versi_diubah' => 'Versi diubah',
        'versi_diaktifkan' => 'Versi diaktifkan',
        'versi_dinonaktifkan' => 'Versi dinonaktifkan',
        'entri_dibuat' => 'Entri ditambahkan',
        'entri_diubah' => 'Entri diubah',
        'entri_dinonaktifkan' => 'Entri dinonaktifkan',
        'entri_diaktifkan' => 'Entri diaktifkan',
        'entri_dihapus' => 'Entri dihapus',
        'impor' => 'Impor entri',
    ];

    protected $fillable = [
        'kamus_acuan_versi_id',
        'kamus_acuan_entri_id',
        'aksi',
        'sebelum',
        'sesudah',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'sebelum' => 'array',
        'sesudah' => 'array',
    ];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanVersi::class, 'kamus_acuan_versi_id');
    }

    public function entri(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanEntri::class, 'kamus_acuan_entri_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAksiLabelAttribute(): string
    {
        return self::AKSI_LABELS[$this->aksi] ?? ucfirst(str_replace('_', ' ', (string) $this->aksi));
    }
}
