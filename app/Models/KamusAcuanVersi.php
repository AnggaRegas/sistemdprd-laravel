<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Versi Kamus Acuan untuk satu tahun.
 *
 * Hanya satu versi yang boleh aktif pada satu waktu. Versi lama tetap
 * disimpan agar hasil pemeriksaan historis tidak berubah ketika kamus
 * diperbarui.
 */
class KamusAcuanVersi extends Model
{
    use HasFactory;

    protected $table = 'kamus_acuan_versi';

    protected $fillable = [
        'tahun',
        'nama',
        'keterangan',
        'is_active',
        'ditetapkan_oleh',
        'ditetapkan_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'is_active' => 'boolean',
        'ditetapkan_at' => 'datetime',
    ];

    public function entri(): HasMany
    {
        return $this->hasMany(KamusAcuanEntri::class, 'kamus_acuan_versi_id');
    }

    public function entriAktif(): HasMany
    {
        return $this->entri()->where('is_active', true);
    }

    public function kriteria(): HasMany
    {
        return $this->hasMany(KamusKriteria::class, 'kamus_acuan_versi_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(KamusAcuanRiwayat::class, 'kamus_acuan_versi_id');
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(LaporanKamusHasil::class, 'kamus_acuan_versi_id');
    }

    public function ditetapkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditetapkan_oleh');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTahun(Builder $query, int|string|null $tahun): Builder
    {
        if ($tahun === null || $tahun === '') {
            return $query;
        }

        return $query->where('tahun', (int) $tahun);
    }

    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderByDesc('tahun');
    }

    public function getLabelAttribute(): string
    {
        return $this->nama ?: 'Kamus Acuan '.$this->tahun;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public static function aktif(): ?self
    {
        return static::query()->active()->orderByDesc('tahun')->first();
    }
}
