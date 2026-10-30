<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveFolder extends Model
{
    use HasFactory;

    protected $table = 'archive_folders';

    protected $fillable = [
        'archive_year_id',
        'nama',
        'deskripsi',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(ArchiveYear::class, 'archive_year_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Arsip::class, 'archive_folder_id');
    }

    public function getDocumentCountAttribute(): int
    {
        return (int) ($this->documents_count ?? $this->documents()->count());
    }
}
