<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    /**
     * Paksa pengguna yang baru direset/dibuat administrator untuk mengganti
     * kata sandi sebelum mengakses halaman internal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && $user->must_change_password
            && ! $request->routeIs('password.force.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()
                ->route('password.force.edit')
                ->with('warning', 'Demi keamanan, ganti kata sandi awal Anda sebelum melanjutkan.');
        }

        return $next($request);
    }
}
