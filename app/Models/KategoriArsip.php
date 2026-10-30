<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriArsip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kategori_arsips';

    protected $fillable = [
        'nama',
        'kode',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class, 'kategori_arsip_id');
    }

    public function archiveYears(): HasMany
    {
        return $this->hasMany(ArchiveYear::class, 'kategori_arsip_id');
    }
}
