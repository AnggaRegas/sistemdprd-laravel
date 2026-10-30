<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(string $name = 'Administrator Notifikasi'): User
    {
        return User::factory()->administrator()->create(['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pendingReport(array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            'assigned_to' => null,
            'assigned_at' => null,
        ], $overrides));
    }

    public function test_unread_badge_and_notification_click_marks_item_as_read(): void
    {
        $admin = $this->administrator();
        $report = $this->pendingReport(['nomor_tiket' => 'LPR-NOTIF-001']);

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('2 belum dibaca')
            ->assertSee('data-notif-badge', false)
            ->assertSee('is-unread', false)
            ->assertSee(route('notifikasi.buka', [
                'notificationKey' => 'laporan-baru-'.$report->id,
            ]), false)
            ->assertSee('Tandai semua dibaca');

        $this->actingAs($admin)
            ->get(route('notifikasi.buka', [
                'notificationKey' => 'laporan-baru-'.$report->id,
            ]))
            ->assertRedirect(route('laporan.show', $report));

        $this->assertDatabaseHas('notification_reads', [
            'user_id' => $admin->id,
            'notification_key' => 'laporan-baru-'.$report->id,
        ]);
        $this->assertNotNull(
            NotificationRead::query()
                ->where('user_id', $admin->id)
                ->where('notification_key', 'laporan-baru-'.$report->id)
                ->value('read_at')
        );

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('1 belum dibaca')
            ->assertSee('notif-item is-read', false)
            ->assertDontSee('2 belum dibaca');
    }

    public function test_mark_all_notifications_read_persists_after_reload(): void
    {
        $admin = $this->administrator();
        $this->pendingReport();
        $this->pendingReport();

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('3 belum dibaca');

        $this->actingAs($admin)
            ->post(route('notifikasi.baca-semua'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertSame(
            3,
            NotificationRead::query()->where('user_id', $admin->id)->count()
        );

        $this->flushSession();

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Semua dibaca')
            ->assertDontSee('data-notif-badge', false)
            ->assertDontSee('Tandai semua dibaca');
    }

    public function test_notification_read_state_is_isolated_per_user(): void
    {
        $adminA = $this->administrator('Admin Notifikasi A');
        $adminB = $this->administrator('Admin Notifikasi B');
        $report = $this->pendingReport();

        $this->actingAs($adminA)
            ->get(route('notifikasi.buka', [
                'notificationKey' => 'laporan-baru-'.$report->id,
            ]))
            ->assertRedirect(route('laporan.show', $report));

        $this->actingAs($adminA)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('1 belum dibaca');

        $this->actingAs($adminB)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('2 belum dibaca');

        $this->assertDatabaseMissing('notification_reads', [
            'user_id' => $adminB->id,
            'notification_key' => 'laporan-baru-'.$report->id,
        ]);
    }

    public function test_notification_key_cannot_be_opened_by_a_user_who_does_not_own_it(): void
    {
        $admin = $this->administrator();
        $report = $this->pendingReport();

        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $this->actingAs($staff)
            ->get(route('notifikasi.buka', [
                'notificationKey' => 'laporan-baru-'.$report->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseMissing('notification_reads', [
            'user_id' => $staff->id,
            'notification_key' => 'laporan-baru-'.$report->id,
        ]);
    }

    public function test_staff_can_open_own_assignment_notification_and_it_marks_read(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $key = 'laporan-ditugaskan-'.$report->id.'-'.$report->assigned_at->getTimestamp();

        $this->actingAs($staff)
            ->get(route('dashboard.staf'))
            ->assertOk()
            ->assertSee('1 belum dibaca')
            ->assertSee(route('notifikasi.buka', ['notificationKey' => $key]), false);

        $this->actingAs($staff)
            ->get(route('notifikasi.buka', ['notificationKey' => $key]))
            ->assertRedirect(route('laporan.show', $report));

        $this->assertDatabaseHas('notification_reads', [
            'user_id' => $staff->id,
            'notification_key' => $key,
        ]);
    }
}
