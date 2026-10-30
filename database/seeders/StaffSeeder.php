<?php

namespace Database\Seeders;

use App\Models\DprdMember;
use App\Models\DprdStaff;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Data referensi 25 staf pendamping resmi dan pemetaannya ke anggota DPRD.
     *
     * Seeder ini tidak membuat akun login, username, atau kata sandi. Akun
     * yang sudah ada hanya dihubungkan ke data referensi bila namanya cocok
     * dengan anggota yang benar. Akun baru tetap dibuat administrator.
     */
    public function run(): void
    {
        $staff = [
            'DWN-01' => 'SUKARNO, S. Kom.',
            'DWN-02' => 'M. KHAERUL KADAR, S.H',
            'DWN-03' => 'AJI WICAKSONO, S.H.',
            'DWN-04' => 'SINTA DEWI ANGGRAENI, S.E.',
            'DWN-05' => 'ANDI ANWAR, A.md.',
            'DWN-06' => 'SYARIEF LATIEF, S.Pt.',
            'DWN-07' => 'SARTIKA',
            'DWN-08' => 'ARIYANI, S.H.',
            'DWN-09' => 'SUDARMIN, S.Pt.',
            'DWN-10' => 'ARIS, S.E.',
            'DWN-11' => 'MARIANI',
            'DWN-12' => 'AMIR, S.T.',
            'DWN-13' => 'RESTY NOVIANTI, S.H',
            'DWN-14' => 'MUH. RAYNALDI KURNIAWAN, S.Tr.IP.',
            'DWN-15' => "YOPIE MA'DIKA, S.H.",
            'DWN-16' => 'AGUS SALIM',
            'DWN-17' => 'FAISAL IKHWANSYAH, S.H.',
            'DWN-18' => 'Hj. NURAENI HUSAIN',
            'DWN-19' => 'NURAINIA NAIM',
            'DWN-20' => 'M. ALI YUNUS',
            'DWN-21' => 'FITRI HANDAYANI, SE.',
            'DWN-22' => 'MUHAMMAD RAIHAN IRSAN',
            'DWN-23' => 'HASRIANTI, S.Kom.',
            'DWN-24' => 'ALFIAN SYUKUR',
            'DWN-25' => 'ARI WIBOWO S.H.',
        ];

        foreach ($staff as $memberCode => $staffName) {
            $order = (int) substr($memberCode, -2);

            /** @var DprdStaff $record */
            $record = DprdStaff::query()->updateOrCreate(
                ['kode' => 'STF-'.str_pad((string) $order, 2, '0', STR_PAD_LEFT)],
                [
                    'nama' => $staffName,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );

            $member = DprdMember::query()->where('kode', $memberCode)->first();

            if (! $member) {
                continue;
            }

            if ((int) $member->staff_id !== (int) $record->id) {
                // Pastikan relasi unik tidak dimiliki anggota lain.
                DprdMember::query()
                    ->where('staff_id', $record->id)
                    ->whereKeyNot($member->id)
                    ->update(['staff_id' => null]);

                $member->update(['staff_id' => $record->id]);
            }

            $this->linkExistingAccount($record, $member);
        }
    }

    /**
     * Menghubungkan akun staf lama tanpa membuat kredensial baru.
     */
    private function linkExistingAccount(DprdStaff $staff, DprdMember $member): void
    {
        if (User::query()->where('dprd_staff_id', $staff->id)->exists()) {
            return;
        }

        $base = User::query()
            ->where('role', User::ROLE_STAF_PENDAMPING)
            ->whereNull('dprd_staff_id')
            ->whereNull('deleted_at')
            ->where('dprd_member_id', $member->id);

        $account = (clone $base)
            ->where('name', $staff->nama)
            ->first();

        if (! $account) {
            $account = (clone $base)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (! $account) {
            $account = (clone $base)
                ->orderBy('id')
                ->first();
        }

        if ($account) {
            $account->forceFill(['dprd_staff_id' => $staff->id])->save();
        }
    }
}
