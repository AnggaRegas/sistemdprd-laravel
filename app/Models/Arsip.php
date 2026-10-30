<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arsip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arsips';

    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    public const JENIS_INTERNAL = 'internal';

    public const JENIS_LABELS = [
        self::JENIS_MASUK => 'Arsip Masuk',
        self::JENIS_KELUAR => 'Arsip Keluar',
        self::JENIS_INTERNAL => 'Arsip Internal',
    ];

    public const STATUS_DRAF = 'draf';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_TERVERIFIKASI = 'terverifikasi';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_LABELS = [
        self::STATUS_DRAF => 'Draf',
        self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
        self::STATUS_TERVERIFIKASI => 'Terverifikasi',
        self::STATUS_DITOLAK => 'Ditolak',
    ];

    protected $fillable = [
        'nomor',
        'nama',
        'kategori_arsip_id',
        'archive_folder_id',
        'dprd_member_id',
        'jenis',
        'status',
        'tanggal',
        'deskripsi',
        'file_path',
        'file_name',
        'file_size',
        'uploaded_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'file_size' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function kategoriArsip(): BelongsTo
    {
        /*
         * Kategori yang sudah dihapus tetap ditampilkan pada arsip lama agar
         * riwayat dan label dokumen tidak hilang.
         */
        return $this->belongsTo(KategoriArsip::class, 'kategori_arsip_id')->withTrashed();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ArchiveFolder::class, 'archive_folder_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS_LABELS[$this->jenis] ?? ucfirst((string) $this->jenis);
    }

    public function getFileSizeLabelAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '—';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        return number_format(max(1, (int) round($bytes / 1024)), 0, ',', '.').' KB';
    }

    public function getIsHierarchicalAttribute(): bool
    {
        return $this->archive_folder_id !== null;
    }

    /**
     * Administrator melihat seluruh arsip. Staf pendamping hanya melihat arsip
     * milik anggota DPRD yang ditugaskan kepadanya.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdministrator()) {
            return $query;
        }

        if ($user?->isStaff() && $user->dprd_member_id) {
            return $query->where('dprd_member_id', $user->dprd_member_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
