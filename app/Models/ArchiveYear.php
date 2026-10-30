<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ArchiveYear extends Model
{
    use HasFactory;

    protected $table = 'archive_years';

    protected $fillable = [
        'dprd_member_id',
        'kategori_arsip_id',
        'tahun',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function kategoriArsip(): BelongsTo
    {
        return $this->belongsTo(KategoriArsip::class, 'kategori_arsip_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(ArchiveFolder::class, 'archive_year_id');
    }

    /**
     * Dokumen dihitung melewati folder agar hanya dokumen hierarkis yang
     * masuk ke ringkasan tahun anggaran.
     */
    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(
            Arsip::class,
            ArchiveFolder::class,
            'archive_year_id',
            'archive_folder_id',
            'id',
            'id'
        );
    }

    public function getLabelAttribute(): string
    {
        return (string) $this->tahun;
    }
}
