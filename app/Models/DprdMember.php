<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DprdMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dprd_members';

    protected $fillable = [
        'kode',
        'nama_lengkap',
        'jabatan',
        'partai',
        'dapil',
        'kecamatan',
        'dprd_region_id',
        'staff_id',
        'komisi',
        'email',
        'telepon',
        'foto_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getKodeLabelAttribute(): string
    {
        return $this->kode ?: 'Tanpa kode';
    }

    public function getAvatarToneAttribute(): string
    {
        $tones = ['emerald', 'gold', 'blue', 'rose', 'slate', 'violet'];

        return $tones[(int) $this->id % count($tones)];
    }

    public function getStaffLabelAttribute(): string
    {
        return $this->staffReference?->nama
            ?? $this->staff?->name
            ?? 'Belum ditugaskan';
    }

    public function getStaffAccountStatusLabelAttribute(): string
    {
        return $this->staffReference?->account_status_label ?? 'Akun belum dibuat';
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(DprdRegion::class, 'dprd_region_id');
    }

    /**
     * Data referensi staf pendamping resmi yang ditugaskan ke anggota ini.
     */
    public function staffReference(): BelongsTo
    {
        return $this->belongsTo(DprdStaff::class, 'staff_id');
    }

    /**
     * Akun login staf yang aktif. Dipertahankan untuk kompatibilitas dengan
     * modul laporan dan arsip yang sudah ada.
     */
    public function staff(): HasOne
    {
        return $this->hasOne(User::class, 'dprd_member_id')
            ->where('is_active', true);
    }

    public function activeStaff(): HasOne
    {
        return $this->staff();
    }

    public function staffAccounts(): HasMany
    {
        return $this->hasMany(User::class, 'dprd_member_id');
    }

    /**
     * Anggota aktif yang belum memiliki staf pendamping aktif.
     * Akun yang sedang diedit dapat dikecualikan agar penugasannya tidak
     * dianggap bentrok dengan dirinya sendiri.
     */
    public function scopeAvailableForStaffAssignment(Builder $query, ?int $ignoreUserId = null): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDoesntHave('activeStaff', function (Builder $staffQuery) use ($ignoreUserId) {
                if ($ignoreUserId) {
                    $staffQuery->where('users.id', '!=', $ignoreUserId);
                }
            });
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class, 'dprd_member_id');
    }

    public function archiveYears(): HasMany
    {
        return $this->hasMany(ArchiveYear::class, 'dprd_member_id');
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(LaporanMasyarakat::class, 'dprd_member_id');
    }

    public function scopeInRegion(Builder $query, int $regionId): Builder
    {
        return $query->where('dprd_region_id', $regionId);
    }

    /**
     * Administrator melihat semua anggota. Staf pendamping hanya melihat
     * anggota DPRD yang ditugaskan kepadanya.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdministrator()) {
            return $query;
        }

        if ($user?->isStaff() && $user->dprd_member_id) {
            return $query->whereKey($user->dprd_member_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
