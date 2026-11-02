<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingNews extends Model
{
    use HasFactory;

    protected $table = 'landing_news';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    public const CATEGORIES = [
        'kegiatan' => 'Kegiatan',
        'pengumuman' => 'Pengumuman',
        'berita' => 'Berita',
        'publikasi' => 'Publikasi',
    ];

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'thumbnail',
        'published_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
