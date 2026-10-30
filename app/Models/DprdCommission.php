<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class DprdCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dprd_commissions';

    protected $fillable = [
        'name',
        'slug',
        'field',
        'description',
        'keywords',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];

    public function rdpMeetings(): HasMany
    {
        return $this->hasMany(RdpMeeting::class, 'dprd_commission_id');
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(
            RdpDocument::class,
            RdpMeeting::class,
            'dprd_commission_id',
            'rdp_meeting_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Kata kunci yang dipakai classifier. Data lama berbentuk string juga
     * tetap dibaca agar perubahan skema tidak menghilangkan konfigurasi.
     *
     * @return array<int, array{term: string, weight: int}>
     */
    public function classificationKeywords(): array
    {
        $raw = is_array($this->keywords) ? $this->keywords : [];
        $keywords = [];

        foreach ($raw as $item) {
            $term = '';
            $weight = 2;

            if (is_string($item)) {
                $term = $item;
            } elseif (is_array($item)) {
                $term = (string) ($item['term'] ?? $item['keyword'] ?? '');
                $weight = (int) ($item['weight'] ?? $item['bobot'] ?? 2);
            }

            $term = self::normalizeKeywordTerm($term);

            if ($term === '') {
                continue;
            }

            $weight = max(1, min(10, $weight));

            if (! isset($keywords[$term]) || $keywords[$term]['weight'] < $weight) {
                $keywords[$term] = ['term' => $term, 'weight' => $weight];
            }
        }

        return array_values($keywords);
    }

    public function getKeywordsTextAttribute(): string
    {
        return collect($this->classificationKeywords())
            ->map(static fn (array $keyword) => $keyword['term'].'|'.$keyword['weight'])
            ->implode("\n");
    }

    public static function normalizeKeywordTerm(string $term): string
    {
        $term = mb_strtolower(trim($term));
        $term = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $term) ?? '';
        $term = preg_replace('/\s+/u', ' ', $term) ?? '';

        return trim($term);
    }

    /**
     * Format textarea: satu kata kunci/frasa per baris, opsional "|bobot".
     * Baris tanpa bobot memakai bobot 2.
     *
     * @return array{keywords: array<int, array{term: string, weight: int}>, errors: array<int, string>}
     */
    public static function parseKeywordsText(?string $text): array
    {
        $keywords = [];
        $errors = [];
        $lines = preg_split('/\R/u', (string) $text) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $term = self::normalizeKeywordTerm($parts[0] ?? '');
            $weight = 2;

            if ($term === '') {
                $errors[] = 'Baris '.($index + 1).': kata kunci tidak boleh kosong.';

                continue;
            }

            if (mb_strlen($term) > 100) {
                $errors[] = 'Baris '.($index + 1).': kata kunci maksimal 100 karakter.';

                continue;
            }

            if (count($parts) > 2) {
                $errors[] = 'Baris '.($index + 1).': gunakan format "kata kunci|bobot".';

                continue;
            }

            if (isset($parts[1]) && $parts[1] !== '') {
                if (! ctype_digit($parts[1]) || (int) $parts[1] < 1 || (int) $parts[1] > 10) {
                    $errors[] = 'Baris '.($index + 1).': bobot harus bilangan bulat 1 sampai 10.';

                    continue;
                }

                $weight = (int) $parts[1];
            }

            if (! isset($keywords[$term]) || $keywords[$term]['weight'] < $weight) {
                $keywords[$term] = ['term' => $term, 'weight' => $weight];
            }
        }

        if (count($keywords) > 150) {
            $errors[] = 'Maksimal 150 kata kunci per komisi.';
        }

        return [
            'keywords' => array_values($keywords),
            'errors' => $errors,
        ];
    }

    public function getIconAttribute(): string
    {
        return match ($this->slug) {
            'komisi-1' => 'ri-government-line',
            'komisi-2' => 'ri-line-chart-line',
            'komisi-3' => 'ri-heart-3-line',
            default => 'ri-building-2-line',
        };
    }
}
