<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanMasyarakat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laporan_masyarakat';

    public const STATUS_BARU = 'baru';

    public const STATUS_MENUNGGU_PENUGASAN = 'menunggu_penugasan';

    public const STATUS_DITUGASKAN = 'ditugaskan';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_LABELS = [
        self::STATUS_BARU => 'Baru',
        self::STATUS_MENUNGGU_PENUGASAN => 'Menunggu Penugasan',
        self::STATUS_DITUGASKAN => 'Ditugaskan',
        self::STATUS_DIPROSES => 'Diproses',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DITOLAK => 'Ditolak',
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_BARU,
        self::STATUS_MENUNGGU_PENUGASAN,
        self::STATUS_DITUGASKAN,
        self::STATUS_DIPROSES,
    ];

    /**
     * Alur status yang sah. Transisi di luar peta ini ditolak di backend.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_BARU => [
            self::STATUS_MENUNGGU_PENUGASAN,
            self::STATUS_DITUGASKAN,
            self::STATUS_DITOLAK,
        ],
        self::STATUS_MENUNGGU_PENUGASAN => [
            self::STATUS_DITUGASKAN,
            self::STATUS_DITOLAK,
        ],
        self::STATUS_DITUGASKAN => [
            self::STATUS_DIPROSES,
            self::STATUS_MENUNGGU_PENUGASAN,
            self::STATUS_DITOLAK,
        ],
        self::STATUS_DIPROSES => [
            self::STATUS_SELESAI,
            self::STATUS_DITOLAK,
        ],
        self::STATUS_SELESAI => [],
        self::STATUS_DITOLAK => [],
    ];

    public const KATEGORI_OPTIONS = [
        'Infrastruktur dan fasilitas umum',
        'Pendidikan',
        'Kesehatan',
        'Sosial dan kesejahteraan',
        'Ekonomi dan UMKM',
        'Pemerintahan dan pelayanan publik',
        'Lainnya',
    ];

    public const KECAMATAN_OPTIONS = [
        'Kecamatan Bacukiki',
        'Kecamatan Bacukiki Barat',
        'Kecamatan Ujung',
        'Kecamatan Soreang',
    ];

    public const KLASIFIKASI_DIREKOMENDASIKAN = 'direkomendasikan';

    public const KLASIFIKASI_PERLU_DITINJAU = 'perlu_ditinjau';

    public const KLASIFIKASI_STATUS_LABELS = [
        self::KLASIFIKASI_DIREKOMENDASIKAN => 'Direkomendasikan',
        self::KLASIFIKASI_PERLU_DITINJAU => 'Perlu Ditinjau',
    ];

    protected $fillable = [
        'nomor_tiket',
        'pelapor_nama',
        'pelapor_kontak',
        'pelapor_alamat',
        'ringkasan',
        'isi',
        'kategori',
        'kecamatan',
        'dprd_region_id',
        'klasifikasi_komisi_id',
        'klasifikasi_skor',
        'klasifikasi_skor_kedua',
        'klasifikasi_keywords',
        'klasifikasi_skor_detail',
        'klasifikasi_metode',
        'klasifikasi_status',
        'klasifikasi_alasan',
        'klasifikasi_dianalisis_at',
        'kamus_status',
        'kamus_status_otomatis',
        'kamus_hasil_id',
        'kamus_entri_id',
        'kamus_versi_id',
        'kamus_tahun',
        'kamus_prioritas',
        'kamus_diperiksa_at',
        'kamus_diverifikasi_oleh',
        'kamus_diverifikasi_at',
        'kamus_catatan_verifikasi',
        'komisi_final_id',
        'komisi_final_ditetapkan_oleh',
        'komisi_final_ditetapkan_at',
        'lampiran_path',
        'lampiran_nama',
        'lampiran_mime',
        'lampiran_size',
        'dprd_member_id',
        'assigned_to',
        'assigned_at',
        'disetujui_oleh',
        'disetujui_at',
        'status',
        'tanggal_laporan',
        'ditutup_at',
        'catatan_penyelesaian',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'assigned_at' => 'datetime',
        'ditutup_at' => 'datetime',
        'lampiran_size' => 'integer',
        'klasifikasi_skor' => 'float',
        'klasifikasi_skor_kedua' => 'float',
        'klasifikasi_keywords' => 'array',
        'klasifikasi_skor_detail' => 'array',
        'klasifikasi_dianalisis_at' => 'datetime',
        'kamus_tahun' => 'integer',
        'kamus_diperiksa_at' => 'datetime',
        'kamus_diverifikasi_at' => 'datetime',
        'komisi_final_ditetapkan_at' => 'datetime',
        'disetujui_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(DprdRegion::class, 'dprd_region_id');
    }

    public function klasifikasiKomisi(): BelongsTo
    {
        return $this->belongsTo(DprdCommission::class, 'klasifikasi_komisi_id');
    }

    public function komisiFinal(): BelongsTo
    {
        return $this->belongsTo(DprdCommission::class, 'komisi_final_id');
    }

    public function komisiFinalOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'komisi_final_ditetapkan_oleh');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function penugasans(): HasMany
    {
        return $this->hasMany(LaporanPenugasan::class, 'laporan_masyarakat_id');
    }

    public function penugasanAktif(): HasOne
    {
        return $this->hasOne(LaporanPenugasan::class, 'laporan_masyarakat_id')
            ->whereNull('released_at')
            ->latestOfMany();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function similarityResults(): HasMany
    {
        return $this->hasMany(LaporanSimilarityResult::class, 'laporan_masyarakat_id');
    }

    public function comparedSimilarityResults(): HasMany
    {
        return $this->hasMany(LaporanSimilarityResult::class, 'compared_laporan_masyarakat_id');
    }

    public function kamusHasil(): BelongsTo
    {
        return $this->belongsTo(LaporanKamusHasil::class, 'kamus_hasil_id');
    }

    public function kamusEntri(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanEntri::class, 'kamus_entri_id')->withTrashed();
    }

    public function kamusVersi(): BelongsTo
    {
        return $this->belongsTo(KamusAcuanVersi::class, 'kamus_versi_id');
    }

    public function kamusDiverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kamus_diverifikasi_oleh');
    }

    public function kamusHasilList(): HasMany
    {
        return $this->hasMany(LaporanKamusHasil::class, 'laporan_masyarakat_id')->latest('id');
    }

    public function kamusVerifikasi(): HasMany
    {
        return $this->hasMany(LaporanKamusVerifikasi::class, 'laporan_masyarakat_id')->latest('id');
    }

    public function getKamusStatusLabelAttribute(): string
    {
        return LaporanKamusHasil::STATUS_LABELS[$this->kamus_status]
            ?? 'BELUM DIPERIKSA';
    }

    public function getKamusStatusToneAttribute(): string
    {
        return LaporanKamusHasil::STATUS_TONES[$this->kamus_status] ?? 'neutral';
    }

    public function getKamusStatusOtomatisLabelAttribute(): string
    {
        if (! $this->kamus_status_otomatis) {
            return 'Belum diperiksa';
        }

        return LaporanKamusHasil::STATUS_LABELS[$this->kamus_status_otomatis]
            ?? ucfirst(str_replace('_', ' ', (string) $this->kamus_status_otomatis));
    }

    public function getKamusPrioritasLabelAttribute(): string
    {
        return $this->kamus_prioritas ?: 'Belum ditetapkan';
    }

    public function getKamusTahunLabelAttribute(): string
    {
        return $this->kamus_tahun ? (string) $this->kamus_tahun : '—';
    }

    public function getKamusEntriLabelAttribute(): string
    {
        if ($this->kamusEntri) {
            return $this->kamusEntri->kode_label;
        }

        $snapshot = $this->kamusHasil?->entri_utama_snapshot;

        if (is_array($snapshot) && ! empty($snapshot['kode'])) {
            return trim(($snapshot['kode'] ?? '').' — '.($snapshot['klasifikasi'] ?? ''), ' —');
        }

        return 'Belum ada entri';
    }

    public function hasKamusResult(): bool
    {
        return $this->kamus_status !== null && $this->kamus_status !== LaporanKamusHasil::STATUS_BELUM_DIPERIKSA;
    }

    public function isKamusVerified(): bool
    {
        return $this->kamus_diverifikasi_at !== null;
    }

    public function scopeKamusStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        if ($status === LaporanKamusHasil::STATUS_BELUM_DIPERIKSA) {
            return $query->where(function (Builder $query) {
                $query->whereNull('kamus_status')
                    ->orWhere('kamus_status', LaporanKamusHasil::STATUS_BELUM_DIPERIKSA);
            });
        }

        return $query->where('kamus_status', $status);
    }

    public function scopeKamusTahun(Builder $query, int|string|null $tahun): Builder
    {
        if ($tahun === null || $tahun === '') {
            return $query;
        }

        return $query->where('kamus_tahun', (int) $tahun);
    }

    public function getDisetujuiAtLabelAttribute(): string
    {
        return \App\Support\PortalData::dateTime($this->disetujui_at);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getKecamatanLabelAttribute(): string
    {
        return $this->kecamatan ?: 'Belum ditentukan';
    }

    public function getKlasifikasiStatusLabelAttribute(): string
    {
        return self::KLASIFIKASI_STATUS_LABELS[$this->klasifikasi_status]
            ?? 'Belum dianalisis';
    }

    public function getKlasifikasiKomisiLabelAttribute(): string
    {
        return $this->klasifikasiKomisi?->name ?: 'Belum terklasifikasi';
    }

    public function getKomisiFinalLabelAttribute(): string
    {
        return $this->komisiFinal?->name ?: 'Belum ditetapkan';
    }

    public function getKomisiEfektifLabelAttribute(): string
    {
        return $this->komisiFinal?->name
            ?? $this->klasifikasiKomisi?->name
            ?? 'Belum terklasifikasi';
    }

    public function hasClassification(): bool
    {
        return $this->klasifikasi_dianalisis_at !== null;
    }

    public function getLampiranSizeLabelAttribute(): string
    {
        if (! $this->lampiran_size) {
            return '—';
        }

        $kilobytes = $this->lampiran_size / 1024;

        if ($kilobytes >= 1024) {
            return number_format($kilobytes / 1024, 1, ',', '.').' MB';
        }

        return number_format($kilobytes, 0, ',', '.').' KB';
    }

    public function hasLampiran(): bool
    {
        return filled($this->lampiran_path);
    }

    /**
     * Status yang boleh dipilih administrator dari status saat ini.
     *
     * @return array<int, string>
     */
    public function availableStatuses(): array
    {
        $statuses = [$this->status];

        foreach (self::STATUS_TRANSITIONS[$this->status] ?? [] as $status) {
            if ($status === self::STATUS_DITUGASKAN && ! $this->assigned_to) {
                continue;
            }

            $statuses[] = $status;
        }

        return array_values(array_unique($statuses));
    }

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return true;
        }

        if ($status === self::STATUS_DITUGASKAN && ! $this->assigned_to) {
            return false;
        }

        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Administrator melihat semua laporan. Staf pendamping hanya melihat
     * laporan yang secara eksplisit ditugaskan kepadanya.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdministrator()) {
            return $query;
        }

        if ($user?->isStaff()) {
            return $query
                ->where('assigned_to', $user->id)
                ->where('dprd_member_id', $user->dprd_member_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public static function statusCountsFor(User $user): array
    {
        $counts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);

        $rows = static::query()
            ->visibleTo($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        foreach ($rows as $status => $total) {
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $total;
            }
        }

        return $counts;
    }

    public static function statusLabelCountsFor(User $user): array
    {
        $counts = [];

        foreach (self::statusCountsFor($user) as $status => $total) {
            $counts[self::STATUS_LABELS[$status] ?? $status] = $total;
        }

        return $counts;
    }

    public static function badgeCountFor(User $user): int
    {
        if ($user->isAdministrator()) {
            return static::query()
                ->where('status', self::STATUS_MENUNGGU_PENUGASAN)
                ->count();
        }

        return static::query()
            ->where('assigned_to', $user->id)
            ->where('dprd_member_id', $user->dprd_member_id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->count();
    }
}
