<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Helper format tampilan portal.
 *
 * Data arsip, anggota, laporan, dan statistik tidak lagi disimpan di sini.
 * Seluruh data operasional dibaca dari database melalui model Eloquent.
 */
class PortalData
{
    public static function date(?string $date, bool $long = false): string
    {
        if (! $date) {
            return '—';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat($long ? 'd F Y' : 'd M Y');
    }

    public static function longDate(?string $date): string
    {
        return self::date($date, true);
    }

    public static function dateTime(mixed $value, bool $long = false): string
    {
        if (! $value) {
            return '—';
        }

        $date = $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse((string) $value);

        return $date->locale('id')->translatedFormat($long ? 'd F Y H:i' : 'd M Y H:i');
    }

    public static function initials(string $name): string
    {
        $name = explode(',', $name)[0];
        $clean = preg_replace('/[^A-Za-z\s]/', ' ', Str::ascii($name));
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $clean)) ?: []));

        $titles = ['H', 'Hj', 'Drs', 'Dr', 'Ir', 'ST', 'SE', 'SH', 'SPd', 'SKM', 'SSos', 'SAg', 'MSi', 'MM', 'MH'];
        $parts = array_values(array_filter($parts, static function ($part) use ($titles) {
            return ! in_array($part, $titles, true);
        }));

        if ($parts === []) {
            return 'DP';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    public static function toneForStatus(string $status): string
    {
        return match ($status) {
            'Terverifikasi', 'Selesai', 'Aktif' => 'success',
            'Menunggu Verifikasi', 'Menunggu Penugasan' => 'warning',
            'Ditolak' => 'danger',
            'Ditugaskan' => 'gold',
            'Baru', 'Diproses' => 'info',
            default => 'neutral',
        };
    }
}
