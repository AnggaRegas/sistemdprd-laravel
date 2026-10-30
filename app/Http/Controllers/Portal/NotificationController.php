<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Tandai satu notifikasi dibaca lalu arahkan ke tujuan yang berasal dari
     * daftar notifikasi milik pengguna sendiri, bukan dari input URL.
     */
    public function open(Request $request, string $notificationKey): RedirectResponse
    {
        $user = $request->user();

        $notification = collect(AdminNotification::for($user))
            ->firstWhere('key', $notificationKey);

        abort_if($notification === null, 404, 'Notifikasi tidak ditemukan.');

        AdminNotification::markRead($user, $notificationKey);

        return redirect($notification['url']);
    }

    public function markAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $marked = AdminNotification::markAllRead($user);

        return redirect()
            ->route($user->dashboardRouteName())
            ->with(
                'success',
                $marked > 0
                    ? 'Semua notifikasi ditandai sudah dibaca.'
                    : 'Tidak ada notifikasi yang perlu ditandai.'
            );
    }
}
