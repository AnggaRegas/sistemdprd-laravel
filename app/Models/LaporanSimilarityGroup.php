<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelompok aspirasi yang memiliki kemiripan tinggi/sedang pada satu anggota
 * DPRD. Kelompok tidak menghapus aspirasi apa pun; ia hanya menandai
 * keterkaitan dan menyimpan keputusan Super Admin.
 */
class LaporanSimilarityGroup extends Model
{
    use HasFactory;

    protected $table = 'laporan_similarity_groups';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIPUTUSKAN = 'diputuskan';

    public const STATUS_DITUNDA = 'ditunda';

    public const STATUS_DIBUBARKAN = 'dibubarkan';

    public const STATUS_LABELS = [
        self::STATUS_MENUNGGU => 'Menunggu Verifikasi',
        self::STATUS_DIPUTUSKAN => 'Sudah Diputuskan',
        self::STATUS_DITUNDA => 'Ditunda',
        self::STATUS_DIBUBARKAN => 'Dibubarkan',
    ];

    public const STATUS_TONES = [
        self::STATUS_MENUNGGU => 'warning',
        self::STATUS_DIPUTUSKAN => 'success',
        self::STATUS_DITUNDA => 'info',
        self::STATUS_DIBUBARKAN => 'neutral',
    ];

    protected $fillable = [
        'dprd_member_id',
        'kode',
        'status',
        'main_laporan_id',
        'decided_by',
        'decided_at',
        'catatan',
        'algorithm_version',
        'threshold_high',
        'threshold_medium',
        'snapshot',
    ];

    protected $casts = [
        'main_laporan_id' => 'integer',
        'decided_at' => 'datetime',
        'threshold_high' => 'float',
        'threshold_medium' => 'float',
        'snapshot' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function mainLaporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'main_laporan_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LaporanSimilarityGroupMember::class, 'laporan_similarity_group_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(LaporanSimilarityDecision::class, 'laporan_similarity_group_id');
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('dprd_member_id', $memberId);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', '<>', self::STATUS_DIBUBARKAN);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->status] ?? 'neutral';
    }

    public function isDecided(): bool
    {
        return $this->decided_at !== null;
    }
}
