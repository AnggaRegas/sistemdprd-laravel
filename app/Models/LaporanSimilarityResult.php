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
        'checked_at',
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'checked_at' => 'datetime',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function comparedLaporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'compared_laporan_masyarakat_id');
    }

    /**
     * Hasil dengan skor di atas ambang yang disimpan pada konfigurasi.
     */
    public function scopeDiAtasAmbang($query)
    {
        return $query
            ->where('similarity_score', '>=', (float) config('aspiration_similarity.threshold'))
            ->orderByDesc('similarity_score');
    }
}
