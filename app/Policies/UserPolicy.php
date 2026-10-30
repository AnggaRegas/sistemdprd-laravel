<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Hanya administrator yang boleh melihat daftar seluruh akun internal.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $account): bool
    {
        return $user->isAdministrator();
    }

    public function resetPassword(User $user, User $account): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Akun yang sedang digunakan tidak boleh dinonaktifkan. Administrator
     * aktif terakhir juga tidak boleh dinonaktifkan.
     */
    public function toggleActive(User $user, User $account): bool
    {
        if (! $user->isAdministrator()) {
            return false;
        }

        if ($user->is($account) && $account->is_active) {
            return false;
        }

        return ! $this->isProtectedActiveAdministrator($account);
    }

    /**
     * Penghapusan memakai soft delete. Akun sendiri dan administrator aktif
     * terakhir selalu dilindungi.
     */
    public function delete(User $user, User $account): bool
    {
        if (! $user->isAdministrator()) {
            return false;
        }

        if ($user->is($account)) {
            return false;
        }

        return ! $this->isProtectedActiveAdministrator($account);
    }

    private function isProtectedActiveAdministrator(User $account): bool
    {
        if (! $account->isAdministrator() || ! $account->is_active) {
            return false;
        }

        return User::query()
            ->where('role', User::ROLE_ADMINISTRATOR)
            ->where('is_active', true)
            ->count() <= 1;
    }
}
