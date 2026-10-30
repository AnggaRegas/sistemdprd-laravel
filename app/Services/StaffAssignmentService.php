<?php

namespace App\Services;

use App\Models\DprdMember;
use App\Models\DprdStaff;
use App\Models\StaffAssignmentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAssignmentService
{
    /**
     * Mengubah staf pendamping pada data anggota. Perubahan ditolak jika staf
     * lama masih memiliki akun aktif karena akun tersebut masih memberi akses
     * ke anggota lama.
     */
    public function assignToMember(
        DprdMember $member,
        ?DprdStaff $staff,
        ?User $actor,
        string $source = 'member_form',
        ?string $note = null
    ): void {
        $currentStaffId = $member->staff_id ? (int) $member->staff_id : null;
        $newStaffId = $staff?->id;

        if ($currentStaffId === $newStaffId) {
            return;
        }

        DB::transaction(function () use ($member, $staff, $actor, $source, $note) {
            $member = DprdMember::query()->lockForUpdate()->findOrFail($member->id);

            $currentStaff = $member->staff_id
                ? DprdStaff::query()->with('account')->find($member->staff_id)
                : null;

            if ($currentStaff?->hasActiveAccount()) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Staf '.$currentStaff->nama.' masih memiliki akun aktif. '
                        .'Ubah penugasan melalui Kelola Akun Staf atau nonaktifkan akun tersebut terlebih dahulu.',
                ]);
            }

            if ($staff) {
                if (! $staff->is_active) {
                    throw ValidationException::withMessages([
                        'staff_id' => 'Staf pendamping yang dipilih sudah tidak aktif.',
                    ]);
                }

                $staff->loadMissing('account');

                $assignedElsewhere = DprdMember::query()
                    ->where('staff_id', $staff->id)
                    ->whereKeyNot($member->id)
                    ->exists();

                if ($assignedElsewhere) {
                    throw ValidationException::withMessages([
                        'staff_id' => 'Staf '.$staff->nama.' sudah ditugaskan ke anggota DPRD lain.',
                    ]);
                }

                if (
                    $staff->account
                    && $staff->account->is_active
                    && (int) $staff->account->dprd_member_id !== (int) $member->id
                ) {
                    throw ValidationException::withMessages([
                        'staff_id' => 'Staf '.$staff->nama.' memiliki akun aktif pada anggota lain. '
                            .'Pindahkan akun tersebut melalui Kelola Akun Staf terlebih dahulu.',
                    ]);
                }
            }

            $previousStaffId = $member->staff_id;
            $member->staff_id = $staff?->id;
            $member->save();

            if ($previousStaffId) {
                $this->record(
                    (int) $previousStaffId,
                    null,
                    $member->id,
                    $actor?->id,
                    $source,
                    'Staf dilepas dari anggota '.$member->nama_lengkap.'.'
                );
            }

            if ($staff) {
                $this->record(
                    $staff->id,
                    $member->id,
                    null,
                    $actor?->id,
                    $source,
                    $note ?: 'Staf ditugaskan ke anggota '.$member->nama_lengkap.'.'
                );
            }
        });
    }

    /**
     * Menyelaraskan penugasan anggota ketika administrator mengubah anggota
     * pada akun staf. Staf yang tergeser dilepas tanpa menghapus riwayat.
     */
    public function syncAccountAssignment(
        User $account,
        ?DprdStaff $staff,
        ?DprdMember $targetMember,
        ?User $actor,
        string $source = 'staff_account'
    ): void {
        if (! $staff) {
            return;
        }

        DB::transaction(function () use ($account, $staff, $targetMember, $actor, $source) {
            $staff = DprdStaff::query()->lockForUpdate()->findOrFail($staff->id);

            // Lepas staf ini dari anggota mana pun yang bukan tujuan baru.
            $holders = DprdMember::query()
                ->where('staff_id', $staff->id)
                ->when($targetMember, fn ($query) => $query->whereKeyNot($targetMember->id))
                ->lockForUpdate()
                ->get();

            foreach ($holders as $holder) {
                $holder->staff_id = null;
                $holder->save();

                $this->record(
                    $staff->id,
                    null,
                    $holder->id,
                    $actor?->id,
                    $source,
                    'Staf dilepas dari anggota '.$holder->nama_lengkap.' karena akun dipindahkan.'
                );
            }

            if (! $targetMember) {
                return;
            }

            $targetMember = DprdMember::query()->lockForUpdate()->findOrFail($targetMember->id);

            if ($targetMember->staff_id && (int) $targetMember->staff_id !== $staff->id) {
                $previousStaff = DprdStaff::query()->with('account')->find($targetMember->staff_id);

                if (
                    $previousStaff?->hasActiveAccount()
                    && (int) $previousStaff->account->id !== (int) $account->id
                ) {
                    throw ValidationException::withMessages([
                        'dprd_member_id' => 'Anggota DPRD tersebut sudah memiliki staf pendamping dengan akun aktif lain.',
                    ]);
                }

                $previousStaffId = $targetMember->staff_id;
                $targetMember->staff_id = null;
                $targetMember->save();

                if ($previousStaff) {
                    $this->record(
                        $previousStaff->id,
                        null,
                        $targetMember->id,
                        $actor?->id,
                        $source,
                        'Staf sebelumnya dilepas karena anggota menerima penugasan baru.'
                    );
                }
            }

            if ((int) $targetMember->staff_id !== (int) $staff->id) {
                $targetMember->staff_id = $staff->id;
                $targetMember->save();

                $this->record(
                    $staff->id,
                    $targetMember->id,
                    null,
                    $actor?->id,
                    $source,
                    'Staf ditugaskan ke anggota '.$targetMember->nama_lengkap.' melalui akun staf.'
                );
            }
        });
    }

    private function record(
        int $staffId,
        ?int $memberId,
        ?int $previousMemberId,
        ?int $actorId,
        string $source,
        ?string $note = null
    ): void {
        StaffAssignmentHistory::query()->create([
            'dprd_staff_id' => $staffId,
            'dprd_member_id' => $memberId,
            'previous_member_id' => $previousMemberId,
            'assigned_by' => $actorId,
            'source' => $source,
            'catatan' => $note ? mb_substr($note, 0, 255) : null,
        ]);
    }
}
