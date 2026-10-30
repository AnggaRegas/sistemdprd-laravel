<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Hanya mengizinkan kembali ke daftar laporan dengan filter yang dikenal.
 * Nilai return_to berasal dari request sehingga tidak boleh dipercaya
 * mentah-mentah (open redirect / query injection).
 */
class SafeReturnUrl
{
    /**
     * @var array<int, string>
     */
    public const ALLOWED_QUERY = [
        'q',
        'status',
        'kategori',
        'kecamatan',
        'tanggal_dari',
        'tanggal_sampai',
        'page',
    ];

    public static function reportsIndex(?string $candidate, Request $request): string
    {
        $fallback = route('laporan.index');
        $candidate = trim((string) $candidate);

        if ($candidate === '') {
            return $fallback;
        }

        $parts = parse_url($candidate);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return $fallback;
        }

        if (isset($parts['scheme'])
            && ! in_array(mb_strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return $fallback;
        }

        if (isset($parts['host']) && ! hash_equals(mb_strtolower($request->getHost()), mb_strtolower((string) $parts['host']))) {
            return $fallback;
        }

        if (isset($parts['port']) && (int) $parts['port'] !== (int) $request->getPort()) {
            return $fallback;
        }

        $indexPath = (string) (parse_url($fallback, PHP_URL_PATH) ?: '/laporan');
        $path = (string) ($parts['path'] ?? '');

        if ($path === '') {
            $path = $indexPath;
        }

        if (rtrim($path, '/') !== rtrim($indexPath, '/')) {
            return $fallback;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $query = array_intersect_key($query, array_flip(self::ALLOWED_QUERY));
        $query = array_filter($query, static fn ($value) => is_string($value) && $value !== '');
        $queryString = http_build_query($query);

        return $indexPath.($queryString !== '' ? '?'.$queryString : '');
    }
}
