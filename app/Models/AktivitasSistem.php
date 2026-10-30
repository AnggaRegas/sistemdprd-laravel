<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AktivitasSistem extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_sistem';

    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'deskripsi',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat aktivitas tanpa pernah menggagalkan alur utama aplikasi.
     */
    public static function catat(
        string $event,
        ?User $user = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $deskripsi = null
    ): ?self {
        try {
            $request = app()->runningInConsole() ? null : request();

            return static::create([
                'user_id' => $user?->id,
                'event' => $event,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'deskripsi' => $deskripsi,
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
