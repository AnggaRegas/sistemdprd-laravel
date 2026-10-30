<?php

namespace App\Policies;

use App\Models\KategoriArsip;
use App\Models\User;

class KategoriArsipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isStaff();
    }

    public function view(User $user, KategoriArsip $category): bool
    {
        return $user->isAdministrator() || $user->isStaff();
    }

    /**
     * Kategori arsip bersifat global. Hanya administrator yang boleh
     * menambah, mengubah, atau menghapus kategori agar struktur arsip
     * seluruh anggota tetap konsisten.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, KategoriArsip $category): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, KategoriArsip $category): bool
    {
        return $user->isAdministrator();
    }
}
