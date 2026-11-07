<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPenugasan extends Model
{
    use HasFactory;

    protected $table = 'laporan_penugasans';

    public const STATUS_DITUGASKAN = 'ditugaskan';

    public const STATUS_SEDANG_DITINDAKLANJUTI = 'sedang_ditindaklanjuti';

    public const STATUS_SELESAI_DITINDAKLANJUTI = 'selesai_ditindaklanjuti';

    public const STATUS_LABELS = [
        self::STATUS_DITUGASKAN => 'Ditugaskan',
        self::STATUS_SEDANG_DITINDAKLANJUTI => 'Sedang Ditindaklanjuti',
        self::STATUS_SELESAI_DITINDAKLANJUTI => 'Selesai Ditindaklanjuti',
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_DITUGASKAN,
        self::STATUS_SEDANG_DITINDAKLANJUTI,
    ];

    protected $fillable = [
        'laporan_masyarakat_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'released_at',
        'catatan',
        'status',
        'started_at',
        'hasil_tindak_lanjut',
        'keterangan_tambahan',
        'bukti_path',
        'bukti_nama',
        'bukti_mime',
        'bukti_size',
        'completed_at',
        'completed_by',
        'verified_at',
        'verified_by',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'bukti_size' => 'integer',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('released_at');
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SELESAI_DITINDAKLANJUTI);
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI_DITINDAKLANJUTI;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getBuktiSizeLabelAttribute(): string
    {
        if (! $this->bukti_size) {
            return '—';
        }

        $kilobytes = $this->bukti_size / 1024;

        if ($kilobytes >= 1024) {
            return number_format($kilobytes / 1024, 1, ',', '.').' MB';
        }

        return number_format($kilobytes, 0, ',', '.').' KB';
    }

    public function hasBukti(): bool
    {
        return filled($this->bukti_path);
    }
}
