<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat satu proses analisis deteksi duplikasi untuk satu laporan.
 * Snapshot menyimpan rincian perhitungan (preprocessing, TF-IDF, cosine)
 * sebagaimana dijalankan saat analisis berlangsung.
 */
class LaporanAnalysisRun extends Model
{
    use HasFactory;

    protected $table = 'laporan_analysis_runs';

    protected $fillable = [
        'laporan_masyarakat_id',
        'dprd_member_id',
        'threshold',
        'max_results',
        'candidate_count',
        'highest_similarity',
        'is_duplicate',
        'has_detail',
        'analyzed_at',
        'algorithm_version',
        'snapshot',
    ];

    protected $casts = [
        'threshold' => 'float',
        'highest_similarity' => 'float',
        'candidate_count' => 'integer',
        'max_results' => 'integer',
        'is_duplicate' => 'boolean',
        'has_detail' => 'boolean',
        'analyzed_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function scopeTerindikasi($query)
    {
        return $query->where('is_duplicate', true);
    }

    /**
     * Label hasil deteksi yang konsisten di seluruh halaman analisis.
     */
    public function getHasilLabelAttribute(): string
    {
        return $this->is_duplicate ? 'Berpotensi Duplikat' : 'Tidak Terindikasi Duplikat';
    }
}
