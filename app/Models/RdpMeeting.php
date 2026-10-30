<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RdpMeeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdp_meetings';

    public const STATUS_DRAF = 'draf';

    public const STATUS_TERJADWAL = 'terjadwal';

    public const STATUS_BERLANGSUNG = 'berlangsung';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const STATUS_LABELS = [
        self::STATUS_DRAF => 'Draf',
        self::STATUS_TERJADWAL => 'Terjadwal',
        self::STATUS_BERLANGSUNG => 'Berlangsung',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    protected $fillable = [
        'dprd_commission_id',
        'title',
        'code',
        'meeting_date',
        'start_time',
        'location',
        'description',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(DprdCommission::class, 'dprd_commission_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RdpDocument::class, 'rdp_meeting_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SELESAI => 'success',
            self::STATUS_BERLANGSUNG => 'info',
            self::STATUS_TERJADWAL => 'gold',
            self::STATUS_DIBATALKAN => 'danger',
            default => 'neutral',
        };
    }

    public function getStartTimeLabelAttribute(): string
    {
        if (! $this->start_time) {
            return '—';
        }

        return Carbon::parse($this->start_time)->format('H:i');
    }

    public function getScheduleLabelAttribute(): string
    {
        $date = $this->meeting_date
            ? $this->meeting_date->locale('id')->translatedFormat('d F Y')
            : 'Tanggal belum ditentukan';

        return $this->start_time
            ? $date.' · '.$this->start_time_label.' WITA'
            : $date;
    }
}
