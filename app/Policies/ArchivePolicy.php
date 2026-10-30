<?php

namespace App\Policies;

use App\Models\Arsip;
use App\Models\User;

class ArchivePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isStaff();
    }

    public function view(User $user, Arsip $archive): bool
    {
        return $this->ownsOrAdministers($user, $archive);
    }

    public function create(User $user): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isStaff() && (bool) $user->dprd_member_id;
    }

    public function update(User $user, Arsip $archive): bool
    {
        return $this->ownsOrAdministers($user, $archive);
    }

    public function delete(User $user, Arsip $archive): bool
    {
        return $this->ownsOrAdministers($user, $archive);
    }

    public function download(User $user, Arsip $archive): bool
    {
        return $this->view($user, $archive);
    }

    private function ownsOrAdministers(User $user, Arsip $archive): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isStaff()
            && $user->dprd_member_id
            && (int) $archive->dprd_member_id === (int) $user->dprd_member_id;
    }
}
