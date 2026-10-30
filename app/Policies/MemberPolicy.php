<?php

namespace App\Policies;

use App\Models\DprdMember;
use App\Models\User;

class MemberPolicy
{
    /**
     * Administrator melihat semua anggota. Staf pendamping tetap boleh
     * membuka daftar, tetapi query dibatasi ke anggota yang ditugaskan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isStaff();
    }

    public function view(User $user, DprdMember $member): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isStaff()
            && (int) $user->dprd_member_id === (int) $member->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, DprdMember $member): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Penghapusan memakai soft delete. Pemeriksaan relasi dilakukan di
     * controller agar pesan yang ditampilkan menjelaskan data yang masih
     * terkait. Policy hanya membatasi role.
     */
    public function delete(User $user, DprdMember $member): bool
    {
        return $user->isAdministrator();
    }

    public function viewArchives(User $user, DprdMember $member): bool
    {
        return $this->view($user, $member);
    }
}
