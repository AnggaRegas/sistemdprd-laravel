<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffAccountStoreRequest;
use App\Http\Requests\StaffAccountUpdateRequest;
use App\Http\Requests\StaffPasswordResetRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'q' => trim((string) $request->query('q')),
            'role' => (string) $request->query('role'),
            'status' => (string) $request->query('status'),
        ];

        $query = User::query()
            ->with('member')
            ->whereIn('role', User::ROLES);

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$needle])
                    ->orWhereHas('member', function ($memberQuery) use ($needle) {
                        $memberQuery->whereRaw('LOWER(nama_lengkap) LIKE ?', [$needle]);
                    });
            });
        }

        if (in_array($filters['role'], User::ROLES, true)) {
            $query->where('role', $filters['role']);
        }

        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $query->where('is_active', false);
        }

        $filteredTotal = (clone $query)->count();

        $accounts = $query
            ->orderByRaw("CASE WHEN role = 'administrator' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => User::query()->count(),
            'administrator' => User::query()
                ->where('role', User::ROLE_ADMINISTRATOR)
                ->count(),
            'staff' => User::query()
                ->where('role', User::ROLE_STAF_PENDAMPING)
                ->count(),
            'inactive' => User::query()
                ->where('is_active', false)
                ->count(),
        ];

        return view('accounts.index', [
            'accounts' => $accounts,
            'filters' => $filters,
            'summary' => $summary,
            'filteredTotal' => $filteredTotal,
            'activeAdminCount' => $this->activeAdministratorCount(),
            'hasFilters' => $filters['q'] !== ''
                || $filters['role'] !== ''
                || $filters['status'] !== '',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('accounts.create', [
            'members' => $this->assignableMembers(),
        ]);
    }

    public function store(StaffAccountStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $member = DprdMember::query()->findOrFail($validated['dprd_member_id']);

        if (! $member->is_active) {
            throw ValidationException::withMessages([
                'dprd_member_id' => 'Anggota DPRD yang dipilih tidak aktif.',
            ]);
        }

        try {
            $account = DB::transaction(function () use ($validated) {
                $account = new User();
                $account->forceFill([
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'role' => User::ROLE_STAF_PENDAMPING,
                    'dprd_member_id' => $validated['dprd_member_id'],
                    'is_active' => (bool) $validated['is_active'],
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ])->save();

                return $account;
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateValidation($exception);
        }

        AktivitasSistem::catat(
            'akun.staff.created',
            $request->user(),
            $account,
            [
                'role' => $account->role,
                'dprd_member_id' => $account->dprd_member_id,
                'is_active' => $account->is_active,
            ],
            'Akun staf pendamping '.$account->name.' dibuat oleh administrator.'
        );

        return redirect()
            ->route('akun.index')
            ->with('success', 'Akun staf berhasil dibuat. Kata sandi awal wajib diganti saat pengguna pertama kali masuk.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        return view('accounts.edit', [
            'account' => $user,
            'members' => $this->assignableMembers($user),
            'canDeactivate' => $this->canDeactivate($request->user(), $user),
            'activeAdminCount' => $this->activeAdministratorCount(),
        ]);
    }

    public function update(StaffAccountUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();
        $newActiveStatus = (bool) $validated['is_active'];

        if ($user->is_active && ! $newActiveStatus) {
            if ($user->is($request->user())) {
                abort(403, 'Akun yang sedang Anda gunakan tidak dapat dinonaktifkan.');
            }

            if ($user->isAdministrator()) {
                $this->ensureNotLastActiveAdministrator($user);
            }
        }

        try {
            DB::transaction(function () use ($user, $validated, $newActiveStatus) {
                $emailChanged = $user->email !== $validated['email'];

                $attributes = [
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                    'is_active' => $newActiveStatus,
                ];

                if ($user->isStaff()) {
                    $attributes['dprd_member_id'] = $validated['dprd_member_id'];
                }

                if ($emailChanged) {
                    $attributes['email_verified_at'] = now();
                }

                if (! $newActiveStatus) {
                    $attributes['remember_token'] = null;
                }

                $user->forceFill($attributes)->save();
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateValidation($exception);
        }

        AktivitasSistem::catat(
            'akun.staff.updated',
            $request->user(),
            $user,
            [
                'role' => $user->role,
                'dprd_member_id' => $user->dprd_member_id,
                'is_active' => $user->is_active,
            ],
            'Akun '.$user->name.' diperbarui oleh administrator.'
        );

        return redirect()
            ->route('akun.index')
            ->with('success', 'Informasi akun berhasil diperbarui.');
    }

    public function resetPassword(StaffPasswordResetRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $account = User::query()->findOrFail($validated['user_id']);

        $this->authorize('resetPassword', $account);

        DB::transaction(function () use ($account, $validated) {
            $account->forceFill([
                'password' => $validated['password'],
                'must_change_password' => true,
                'remember_token' => null,
            ])->save();
        });

        AktivitasSistem::catat(
            'akun.password.reset',
            $request->user(),
            $account,
            [],
            'Kata sandi akun '.$account->name.' direset oleh administrator.'
        );

        return redirect()
            ->route('akun.index')
            ->with('success', 'Kata sandi akun berhasil direset. Pengguna wajib mengganti kata sandi saat login berikutnya.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        if ($user->is_active) {
            $user->forceFill([
                'is_active' => false,
                'remember_token' => null,
            ])->save();

            $message = 'Akun '.$user->name.' berhasil dinonaktifkan. Session aktif akan ditolak pada permintaan berikutnya.';
        } else {
            if ($user->isStaff() && ! $user->dprd_member_id) {
                return back()->with('error', 'Akun staf belum memiliki penugasan anggota DPRD sehingga tidak dapat diaktifkan.');
            }

            if (
                $user->isStaff()
                && $this->memberHasActiveStaff((int) $user->dprd_member_id, $user->id)
            ) {
                return back()->with('error', 'Anggota DPRD tersebut sudah memiliki staf pendamping aktif lain. Nonaktifkan staf tersebut terlebih dahulu.');
            }

            $user->forceFill(['is_active' => true])->save();

            $message = 'Akun '.$user->name.' berhasil diaktifkan.';
        }

        AktivitasSistem::catat(
            'akun.status.changed',
            $request->user(),
            $user,
            ['is_active' => $user->is_active],
            $message
        );

        return back()->with('success', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        DB::transaction(function () use ($user) {
            $user->forceFill([
                'is_active' => false,
                'remember_token' => null,
            ])->save();

            $user->delete();
        });

        AktivitasSistem::catat(
            'akun.deleted',
            $request->user(),
            $user,
            ['role' => $user->role],
            'Akun '.$user->name.' dihapus (soft delete) oleh administrator. Arsip dan riwayat aktivitas tidak dihapus.'
        );

        return redirect()
            ->route('akun.index')
            ->with('success', 'Akun berhasil dihapus. Arsip, laporan, dan riwayat aktivitas tetap tersimpan.');
    }

    /**
     * @return Collection<int, DprdMember>
     */
    private function assignableMembers(?User $account = null): Collection
    {
        $query = DprdMember::query()->where('is_active', true);

        if ($account && $account->isStaff() && $account->dprd_member_id) {
            $query->where(function ($query) use ($account) {
                $query->whereDoesntHave('activeStaff', function ($staffQuery) use ($account) {
                    $staffQuery->where('users.id', '!=', $account->id);
                })->orWhere('id', $account->dprd_member_id);
            });
        } else {
            $query->whereDoesntHave('activeStaff');
        }

        return $query->orderBy('nama_lengkap')->get();
    }

    private function activeAdministratorCount(): int
    {
        return User::query()
            ->where('role', User::ROLE_ADMINISTRATOR)
            ->where('is_active', true)
            ->count();
    }

    private function memberHasActiveStaff(int $memberId, ?int $ignoreUserId = null): bool
    {
        return User::query()
            ->where('role', User::ROLE_STAF_PENDAMPING)
            ->where('dprd_member_id', $memberId)
            ->where('is_active', true)
            ->when($ignoreUserId, function ($query) use ($ignoreUserId) {
                $query->where('id', '!=', $ignoreUserId);
            })
            ->exists();
    }

    private function canDeactivate(User $actor, User $account): bool
    {
        if ($actor->is($account)) {
            return false;
        }

        if ($account->isAdministrator() && $account->is_active && $this->activeAdministratorCount() <= 1) {
            return false;
        }

        return true;
    }

    private function ensureNotLastActiveAdministrator(User $account): void
    {
        if (! $account->isAdministrator() || ! $account->is_active) {
            return;
        }

        if ($this->activeAdministratorCount() <= 1) {
            abort(403, 'Administrator aktif terakhir tidak dapat dinonaktifkan atau dihapus.');
        }
    }

    private function throwDuplicateValidation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'users_active_member_unique')) {
            throw ValidationException::withMessages([
                'dprd_member_id' => 'Anggota DPRD ini sudah memiliki staf pendamping aktif.',
            ]);
        }

        if (str_contains($message, 'users_username_unique')) {
            throw ValidationException::withMessages([
                'username' => 'Username sudah digunakan oleh akun lain.',
            ]);
        }

        if (str_contains($message, 'users_email_unique')) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah digunakan oleh akun lain.',
            ]);
        }

        throw $exception;
    }
}
