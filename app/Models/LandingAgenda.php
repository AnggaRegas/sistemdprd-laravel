<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingAgenda extends Model
{
    use HasFactory;

    protected $table = 'landing_agendas';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    public const EVENT_UPCOMING = 'upcoming';
    public const EVENT_ONGOING = 'ongoing';
    public const EVENT_COMPLETED = 'completed';
    public const EVENT_CANCELLED = 'cancelled';

    public const EVENT_STATUSES = [
        self::EVENT_UPCOMING => 'Akan Datang',
        self::EVENT_ONGOING => 'Berlangsung',
        self::EVENT_COMPLETED => 'Selesai',
        self::EVENT_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'image',
        'event_status',
        'status',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->published()
            ->where('event_date', '>=', now()->toDateString());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getEventStatusLabelAttribute(): string
    {
        return self::EVENT_STATUSES[$this->event_status] ?? $this->event_status;
    }
}
