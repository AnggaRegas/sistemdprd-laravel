<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_administrator_dashboard(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Administrator');
    }

    public function test_staff_cannot_access_administrator_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_staff_can_access_staff_dashboard(): void
    {
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Binaan Staf',
        ]);
        $staff = User::factory()->staff($member)->create();

        $this->actingAs($staff)
            ->get('/dashboard/staf')
            ->assertOk()
            ->assertSee('Dashboard Staf Pendamping')
            ->assertSee('Anggota Binaan Staf');
    }

    public function test_administrator_cannot_access_staff_dashboard(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get('/dashboard/staf')
            ->assertForbidden();
    }

    public function test_staff_lists_only_contain_data_for_the_assigned_member(): void
    {
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Beta']);
        $staff = User::factory()->staff($memberA)->create();

        Arsip::factory()->create([
            'dprd_member_id' => $memberA->id,
            'nama' => 'Arsip Alpha',
            'nomor' => 'ARS-ALPHA',
        ]);
        Arsip::factory()->create([
            'dprd_member_id' => $memberB->id,
            'nama' => 'Arsip Beta',
            'nomor' => 'ARS-BETA',
        ]);

        LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $memberA->id,
            'assigned_to' => $staff->id,
            'ringkasan' => 'Laporan Alpha',
            'nomor_tiket' => 'LPR-ALPHA',
        ]);
        LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $memberB->id,
            'ringkasan' => 'Laporan Beta',
            'nomor_tiket' => 'LPR-BETA',
        ]);

        $this->actingAs($staff)
            ->get('/arsip')
            ->assertOk()
            ->assertSee('Arsip Alpha')
            ->assertDontSee('Arsip Beta');

        $this->actingAs($staff)
            ->get('/laporan')
            ->assertOk()
            ->assertSee('Laporan Alpha')
            ->assertDontSee('Laporan Beta');

        $this->actingAs($staff)
            ->get('/anggota')
            ->assertOk()
            ->assertSee('Anggota Alpha')
            ->assertDontSee('Anggota Beta');

        $this->actingAs($staff)
            ->get('/dashboard/staf')
            ->assertOk()
            ->assertSee('Anggota Alpha')
            ->assertDontSee('Anggota Beta');
    }

    public function test_administrator_lists_contain_all_data(): void
    {
        $admin = User::factory()->administrator()->create();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Beta']);

        Arsip::factory()->create([
            'dprd_member_id' => $memberA->id,
            'nama' => 'Arsip Alpha',
            'nomor' => 'ARS-ALPHA',
        ]);
        Arsip::factory()->create([
            'dprd_member_id' => $memberB->id,
            'nama' => 'Arsip Beta',
            'nomor' => 'ARS-BETA',
        ]);

        LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $memberA->id,
            'ringkasan' => 'Laporan Alpha',
            'nomor_tiket' => 'LPR-ALPHA',
        ]);
        LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $memberB->id,
            'ringkasan' => 'Laporan Beta',
            'nomor_tiket' => 'LPR-BETA',
        ]);

        $this->actingAs($admin)->get('/arsip')->assertOk()
            ->assertSee('Arsip Alpha')->assertSee('Arsip Beta');
        $this->actingAs($admin)->get('/laporan')->assertOk()
            ->assertSee('Laporan Alpha')->assertSee('Laporan Beta');
        $this->actingAs($admin)->get('/anggota')->assertOk()
            ->assertSee('Anggota Alpha')->assertSee('Anggota Beta');
    }

    public function test_administrator_dashboard_shows_empty_states_without_data(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Belum ada arsip')
            ->assertSee('Belum ada laporan');
    }

    public function test_lists_show_empty_states_without_data(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->get('/arsip')->assertOk()->assertSee('Belum ada arsip');
        $this->actingAs($admin)->get('/anggota')->assertOk()->assertSee('Belum ada anggota');
        $this->actingAs($admin)->get('/laporan')->assertOk()->assertSee('Belum ada laporan');
    }

    public function test_archive_filter_shows_filter_empty_state_instead_of_no_data_state(): void
    {
        $admin = User::factory()->administrator()->create();

        Arsip::factory()->create([
            'nama' => 'Arsip yang Ada',
            'nomor' => 'ARS-ADA',
        ]);

        $this->actingAs($admin)
            ->get('/arsip?q=tidak-akan-ditemukan')
            ->assertOk()
            ->assertSee('Arsip tidak ditemukan')
            ->assertDontSee('Belum ada arsip');
    }

    public function test_staff_dashboard_shows_empty_states_without_tasks(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $this->actingAs($staff)
            ->get('/dashboard/staf')
            ->assertOk()
            ->assertSee('Tidak ada laporan aktif')
            ->assertSee('Belum ada tugas aktif');
    }
}
