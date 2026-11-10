<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanSimilarityGroupMember extends Model
{
    use HasFactory;

    protected $table = 'laporan_similarity_group_members';

    public const PERAN_ANGGOTA = 'anggota';

    public const PERAN_UTAMA = 'utama';

    public const PERAN_DUPLIKAT = 'duplikat';

    public const PERAN_DIPISAH = 'dipisah';

    public const PERAN_LABELS = [
        self::PERAN_ANGGOTA => 'Anggota Kelompok',
        self::PERAN_UTAMA => 'Aspirasi Utama',
        self::PERAN_DUPLIKAT => 'Duplikat Terverifikasi',
        self::PERAN_DIPISAH => 'Tetap Diproses Terpisah',
    ];

    protected $fillable = [
        'laporan_similarity_group_id',
        'laporan_masyarakat_id',
        'peran',
        'similarity_level',
        'similarity_score',
        'evidence',
        'decided_by',
        'decided_at',
        'catatan',
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'evidence' => 'array',
        'decided_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LaporanSimilarityGroup::class, 'laporan_similarity_group_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getPeranLabelAttribute(): string
    {
        return self::PERAN_LABELS[$this->peran] ?? ucfirst((string) $this->peran);
    }
}
