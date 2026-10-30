<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\KategoriArsip;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Database\Seeders\MemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Anggota',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'kode' => 'DWN-90',
            'nama_lengkap' => 'H. Anggota Uji, S.E.',
            'jabatan' => 'Anggota',
            'partai' => 'Partai Uji',
            'kecamatan' => 'Kecamatan Ujung',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_member_seeder_is_idempotent_and_does_not_create_staff_accounts(): void
    {
        $this->seed(MemberSeeder::class);
        $this->seed(MemberSeeder::class);

        $this->assertSame(25, DprdMember::query()->count());
        $this->assertSame(25, DprdMember::query()->distinct()->count('kode'));
        $this->assertSame(
            0,
            User::query()->where('role', User::ROLE_STAF_PENDAMPING)->count()
        );

        $this->assertDatabaseHas('dprd_members', [
            'kode' => 'DWN-01',
            'nama_lengkap' => 'Ir. H. KAHARUDDIN KADIR, M.Si.',
        ]);
        $this->assertDatabaseHas('dprd_members', [
            'kode' => 'DWN-25',
            'nama_lengkap' => 'SAPPE',
        ]);
    }

    public function test_administrator_sees_required_member_page_content(): void
    {
        $admin = $this->administrator();
        $this->seed(MemberSeeder::class);

        $firstMember = DprdMember::query()->where('kode', 'DWN-01')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('anggota.index'))
            ->assertOk()
            ->assertSee('Daftar Anggota DPRD Kota Parepare')
            ->assertSee('Data resmi 25 anggota dewan periode 2024–2029, jabatan, pendamping, dan berkas arsip terkait.')
            ->assertSee('Tambah Anggota Dewan')
            ->assertSee('Data Anggota Dewan')
            ->assertSee('Menampilkan seluruh legislator resmi DPRD Kota Parepare.')
            ->assertSee('Nama Lengkap & Gelar')
            ->assertSee('Partai Politik / Fraksi')
            ->assertSee('Kecamatan Asal')
            ->assertSee('Staf Pendamping')
            ->assertSee('Aksi & Arsip')
            ->assertSee('DWN-01')
            ->assertSee('Ir. H. KAHARUDDIN KADIR, M.Si.')
            ->assertSee('Buka Arsip')
            ->assertSee(route('anggota.arsip', $firstMember), false)
            ->assertSee('Belum ditugaskan');
    }

    public function test_member_list_is_paginated_and_keeps_query_string(): void
    {
        $admin = $this->administrator();
        $this->seed(MemberSeeder::class);

        $this->actingAs($admin)
            ->get(route('anggota.index'))
            ->assertOk()
            ->assertSee('DWN-01')
            ->assertSee('DWN-10')
            ->assertDontSee('DWN-11');

        $this->actingAs($admin)
            ->get(route('anggota.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('DWN-11')
            ->assertDontSee('DWN-01');

        $this->actingAs($admin)
            ->get(route('anggota.index', ['q' => 'DWN']))
            ->assertOk()
            ->assertSee('q=DWN', false);
    }

    public function test_member_search_matches_name_code_position_party_kecamatan_and_staff(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'kode' => 'DWN-77',
            'nama_lengkap' => 'Anggota Pencarian Khusus',
            'jabatan' => 'Sekretaris Komisi Uji',
            'partai' => 'Partai Pencarian',
            'kecamatan' => 'Kecamatan Soreang',
        ]);
        $other = DprdMember::factory()->create([
            'kode' => 'DWN-78',
            'nama_lengkap' => 'Anggota Pembanding',
            'partai' => 'Partai Pembanding',
            'kecamatan' => 'Kecamatan Ujung',
        ]);
        User::factory()->staff($member)->create([
            'name' => 'Staf Pendamping Pencarian',
        ]);

        foreach (['Anggota Pencarian Khusus', 'DWN-77', 'Sekretaris Komisi Uji', 'Partai Pencarian', 'Kecamatan Soreang', 'Staf Pendamping Pencarian'] as $keyword) {
            $this->actingAs($admin)
                ->get(route('anggota.index', ['q' => $keyword]))
                ->assertOk()
                ->assertSee($member->nama_lengkap)
                ->assertDontSee($other->nama_lengkap);
        }
    }

    public function test_member_list_filters_by_status_party_kecamatan_and_staff_assignment(): void
    {
        $admin = $this->administrator();
        $memberWithStaff = DprdMember::factory()->create([
            'kode' => 'DWN-71',
            'nama_lengkap' => 'Anggota Berstaf',
            'partai' => 'Partai Alpha',
            'kecamatan' => 'Kecamatan Alpha',
            'is_active' => true,
        ]);
        $memberWithoutStaff = DprdMember::factory()->inactive()->create([
            'kode' => 'DWN-72',
            'nama_lengkap' => 'Anggota Tanpa Staf',
            'partai' => 'Partai Beta',
            'kecamatan' => 'Kecamatan Beta',
        ]);
        User::factory()->staff($memberWithStaff)->create();

        $this->actingAs($admin)
            ->get(route('anggota.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($memberWithoutStaff->nama_lengkap)
            ->assertDontSee($memberWithStaff->nama_lengkap);

        $this->actingAs($admin)
            ->get(route('anggota.index', ['partai' => 'Partai Alpha']))
            ->assertOk()
            ->assertSee($memberWithStaff->nama_lengkap)
            ->assertDontSee($memberWithoutStaff->nama_lengkap);

        $this->actingAs($admin)
            ->get(route('anggota.index', ['kecamatan' => 'Kecamatan Beta']))
            ->assertOk()
            ->assertSee($memberWithoutStaff->nama_lengkap)
            ->assertDontSee($memberWithStaff->nama_lengkap);

        $this->actingAs($admin)
            ->get(route('anggota.index', ['staff' => 'assigned']))
            ->assertOk()
            ->assertSee($memberWithStaff->nama_lengkap)
            ->assertDontSee($memberWithoutStaff->nama_lengkap);

        $this->actingAs($admin)
            ->get(route('anggota.index', ['staff' => 'unassigned']))
            ->assertOk()
            ->assertSee($memberWithoutStaff->nama_lengkap)
            ->assertDontSee($memberWithStaff->nama_lengkap);
    }

    public function test_staff_name_is_read_from_database_relationship(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Dengan Staf Database',
        ]);
        User::factory()->staff($member)->create([
            'name' => 'Nama Staf Dari Database',
        ]);

        $this->actingAs($admin)
            ->get(route('anggota.index'))
            ->assertOk()
            ->assertSee('Nama Staf Dari Database')
            ->assertSee('Belum ditugaskan');
    }

    public function test_administrator_can_create_and_edit_member(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->get(route('anggota.create'))
            ->assertOk()
            ->assertSee('Tambah Anggota Dewan');

        $this->actingAs($admin)
            ->post(route('anggota.store'), $this->payload([
                'kode' => ' dwn-90 ',
                'nama_lengkap' => '  H. Anggota Uji, S.E.  ',
                'partai' => '  Partai Uji  ',
            ]))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dprd_members', [
            'kode' => 'DWN-90',
            'nama_lengkap' => 'H. Anggota Uji, S.E.',
            'partai' => 'Partai Uji',
            'kecamatan' => 'Kecamatan Ujung',
            'is_active' => true,
        ]);

        $member = DprdMember::query()->where('kode', 'DWN-90')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('anggota.edit', $member))
            ->assertOk()
            ->assertSee('Edit Anggota Dewan')
            ->assertSee('H. Anggota Uji, S.E.');

        $this->actingAs($admin)
            ->put(route('anggota.update', $member), $this->payload([
                'kode' => 'DWN-91',
                'nama_lengkap' => 'H. Anggota Setelah Edit',
                'jabatan' => 'Wakil Ketua',
                'partai' => 'Partai Setelah Edit',
                'kecamatan' => '',
                'is_active' => '0',
            ]))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dprd_members', [
            'id' => $member->id,
            'kode' => 'DWN-91',
            'nama_lengkap' => 'H. Anggota Setelah Edit',
            'jabatan' => 'Wakil Ketua',
            'kecamatan' => null,
            'is_active' => false,
        ]);
    }

    public function test_administrator_can_map_member_to_aspiration_region_and_commission(): void
    {
        $admin = $this->administrator();
        $region = DprdRegion::query()->where('name', 'Kecamatan Soreang')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('anggota.create'))
            ->assertOk()
            ->assertSee('Wilayah Aspirasi')
            ->assertSee('Dapil')
            ->assertSee('Komisi')
            ->assertSee($region->name);

        $this->actingAs($admin)
            ->post(route('anggota.store'), $this->payload([
                'kode' => 'DWN-95',
                'nama_lengkap' => 'Anggota Pemetaan Wilayah',
                'dapil' => 'Dapil Soreang',
                'komisi' => 'Komisi 2',
                'dprd_region_id' => (string) $region->id,
            ]))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dprd_members', [
            'kode' => 'DWN-95',
            'dapil' => 'Dapil Soreang',
            'komisi' => 'Komisi 2',
            'dprd_region_id' => $region->id,
        ]);

        $member = DprdMember::query()->where('kode', 'DWN-95')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('anggota.edit', $member))
            ->assertOk()
            ->assertSee('Anggota Pemetaan Wilayah')
            ->assertSee($region->name);

        $this->actingAs($admin)
            ->put(route('anggota.update', $member), $this->payload([
                'kode' => 'DWN-95',
                'nama_lengkap' => 'Anggota Pemetaan Wilayah',
                'dapil' => 'Dapil Ujung',
                'komisi' => 'Komisi 3',
                'dprd_region_id' => '',
            ]))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $member->refresh();

        $this->assertNull($member->dprd_region_id);
        $this->assertSame('Dapil Ujung', $member->dapil);
        $this->assertSame('Komisi 3', $member->komisi);
    }

    public function test_member_region_mapping_rejects_unknown_region(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->post(route('anggota.store'), $this->payload([
                'kode' => 'DWN-96',
                'dprd_region_id' => '999999',
            ]))
            ->assertSessionHasErrors('dprd_region_id');

        $this->assertDatabaseMissing('dprd_members', ['kode' => 'DWN-96']);
    }

    public function test_member_code_must_be_unique_and_can_be_reused_after_soft_delete(): void
    {
        $admin = $this->administrator();
        $existing = DprdMember::factory()->create([
            'kode' => 'DWN-88',
            'nama_lengkap' => 'Anggota Kode Lama',
        ]);

        $this->actingAs($admin)
            ->post(route('anggota.store'), $this->payload([
                'kode' => 'dwn-88',
                'nama_lengkap' => 'Anggota Kode Duplikat',
            ]))
            ->assertSessionHasErrors('kode');

        $this->assertSame(1, DprdMember::query()->where('kode', 'DWN-88')->count());

        $this->actingAs($admin)
            ->delete(route('anggota.destroy', $existing))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('dprd_members', ['id' => $existing->id]);

        $this->actingAs($admin)
            ->post(route('anggota.store'), $this->payload([
                'kode' => 'DWN-88',
                'nama_lengkap' => 'Anggota Kode Baru',
            ]))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dprd_members', [
            'kode' => 'DWN-88',
            'nama_lengkap' => 'Anggota Kode Baru',
            'deleted_at' => null,
        ]);
    }

    public function test_administrator_can_toggle_member_status_without_deleting_history(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Status',
            'is_active' => true,
        ]);
        $archive = Arsip::factory()->create([
            'dprd_member_id' => $member->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('anggota.toggle-status', $member))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($member->refresh()->is_active);
        $this->assertNotSoftDeleted('dprd_members', ['id' => $member->id]);
        $this->assertNotSoftDeleted('arsips', ['id' => $archive->id]);
    }

    public function test_member_with_related_records_cannot_be_deleted(): void
    {
        $admin = $this->administrator();
        $category = KategoriArsip::factory()->create();

        $memberWithArchive = DprdMember::factory()->create();
        Arsip::factory()->create([
            'dprd_member_id' => $memberWithArchive->id,
            'kategori_arsip_id' => $category->id,
        ]);

        $memberWithStaff = DprdMember::factory()->create();
        User::factory()->staff($memberWithStaff)->create();

        $memberWithReport = DprdMember::factory()->create();
        LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $memberWithReport->id,
        ]);

        foreach ([$memberWithArchive, $memberWithStaff, $memberWithReport] as $member) {
            $this->actingAs($admin)
                ->from(route('anggota.index'))
                ->delete(route('anggota.destroy', $member))
                ->assertRedirect(route('anggota.index'))
                ->assertSessionHas('error');

            $this->assertNotSoftDeleted('dprd_members', ['id' => $member->id]);
        }
    }

    public function test_member_without_related_records_is_soft_deleted(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $this->actingAs($admin)
            ->delete(route('anggota.destroy', $member))
            ->assertRedirect(route('anggota.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('dprd_members', ['id' => $member->id]);
    }

    public function test_staff_only_sees_assigned_member_and_cannot_manage_members(): void
    {
        $memberA = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Binaan Staf',
        ]);
        $memberB = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Bukan Binaan',
        ]);
        $staff = User::factory()->staff($memberA)->create();

        $this->actingAs($staff)
            ->get(route('anggota.index'))
            ->assertOk()
            ->assertSee($memberA->nama_lengkap)
            ->assertDontSee($memberB->nama_lengkap)
            ->assertDontSee('Tambah Anggota Dewan');

        $this->actingAs($staff)
            ->get(route('anggota.create'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('anggota.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('anggota.edit', $memberA))
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('anggota.update', $memberA), $this->payload())
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('anggota.toggle-status', $memberA))
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('anggota.destroy', $memberA))
            ->assertForbidden();

        $this->assertNotSoftDeleted('dprd_members', ['id' => $memberA->id]);
    }

    public function test_staff_can_open_own_member_archives_but_not_another_members_archives(): void
    {
        $memberA = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Arsip Sendiri',
        ]);
        $memberB = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Arsip Orang Lain',
        ]);
        $staff = User::factory()->staff($memberA)->create();

        $this->actingAs($staff)
            ->get(route('anggota.arsip', $memberA))
            ->assertOk()
            ->assertSee($memberA->nama_lengkap);

        $this->actingAs($staff)
            ->get(route('anggota.arsip', $memberB))
            ->assertForbidden();
    }
}
