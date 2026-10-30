<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AktivitasSistem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route($user->dashboardRouteName());
        }

        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route($user->dashboardRouteName());
        }

        $validated = $request->validateWithBag('forcePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'remember_token' => null,
        ])->save();

        AktivitasSistem::catat(
            'akun.password.changed',
            $user,
            $user,
            [],
            'Pengguna mengganti kata sandi wajib setelah reset administrator.'
        );

        return redirect()
            ->route($user->dashboardRouteName())
            ->with('success', 'Kata sandi berhasil diperbarui. Anda dapat melanjutkan menggunakan sistem.');
    }
}
