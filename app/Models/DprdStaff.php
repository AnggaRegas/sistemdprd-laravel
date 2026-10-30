<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DprdStaff extends Model
{
    use HasFactory;

    protected $table = 'dprd_staffs';

    protected $fillable = [
        'kode',
        'nama',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function member(): HasOne
    {
        return $this->hasOne(DprdMember::class, 'staff_id');
    }

    public function account(): HasOne
    {
        return $this->hasOne(User::class, 'dprd_staff_id');
    }

    public function trashedAccount(): HasOne
    {
        return $this->hasOne(User::class, 'dprd_staff_id')->withTrashed();
    }

    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(StaffAssignmentHistory::class, 'dprd_staff_id')->latest('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('nama');
    }

    public function hasActiveAccount(): bool
    {
        return $this->account !== null && $this->account->is_active;
    }

    public function getAccountStatusLabelAttribute(): string
    {
        if ($this->account) {
            return $this->account->is_active ? 'Akun aktif' : 'Akun nonaktif';
        }

        if ($this->trashedAccount) {
            return 'Akun dihapus';
        }

        return 'Akun belum dibuat';
    }

    public function getAccountStatusToneAttribute(): string
    {
        if ($this->account) {
            return $this->account->is_active ? 'success' : 'danger';
        }

        if ($this->trashedAccount) {
            return 'danger';
        }

        return 'neutral';
    }
}
