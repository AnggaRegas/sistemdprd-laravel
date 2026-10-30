<?php

namespace Tests\Feature;

use App\Models\DprdCommission;
use App\Models\RdpDocument;
use App\Models\RdpMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RdpArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    private function meetingPayload(DprdCommission $commission, array $overrides = []): array
    {
        return array_merge([
            'dprd_commission_id' => $commission->id,
            'title' => 'RDP Pembahasan APBD',
            'code' => 'RDP-K1/001/2026',
            'meeting_date' => '2026-10-20',
            'start_time' => '09:30',
            'location' => 'Ruang Rapat Komisi',
            'description' => 'Pembahasan rancangan anggaran.',
            'status' => RdpMeeting::STATUS_TERJADWAL,
            'notes' => 'Catatan internal.',
        ], $overrides);
    }

    public function test_administrator_can_view_rdp_folder_for_a_commission(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create(['name' => 'Komisi Folder']);
        RdpMeeting::factory()->forCommission($commission)->create([
            'title' => 'RDP Milik Komisi Folder',
            'code' => 'RDP-FOLDER-1',
        ]);

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', $commission))
            ->assertOk()
            ->assertSee('Folder RDP Komisi Folder')
            ->assertSee('RDP Milik Komisi Folder')
            ->assertSee('RDP-FOLDER-1')
            ->assertSee('Tambah RDP');
    }

    public function test_rdp_action_menu_is_positioned_safely_outside_the_scroll_container(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        RdpMeeting::factory()->forCommission($commission)->create();

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', $commission))
            ->assertOk()
            ->assertSee('action-dropdown', false)
            ->assertSee('data-bs-boundary="viewport"', false)
            ->assertSee('data-bs-popper-config=\'{"strategy":"fixed"}\'', false);
    }

    public function test_rdp_folder_only_lists_meetings_of_its_own_commission(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create(['name' => 'Komisi A']);
        $commissionB = DprdCommission::factory()->create(['name' => 'Komisi B']);

        RdpMeeting::factory()->forCommission($commissionA)->create([
            'title' => 'RDP Hanya Komisi A',
        ]);
        RdpMeeting::factory()->forCommission($commissionB)->create([
            'title' => 'RDP Hanya Komisi B',
        ]);

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', $commissionA))
            ->assertOk()
            ->assertSee('RDP Hanya Komisi A')
            ->assertDontSee('RDP Hanya Komisi B');
    }

    public function test_administrator_can_create_rdp_in_active_commission(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commission), $this->meetingPayload($commission))
            ->assertRedirect(route('komisi.rdp.index', $commission))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rdp_meetings', [
            'dprd_commission_id' => $commission->id,
            'title' => 'RDP Pembahasan APBD',
            'code' => 'RDP-K1/001/2026',
            'status' => RdpMeeting::STATUS_TERJADWAL,
            'created_by' => $admin->id,
        ]);
    }

    public function test_rdp_creation_rejects_commission_id_from_another_folder(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->create();

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commissionA), $this->meetingPayload($commissionB))
            ->assertSessionHasErrors('dprd_commission_id');

        $this->assertDatabaseCount('rdp_meetings', 0);
    }

    public function test_rdp_creation_validates_required_fields_and_commission_existence(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();

        $this->actingAs($admin)->post(route('komisi.rdp.store', $commission), [
            'dprd_commission_id' => 999999,
            'title' => '',
            'meeting_date' => 'bukan-tanggal',
            'status' => 'status-tidak-valid',
        ])->assertSessionHasErrors([
            'dprd_commission_id',
            'title',
            'meeting_date',
            'status',
        ]);
    }

    public function test_rdp_code_must_be_unique_within_the_same_commission(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        RdpMeeting::factory()->forCommission($commission)->create(['code' => 'RDP-SAMA']);

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commission), $this->meetingPayload($commission, [
                'code' => 'RDP-SAMA',
            ]))
            ->assertSessionHasErrors('code');
    }

    public function test_same_rdp_code_can_be_used_in_a_different_commission(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->create();
        RdpMeeting::factory()->forCommission($commissionA)->create(['code' => 'RDP-BERSAMA']);

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commissionB), $this->meetingPayload($commissionB, [
                'code' => 'RDP-BERSAMA',
            ]))
            ->assertRedirect(route('komisi.rdp.index', $commissionB));

        $this->assertDatabaseHas('rdp_meetings', [
            'dprd_commission_id' => $commissionB->id,
            'code' => 'RDP-BERSAMA',
        ]);
    }

    public function test_rdp_code_can_be_reused_after_soft_delete(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create(['code' => 'RDP-DIGUNAKAN-ULANG']);

        $this->actingAs($admin)
            ->delete(route('komisi.rdp.destroy', [$commission, $meeting]))
            ->assertRedirect(route('komisi.rdp.index', $commission));

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commission), $this->meetingPayload($commission, [
                'code' => 'RDP-DIGUNAKAN-ULANG',
            ]))
            ->assertRedirect(route('komisi.rdp.index', $commission));

        $this->assertSame(
            1,
            RdpMeeting::query()->where('code', 'RDP-DIGUNAKAN-ULANG')->count()
        );
    }

    public function test_rdp_detail_edit_and_delete_are_scoped_to_the_commission(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commissionB)->create();

        $this->actingAs($admin)
            ->get(route('komisi.rdp.show', [$commissionA, $meeting]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('komisi.rdp.edit', [$commissionA, $meeting]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('komisi.rdp.update', [$commissionA, $meeting]), $this->meetingPayload($commissionA))
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('komisi.rdp.destroy', [$commissionA, $meeting]))
            ->assertNotFound();

        $this->assertNotSoftDeleted('rdp_meetings', ['id' => $meeting->id]);
    }

    public function test_administrator_can_update_and_move_rdp_to_another_active_commission(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commissionA)->create();

        $this->actingAs($admin)
            ->put(route('komisi.rdp.update', [$commissionA, $meeting]), $this->meetingPayload($commissionB, [
                'title' => 'RDP Setelah Dipindahkan',
                'code' => 'RDP-PINDAH',
                'status' => RdpMeeting::STATUS_SELESAI,
            ]))
            ->assertRedirect(route('komisi.rdp.index', $commissionB));

        $meeting->refresh();

        $this->assertSame($commissionB->id, $meeting->dprd_commission_id);
        $this->assertSame('RDP Setelah Dipindahkan', $meeting->title);
        $this->assertSame(RdpMeeting::STATUS_SELESAI, $meeting->status);
    }

    public function test_rdp_cannot_be_moved_to_inactive_commission(): void
    {
        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->inactive()->create();
        $meeting = RdpMeeting::factory()->forCommission($commissionA)->create();

        $this->actingAs($admin)
            ->put(route('komisi.rdp.update', [$commissionA, $meeting]), $this->meetingPayload($commissionB))
            ->assertSessionHasErrors('dprd_commission_id');

        $this->assertSame($commissionA->id, $meeting->refresh()->dprd_commission_id);
    }

    public function test_rdp_cannot_be_created_in_inactive_commission(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->inactive()->create();

        $this->actingAs($admin)
            ->get(route('komisi.rdp.create', $commission))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('komisi.rdp.store', $commission), $this->meetingPayload($commission))
            ->assertForbidden();

        $this->assertDatabaseCount('rdp_meetings', 0);
    }

    public function test_rdp_folder_filters_by_search_status_and_date(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();

        RdpMeeting::factory()->forCommission($commission)->create([
            'title' => 'RDP Anggaran Pendidikan',
            'status' => RdpMeeting::STATUS_SELESAI,
            'meeting_date' => '2026-09-01',
        ]);
        RdpMeeting::factory()->forCommission($commission)->create([
            'title' => 'RDP Infrastruktur Jalan',
            'status' => RdpMeeting::STATUS_DRAF,
            'meeting_date' => '2026-10-15',
        ]);

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', [$commission, 'q' => 'Pendidikan']))
            ->assertOk()
            ->assertSee('RDP Anggaran Pendidikan')
            ->assertDontSee('RDP Infrastruktur Jalan');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', [$commission, 'status' => RdpMeeting::STATUS_DRAF]))
            ->assertOk()
            ->assertSee('RDP Infrastruktur Jalan')
            ->assertDontSee('RDP Anggaran Pendidikan');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.index', [$commission, 'tanggal' => '2026-09-01']))
            ->assertOk()
            ->assertSee('RDP Anggaran Pendidikan')
            ->assertDontSee('RDP Infrastruktur Jalan');
    }

    public function test_administrator_can_view_rdp_detail_and_forms(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create([
            'title' => 'RDP Detail Lengkap',
        ]);
        RdpDocument::factory()->forMeeting($meeting)->create([
            'title' => 'Dokumen Detail RDP',
            'category' => RdpDocument::CATEGORY_RISALAH,
        ]);

        $this->actingAs($admin)
            ->get(route('komisi.rdp.show', [$commission, $meeting]))
            ->assertOk()
            ->assertSee('RDP Detail Lengkap')
            ->assertSee('Dokumen Detail RDP')
            ->assertSee('Unggah Dokumen');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.create', $commission))
            ->assertOk()
            ->assertSee('Tambah RDP');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.edit', [$commission, $meeting]))
            ->assertOk()
            ->assertSee('Edit RDP')
            ->assertSee('RDP Detail Lengkap');
    }

    public function test_administrator_can_view_commission_forms(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create(['name' => 'Komisi Form']);

        $this->actingAs($admin)
            ->get(route('komisi.create'))
            ->assertOk()
            ->assertSee('Tambah Komisi');

        $this->actingAs($admin)
            ->get(route('komisi.edit', $commission))
            ->assertOk()
            ->assertSee('Edit Komisi Form')
            ->assertSee('Buka Folder RDP');
    }

    public function test_administrator_can_soft_delete_rdp(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $this->actingAs($admin)
            ->delete(route('komisi.rdp.destroy', [$commission, $meeting]))
            ->assertRedirect(route('komisi.rdp.index', $commission))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('rdp_meetings', ['id' => $meeting->id]);
    }

    public function test_staff_cannot_access_rdp_archive_urls(): void
    {
        $staff = User::factory()->staff()->create();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $this->actingAs($staff)
            ->get(route('komisi.rdp.index', $commission))
            ->assertForbidden();
        $this->actingAs($staff)
            ->get(route('komisi.rdp.show', [$commission, $meeting]))
            ->assertForbidden();
        $this->actingAs($staff)
            ->post(route('komisi.rdp.store', $commission), $this->meetingPayload($commission))
            ->assertForbidden();
        $this->actingAs($staff)
            ->delete(route('komisi.rdp.destroy', [$commission, $meeting]))
            ->assertForbidden();
    }
}
