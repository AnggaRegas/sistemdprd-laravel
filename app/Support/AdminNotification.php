<?php

namespace App\Support;

use App\Models\Arsip;
use App\Models\LaporanMasyarakat;
use App\Models\NotificationRead;
use App\Models\User;

/**
 * Notifikasi ringkas yang dibangun dari data nyata di database.
 *
 * Konten notifikasi tidak disimpan ulang. Yang disimpan hanya status baca per
 * pengguna pada tabel notification_reads dengan kunci yang stabil.
 */
class AdminNotification
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isAdministrator()) {
            $items = self::forAdministrator();
        } elseif ($user->isStaff()) {
            $items = self::forStaff($user);
        } else {
            $items = [];
        }

        return self::withReadState($user, $items);
    }

    public static function unreadCount(?User $user): int
    {
        return count(array_filter(
            self::for($user),
            static fn (array $notification) => ! $notification['read']
        ));
    }

    public static function markRead(User $user, string $key): void
    {
        NotificationRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_key' => $key,
            ],
            ['read_at' => now()]
        );
    }

    public static function markAllRead(User $user): int
    {
        $marked = 0;

        foreach (self::for($user) as $notification) {
            if ($notification['read']) {
                continue;
            }

            self::markRead($user, (string) $notification['key']);
            $marked++;
        }

        return $marked;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function withReadState(User $user, array $items): array
    {
        if ($items === []) {
            return [];
        }

        $keys = array_map(static fn (array $item) => (string) $item['key'], $items);

        $readKeys = NotificationRead::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', $keys)
            ->whereNotNull('read_at')
            ->pluck('notification_key')
            ->all();

        $readKeys = array_flip($readKeys);

        foreach ($items as &$item) {
            $item['read'] = isset($readKeys[(string) $item['key']]);
        }

        unset($item);

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function forAdministrator(): array
    {
        $items = [];

        $newReports = LaporanMasyarakat::query()
            ->whereIn('status', [
                LaporanMasyarakat::STATUS_BARU,
                LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(2)
            ->get();

        foreach ($newReports as $report) {
            $items[] = [
                'key' => 'laporan-baru-'.$report->id,
                'judul' => 'Laporan baru masuk',
                'isi' => $report->nomor_tiket.' · '.($report->kategori ?: $report->kecamatan_label),
                'waktu' => $report->created_at?->locale('id')->diffForHumans() ?? 'Baru saja',
                'icon' => 'ri-chat-new-line',
                'tone' => 'info',
                'url' => route('laporan.show', $report),
            ];
        }

        $pending = LaporanMasyarakat::query()
            ->where('status', LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if ($pending) {
            $pendingCount = LaporanMasyarakat::query()
                ->where('status', LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN)
                ->count();

            $items[] = [
                'key' => 'laporan-menunggu-'.$pending->id,
                'judul' => 'Menunggu penugasan',
                'isi' => number_format($pendingCount, 0, ',', '.').' laporan belum memiliki staf pendamping.',
                'waktu' => 'Perlu tindakan',
                'icon' => 'ri-time-line',
                'tone' => 'warning',
                'url' => route('laporan.index', ['status' => 'Menunggu Penugasan']),
            ];
        }

        $verified = Arsip::query()
            ->where('status', Arsip::STATUS_TERVERIFIKASI)
            ->latest('verified_at')
            ->latest('id')
            ->limit(1)
            ->get();

        foreach ($verified as $archive) {
            $items[] = [
                'key' => 'arsip-terverifikasi-'.$archive->id,
                'judul' => 'Arsip terverifikasi',
                'isi' => $archive->nama ?: ($archive->nomor ?: 'Arsip DPRD'),
                'waktu' => $archive->verified_at?->locale('id')->diffForHumans() ?? 'Baru saja',
                'icon' => 'ri-checkbox-circle-line',
                'tone' => 'success',
                'url' => route('arsip.index', ['q' => $archive->nomor ?: $archive->nama]),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function forStaff(User $user): array
    {
        return LaporanMasyarakat::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', LaporanMasyarakat::ACTIVE_STATUSES)
            ->latest('assigned_at')
            ->latest('id')
            ->limit(3)
            ->get()
            ->map(static function (LaporanMasyarakat $report) {
                $assignedStamp = $report->assigned_at?->getTimestamp() ?? $report->id;

                return [
                    'key' => 'laporan-ditugaskan-'.$report->id.'-'.$assignedStamp,
                    'judul' => 'Laporan ditugaskan',
                    'isi' => $report->nomor_tiket.' · '.$report->ringkasan,
                    'waktu' => $report->assigned_at?->locale('id')->diffForHumans() ?? 'Aktif',
                    'icon' => 'ri-task-line',
                    'tone' => 'info',
                    'url' => route('laporan.show', $report),
                ];
            })
            ->all();
    }
}
