<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_STAF_PENDAMPING = 'staf_pendamping';

    public const ROLES = [
        self::ROLE_ADMINISTRATOR,
        self::ROLE_STAF_PENDAMPING,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * Role dan dprd_member_id sengaja tidak dimasukkan agar tidak bisa diubah
     * lewat mass assignment dari request biasa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function staffReference(): BelongsTo
    {
        return $this->belongsTo(DprdStaff::class, 'dprd_staff_id');
    }

    /**
     * Akun hanya boleh dipakai jika data referensi stafnya masih ditugaskan
     * ke anggota yang sama dengan lingkup akses akun.
     */
    public function staffReferenceMatchesMember(): bool
    {
        if (! $this->dprd_staff_id || ! $this->dprd_member_id) {
            return true;
        }

        return DprdMember::query()
            ->whereKey($this->dprd_member_id)
            ->where('staff_id', $this->dprd_staff_id)
            ->exists();
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(LaporanMasyarakat::class, 'assigned_to');
    }

    public function reportAssignments(): HasMany
    {
        return $this->hasMany(LaporanPenugasan::class, 'user_id');
    }

    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(LaporanPenugasan::class, 'assigned_by');
    }

    public function uploadedArchives(): HasMany
    {
        return $this->hasMany(Arsip::class, 'uploaded_by');
    }

    public function verifiedArchives(): HasMany
    {
        return $this->hasMany(Arsip::class, 'verified_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AktivitasSistem::class, 'user_id');
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function rdpDocuments(): HasMany
    {
        return $this->hasMany(RdpDocument::class, 'uploaded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAF_PENDAMPING;
    }

    public function hasRole(string ...$roles): bool
    {
        return $roles !== [] && in_array($this->role, $roles, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMINISTRATOR => 'Administrator',
            self::ROLE_STAF_PENDAMPING => 'Staf Pendamping',
            default => 'Tanpa role',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function dashboardRouteName(): string
    {
        return $this->isStaff() ? 'dashboard.staf' : 'dashboard';
    }
}
