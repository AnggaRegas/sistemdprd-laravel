<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanSimilarityResult extends Model
{
    use HasFactory;

    protected $table = 'laporan_similarity_results';

    protected $fillable = [
        'laporan_masyarakat_id',
        'compared_laporan_masyarakat_id',
        'similarity_score',
        'similarity_level',
        'evidence',
        'checked_at',
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'evidence' => 'array',
        'checked_at' => 'datetime',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function comparedLaporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'compared_laporan_masyarakat_id')->withTrashed();
    }

    /**
     * Hasil yang benar-benar lolos gerbang skor ganda (lokasi DAN inti
     * permasalahan). Hanya baris dengan tingkat "tinggi" yang disimpan oleh
     * analisis otomatis sehingga label tidak pernah muncul dari skor lama.
     */
    public function scopeDiAtasAmbang($query)
    {
        return $query
            ->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI)
            ->orderByDesc('similarity_score');
    }

    public function getLevelLabelAttribute(): string
    {
        return LaporanMasyarakat::SIMILARITY_LEVEL_LABELS[$this->similarity_level]
            ?? LaporanMasyarakat::SIMILARITY_LEVEL_LABELS[
                $this->similarity_score >= (float) config('aspiration_similarity.high_threshold', 0.80)
                    ? LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI
                    : LaporanMasyarakat::SIMILARITY_LEVEL_SEDANG
            ];
    }
}
