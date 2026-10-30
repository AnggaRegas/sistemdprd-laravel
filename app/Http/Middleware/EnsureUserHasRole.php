<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Batasi akses berdasarkan role yang diizinkan.
     *
     * Menu yang disembunyikan di frontend bukan pengaman. Middleware ini
     * memeriksa role dan relasi anggota staf langsung di backend.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if ($roles !== [] && ! $user->hasRole(...$roles)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        if ($user->isStaff() && ! $user->dprd_member_id) {
            abort(403, 'Akun staf pendamping belum dikaitkan dengan anggota DPRD.');
        }

        return $next($request);
    }
}
