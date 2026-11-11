<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
use App\Models\User;
use App\Support\AdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PenugasanCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Superadmin Penugasan',
        ]);
    }

    private function staff(DprdMember $member, string $name): User
    {
        return User::factory()->staff($member)->create(['name' => $name]);
    }

    private function waitingReport(DprdMember $member, string $title): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create([
            'ringkasan' => $title,
            'dprd_member_id' => $member->id,
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);
    }

    private function assign(User $admin, LaporanMasyarakat $report): LaporanPenugasan
    {
        $this->actingAs($admin)
            ->patch(route('laporan.persetujuan', $report), [
                'catatan' => 'Silakan tindak lanjuti aspirasi masyarakat ini.',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        return LaporanPenugasan::query()
            ->where('laporan_masyarakat_id', $report->id)
            ->whereNull('released_at')
            ->latest('id')
            ->firstOrFail();
    }

    public function test_full_flow_from_assignment_until_superadmin_notification(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Alur Lengkap']);
        $staff = $this->staff($member, 'Staf Alur Lengkap');
        $report = $this->waitingReport($member, 'Perbaikan Jalan di Jalan Ahmad Yani');

        // 1. Superadmin menugaskan aspirasi kepada Staff A.
        $penugasan = $this->assign($admin, $report);

        $this->assertSame($staff->id, $penugasan->user_id);
        $this->assertSame($admin->id, $penugasan->assigned_by);
        $this->assertSame(LaporanPenugasan::STATUS_DITUGASKAN, $penugasan->status);
        $this->assertNull($penugasan->completed_at);
        $this->assertNull($penugasan->completed_by);

        // 2. Penugasan muncul pada dashboard Staff A.
        $this->actingAs($staff)
            ->get(route('dashboard.staf'))
            ->assertOk()
            ->assertSee('Penugasan Aktif')
            ->assertSee('Perbaikan Jalan di Jalan Ahmad Yani')
            ->assertSee('Ditugaskan');

        // 3. Staff A membuka detail tugas dan melihat formulir hasil.
        $this->actingAs($staff)
            ->get(route('penugasan.show', $penugasan))
            ->assertOk()
            ->assertSee('Hasil Tindak Lanjut')
            ->assertSee('Tandai Selesai');

        $this->actingAs($staff)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Penugasan Tindak Lanjut')
            ->assertSee('Tandai Selesai');

        // 4. Staff A menekan Tandai Selesai dengan hasil tindak lanjut.
        $result = 'Koordinasi telah dilakukan dengan pihak terkait dan laporan sudah diteruskan untuk penanganan lebih lanjut.';

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => $result,
                'keterangan_tambahan' => 'Menunggu jadwal perbaikan dari dinas terkait.',
            ])
            ->assertRedirect(route('dashboard.staf'))
            ->assertSessionHas('success');

        $penugasan->refresh();

        // 5. Status berubah menjadi Selesai Ditindaklanjuti.
        $this->assertSame(LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI, $penugasan->status);
        $this->assertSame('Selesai Ditindaklanjuti', $penugasan->status_label);

        // 6. Tanggal penyelesaian dan identitas Staff tersimpan.
        $this->assertNotNull($penugasan->completed_at);
        $this->assertSame($staff->id, $penugasan->completed_by);
        $this->assertSame($result, $penugasan->hasil_tindak_lanjut);
        $this->assertNotNull($penugasan->started_at);

        // Penyelesaian penugasan tidak menutup aspirasi masyarakat.
        $report->refresh();
        $this->assertNotSame(LaporanMasyarakat::STATUS_SELESAI, $report->status);
        $this->assertNull($report->ditutup_at);

        // 7. Superadmin menerima notifikasi baru yang terhubung ke data nyata.
        $notifications = collect(AdminNotification::for($admin));
        $notificationKey = 'penugasan-selesai-'.$penugasan->id;

        $this->assertTrue($notifications->contains('key', $notificationKey));

        $notification = $notifications->firstWhere('key', $notificationKey);
        $this->assertSame('Penugasan telah diselesaikan', $notification['judul']);
        $this->assertStringContainsString('Staf Alur Lengkap', $notification['isi']);
        $this->assertStringContainsString('Perbaikan Jalan di Jalan Ahmad Yani', $notification['isi']);
        $this->assertSame(route('penugasan.show', $penugasan), $notification['url']);

        // 8. Superadmin dapat membuka detail hasil tindak lanjut.
        $this->actingAs($admin)
            ->get(route('penugasan.show', $penugasan))
            ->assertOk()
            ->assertSee('Perbaikan Jalan di Jalan Ahmad Yani')
            ->assertSee('Staf Alur Lengkap')
            ->assertSee($result)
            ->assertSee('Selesai Ditindaklanjuti')
            ->assertSee('Konfirmasi Hasil Tindak Lanjut');

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertDontSee('Penugasan Tindak Lanjut')
            ->assertDontSee($result);
    }

    public function test_bukti_tindak_lanjut_stored_privately_and_downloadable_by_owner_and_admin(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Bukti');
        $report = $this->waitingReport($member, 'Aspirasi dengan Bukti');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => 'Surat koordinasi telah dikirim ke dinas terkait.',
                'bukti' => UploadedFile::fake()->create('bukti-koordinasi.pdf', 256, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard.staf'))
            ->assertSessionHas('success');

        $penugasan->refresh();

        $this->assertTrue($penugasan->hasBukti());
        $this->assertNotNull($penugasan->bukti_path);
        $this->assertSame('bukti-koordinasi.pdf', $penugasan->bukti_nama);
        Storage::disk('local')->assertExists($penugasan->bukti_path);

        $this->actingAs($staff)
            ->get(route('penugasan.bukti', $penugasan))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('penugasan.bukti', $penugasan))
            ->assertOk();
    }

    public function test_staff_b_cannot_complete_assignment_owned_by_staff_a(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = $this->staff($memberA, 'Staf A');
        $staffB = $this->staff($memberB, 'Staf B');
        $report = $this->waitingReport($memberA, 'Tugas Milik Staf A');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staffB)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => 'Percobaan menyelesaikan tugas orang lain.',
            ])
            ->assertForbidden();

        $this->actingAs($staffB)
            ->get(route('penugasan.show', $penugasan))
            ->assertForbidden();

        $penugasan->refresh();
        $this->assertSame(LaporanPenugasan::STATUS_DITUGASKAN, $penugasan->status);
        $this->assertNull($penugasan->completed_at);
        $this->assertNull($penugasan->hasil_tindak_lanjut);

        $this->assertFalse(
            collect(AdminNotification::for($admin))->contains('key', 'penugasan-selesai-'.$penugasan->id)
        );
    }

    public function test_repeated_completion_request_is_idempotent_and_does_not_duplicate_notification(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Idempoten');
        $report = $this->waitingReport($member, 'Aspirasi Idempoten');
        $penugasan = $this->assign($admin, $report);

        $firstResult = 'Hasil tindak lanjut pertama yang sudah disimpan.';

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => $firstResult,
            ])
            ->assertRedirect(route('dashboard.staf'));

        $penugasan->refresh();
        $firstCompletedAt = $penugasan->completed_at;

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => 'Hasil kedua yang tidak boleh menimpa hasil pertama.',
            ])
            ->assertRedirect(route('dashboard.staf'))
            ->assertSessionHas('success');

        $penugasan->refresh();

        $this->assertSame($firstResult, $penugasan->hasil_tindak_lanjut);
        $this->assertTrue($firstCompletedAt->equalTo($penugasan->completed_at));

        $notifications = collect(AdminNotification::for($admin))
            ->where('key', 'penugasan-selesai-'.$penugasan->id);

        $this->assertCount(1, $notifications);
    }

    public function test_other_unfinished_assignment_keeps_its_status(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Multi Tugas');
        $firstReport = $this->waitingReport($member, 'Tugas Pertama');
        $secondReport = $this->waitingReport($member, 'Tugas Kedua');

        $first = $this->assign($admin, $firstReport);
        $second = $this->assign($admin, $secondReport);

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $first), [
                'hasil_tindak_lanjut' => 'Tugas pertama telah selesai ditindaklanjuti.',
            ])
            ->assertRedirect(route('dashboard.staf'));

        $first->refresh();
        $second->refresh();

        $this->assertSame(LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI, $first->status);
        $this->assertSame(LaporanPenugasan::STATUS_DITUGASKAN, $second->status);
        $this->assertNull($second->completed_at);
        $this->assertNull($second->completed_by);
        $this->assertNull($second->hasil_tindak_lanjut);
    }

    public function test_assignment_requires_result_before_it_can_be_completed(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Validasi');
        $report = $this->waitingReport($member, 'Aspirasi Wajib Hasil');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [])
            ->assertSessionHasErrorsIn('penugasan', ['hasil_tindak_lanjut']);

        $penugasan->refresh();
        $this->assertSame(LaporanPenugasan::STATUS_DITUGASKAN, $penugasan->status);
        $this->assertNull($penugasan->completed_at);
    }

    public function test_staff_report_status_diproses_syncs_active_assignment_to_sedang_ditindaklanjuti(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Sinkron');
        $report = $this->waitingReport($member, 'Aspirasi Sinkron Status');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staff)
            ->patch(route('laporan.tindak-lanjut.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
                'catatan' => 'Sedang berkoordinasi dengan instansi terkait.',
            ])
            ->assertRedirect();

        $penugasan->refresh();

        $this->assertSame(LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI, $penugasan->status);
        $this->assertNotNull($penugasan->started_at);

        // Penyelesaian penugasan tetap harus dilakukan secara eksplisit.
        $this->assertFalse($penugasan->isSelesai());
        $this->assertNull($penugasan->completed_at);
    }

    public function test_start_action_moves_assignment_to_sedang_ditindaklanjuti_without_closing_report(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Mulai');
        $report = $this->waitingReport($member, 'Aspirasi Mulai Ditindaklanjuti');
        $penugasan = $this->assign($admin, $report);

        $reportStatusBefore = $report->fresh()->status;

        $this->actingAs($staff)
            ->post(route('penugasan.mulai', $penugasan))
            ->assertRedirect();

        $penugasan->refresh();
        $report->refresh();

        $this->assertSame(LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI, $penugasan->status);
        $this->assertNotNull($penugasan->started_at);
        $this->assertSame($reportStatusBefore, $report->status);
    }

    public function test_superadmin_verification_only_marks_assignment_and_keeps_report_status(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Verifikasi');
        $report = $this->waitingReport($member, 'Aspirasi Verifikasi Hasil');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => 'Hasil tindak lanjut siap diverifikasi Superadmin.',
            ])
            ->assertRedirect(route('dashboard.staf'));

        $reportStatusAfterCompletion = $report->fresh()->status;

        $this->actingAs($admin)
            ->post(route('penugasan.verifikasi', $penugasan), [
                'catatan_verifikasi' => 'Hasil sudah sesuai, lanjutkan pemantauan.',
            ])
            ->assertRedirect();

        $penugasan->refresh();
        $report->refresh();

        $this->assertTrue($penugasan->isVerified());
        $this->assertSame($admin->id, $penugasan->verified_by);
        $this->assertSame('Hasil sudah sesuai, lanjutkan pemantauan.', $penugasan->catatan_verifikasi);
        $this->assertSame($reportStatusAfterCompletion, $report->status);
    }

    public function test_staff_only_sees_own_assignments_in_monitoring_page(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = $this->staff($memberA, 'Staf Monitoring A');
        $staffB = $this->staff($memberB, 'Staf Monitoring B');

        $reportA = $this->waitingReport($memberA, 'Aspirasi Milik Staf A');
        $reportB = $this->waitingReport($memberB, 'Aspirasi Milik Staf B');

        $this->assign($admin, $reportA);
        $this->assign($admin, $reportB);

        $this->actingAs($staffA)
            ->get(route('penugasan.index'))
            ->assertOk()
            ->assertSee('Aspirasi Milik Staf A')
            ->assertDontSee('Aspirasi Milik Staf B');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Monitoring Penugasan');

        $this->actingAs($admin)
            ->get(route('penugasan.index'))
            ->assertOk()
            ->assertSee('Aspirasi Milik Staf A')
            ->assertSee('Aspirasi Milik Staf B')
            ->assertSee('Monitoring Penugasan Staff Pendamping');
    }

    public function test_staff_export_contains_only_own_assignments(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Ekspor A']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Ekspor B']);
        $staffA = $this->staff($memberA, 'Staf Ekspor A');
        $staffB = $this->staff($memberB, 'Staf Ekspor B');

        $reportA = $this->waitingReport($memberA, 'Aspirasi Ekspor Milik A');
        $reportB = $this->waitingReport($memberB, 'Aspirasi Ekspor Milik B');

        $this->assign($admin, $reportA);
        $this->assign($admin, $reportB);

        $response = $this->actingAs($staffA)
            ->get(route('dashboard.staf.ekspor'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSessionHas('success');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Nomor Tiket', $content);
        $this->assertStringContainsString('Aspirasi Ekspor Milik A', $content);
        $this->assertStringContainsString('Anggota Ekspor A', $content);
        $this->assertStringNotContainsString('Aspirasi Ekspor Milik B', $content);
        $this->assertStringNotContainsString('Anggota Ekspor B', $content);

        // Staf B tetap dapat mengekspor datanya sendiri.
        $contentB = $this->actingAs($staffB)
            ->get(route('dashboard.staf.ekspor'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Aspirasi Ekspor Milik B', $contentB);
        $this->assertStringNotContainsString('Aspirasi Ekspor Milik A', $contentB);

        // Superadmin tidak boleh memakai endpoint ekspor milik staf.
        $this->actingAs($admin)
            ->get(route('dashboard.staf.ekspor'))
            ->assertForbidden();
    }

    public function test_notification_can_be_marked_read_and_opens_assignment_detail(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = $this->staff($member, 'Staf Notifikasi');
        $report = $this->waitingReport($member, 'Aspirasi Notifikasi Baca');
        $penugasan = $this->assign($admin, $report);

        $this->actingAs($staff)
            ->post(route('penugasan.selesai', $penugasan), [
                'hasil_tindak_lanjut' => 'Hasil tindak lanjut untuk menguji notifikasi.',
            ])
            ->assertRedirect(route('dashboard.staf'));

        $key = 'penugasan-selesai-'.$penugasan->id;

        $notification = collect(AdminNotification::for($admin))->firstWhere('key', $key);
        $this->assertNotNull($notification);
        $this->assertFalse($notification['read']);

        $this->actingAs($admin)
            ->get(route('notifikasi.buka', $key))
            ->assertRedirect(route('penugasan.show', $penugasan));

        $notification = collect(AdminNotification::for($admin))->firstWhere('key', $key);
        $this->assertTrue($notification['read']);
    }
}
