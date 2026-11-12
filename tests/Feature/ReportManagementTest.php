<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Laporan',
        ]);
    }

    private function report(array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create($overrides);
    }

    public function test_administrator_sees_dynamic_member_folders_on_report_list(): void
    {
        $admin = $this->administrator();

        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Folder Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Folder Beta']);

        $this->report([
            'nomor_tiket' => 'LPR-FOLDER-A',
            'ringkasan' => 'Laporan Folder Alpha',
            'dprd_member_id' => $memberA->id,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Folder Anggota DPRD')
            ->assertSee('Anggota Folder Alpha')
            ->assertSee('Anggota Folder Beta')
            ->assertDontSee('Laporan Folder Alpha')
            ->assertSee('anggota='.$memberA->id, false);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $memberA->id]))
            ->assertOk()
            ->assertSee('Laporan Folder Alpha')
            ->assertDontSee('Laporan Beta Filter');

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $memberA->id, 'q' => 'Folder Alpha']))
            ->assertOk()
            ->assertSee('Laporan Folder Alpha')
            ->assertSee('1 laporan ditemukan');
    }

    public function test_administrator_folder_list_supports_member_name_search(): void
    {
        $admin = $this->administrator();

        DprdMember::factory()->create(['nama_lengkap' => 'Anggota Cari Soreang']);
        DprdMember::factory()->create(['nama_lengkap' => 'Anggota Lain Ujung']);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['q' => 'Soreang']))
            ->assertOk()
            ->assertSee('Anggota Cari Soreang')
            ->assertDontSee('Anggota Lain Ujung');
    }

    public function test_administrator_folder_shows_similarity_label_and_detail_shows_similarity_card(): void
    {
        config()->set('aspiration_similarity.high_threshold', 0.80);
        config()->set('aspiration_similarity.medium_threshold', 0.55);

        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Duplikat Uji']);

        $report = $this->report([
            'nomor_tiket' => 'LPR-DUP-001',
            'ringkasan' => 'Laporan berpotensi duplikat',
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);

        $previous = $this->report([
            'nomor_tiket' => 'LPR-DUP-000',
            'ringkasan' => 'Aspirasi terdahulu yang mirip',
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        LaporanSimilarityResult::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'compared_laporan_masyarakat_id' => $previous->id,
            'similarity_score' => 0.95,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
            'evidence' => [
                'cosine_similarity' => 0.95,
                'context_factor' => 1.0,
                'final_score' => 0.95,
                'shared_terms' => [
                    ['term' => 'jalan', 'weight_left' => 0.4, 'weight_right' => 0.4, 'contribution' => 0.16],
                ],
                'reasons' => ['Wilayah permasalahan sama.'],
            ],
            'checked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Laporan berpotensi duplikat')
            ->assertSee('Ada Kemiripan')
            ->assertDontSee('<span class="cell-muted">95%</span>', false)
            ->assertDontSee('similarity-dot')
            ->assertDontSee('Aspirasi Mirip Ditemukan')
            ->assertDontSee('Bandingkan');

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Laporan berpotensi duplikat')
            ->assertDontSee('Kemiripan Aspirasi')
            ->assertDontSee('Aspirasi Mirip Ditemukan')
            ->assertDontSee('LPR-DUP-000')
            ->assertDontSee('95,0%')
            ->assertDontSee('Tidak Ada Kemiripan');
    }

    public function test_administrator_can_approve_report_and_auto_dispose_to_member_staff(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Disposisi Uji']);
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Disposisi Uji']);

        $report = $this->report([
            'nomor_tiket' => 'LPR-APPROVE-001',
            'dprd_member_id' => $member->id,
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_BARU,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.persetujuan', $report), [
                'catatan' => 'Silakan koordinasikan dengan lingkungan terkait.',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame($member->id, $report->dprd_member_id);
        $this->assertSame($staff->id, $report->assigned_to);
        $this->assertSame($admin->id, $report->disetujui_oleh);
        $this->assertNotNull($report->disetujui_at);
        $this->assertSame(LaporanMasyarakat::STATUS_DITUGASKAN, $report->status);
        $this->assertNull($report->ditutup_at);
        $this->assertNotNull($report->assigned_at);

        $this->assertDatabaseHas('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'released_at' => null,
            'catatan' => 'Silakan koordinasikan dengan lingkungan terkait.',
        ]);

        $this->assertDatabaseHas('aktivitas_sistem', [
            'user_id' => $admin->id,
            'event' => 'laporan.disetujui',
            'subject_type' => $report->getMorphClass(),
            'subject_id' => $report->id,
        ]);
    }

    public function test_approval_without_active_staff_keeps_report_in_queue(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        DprdMember::factory()->create(); // anggota lain tanpa staf

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.persetujuan', $report), [])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('warning');

        $report->refresh();

        $this->assertNull($report->assigned_to);
        $this->assertNull($report->disetujui_oleh);
        $this->assertNull($report->disetujui_at);
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);

        $this->assertDatabaseMissing('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
        ]);
    }

    public function test_approval_disposisikan_ulang_updates_history_without_duplicating_active_assignment(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        LaporanPenugasan::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'released_at' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.persetujuan', $report), [])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame($staff->id, $report->assigned_to);
        $this->assertNotNull($report->disetujui_at);

        $this->assertSame(
            1,
            LaporanPenugasan::query()
                ->where('laporan_masyarakat_id', $report->id)
                ->whereNull('released_at')
                ->count()
        );
    }

    public function test_closed_report_cannot_be_approved(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.persetujuan', $report), [])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('approval', ['approval']);

        $report->refresh();
        $this->assertNull($report->disetujui_at);
    }

    public function test_staff_cannot_approve_or_change_status_of_administrator_actions(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $this->actingAs($staff)
            ->patch(route('laporan.persetujuan', $report), [])
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('laporan.tindak-lanjut.update', $report), [
                'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            ])
            ->assertRedirect()
            ->assertSessionHasErrorsIn('status', ['status']);

        $report->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_DITUGASKAN, $report->status);
    }

    public function test_staff_only_sees_reports_assigned_to_them(): void
    {
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Staf Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Staf Beta']);
        $staffA = User::factory()->staff($memberA)->create(['name' => 'Staf Alpha']);
        $staffB = User::factory()->staff($memberB)->create(['name' => 'Staf Beta']);

        $reportA = $this->report([
            'nomor_tiket' => 'LPR-STAFF-ALPHA',
            'ringkasan' => 'Laporan Milik Staf Alpha',
            'dprd_member_id' => $memberA->id,
            'assigned_to' => $staffA->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $reportB = $this->report([
            'nomor_tiket' => 'LPR-STAFF-BETA',
            'ringkasan' => 'Laporan Milik Staf Beta',
            'dprd_member_id' => $memberB->id,
            'assigned_to' => $staffB->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $this->actingAs($staffA)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee($reportA->pelapor_nama)
            ->assertSee($reportA->ringkasan)
            ->assertSee('Lihat Detail')
            ->assertDontSee($reportB->ringkasan)
            ->assertDontSee('Folder Anggota DPRD');

        $this->actingAs($staffA)
            ->get(route('laporan.show', $reportA))
            ->assertOk()
            ->assertSee($reportA->ringkasan)
            ->assertDontSee('Hasil Deteksi Kemiripan')
            ->assertDontSee('Verifikasi &amp; Disposisi', false);

        $this->actingAs($staffA)
            ->get(route('laporan.show', $reportB))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran', $reportB))
            ->assertNotFound();
    }

    public function test_staff_sees_members_menu_scoped_to_their_assigned_member(): void
    {
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Menu Staf']);
        $other = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Menu Lain']);
        $staff = User::factory()->staff($member)->create();

        $this->actingAs($staff)
            ->get(route('dashboard.staf'))
            ->assertOk()
            ->assertDontSee('<span class="sidebar-label">Anggota DPRD</span>', false)
            ->assertSee('Pokir &amp; Hasil Reses', false);

        $admin = $this->administrator();
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<span class="sidebar-label">Anggota DPRD</span>', false);

        $this->actingAs($staff)
            ->get(route('anggota.index'))
            ->assertOk()
            ->assertSee('Anggota Menu Staf')
            ->assertDontSee('Anggota Menu Lain')
            ->assertDontSee('Buka Folder')
            ->assertSee('Buka Arsip')
            ->assertSee(route('anggota.show', $member), false);

        $this->actingAs($staff)
            ->get(route('anggota.show', $member))
            ->assertOk()
            ->assertSee('Pokir')
            ->assertSee('Hasil Reses');

        $this->actingAs($staff)
            ->get(route('anggota.show', $other))
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('laporan.status.update', $this->report()), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
            ])
            ->assertForbidden();
    }

    public function test_staff_can_move_status_only_through_tindak_lanjut_steps(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        LaporanPenugasan::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'released_at' => null,
        ]);

        $this->actingAs($staff)
            ->patch(route('laporan.tindak-lanjut.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
                'catatan' => 'Sedang berkoordinasi dengan warga.',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame(LaporanMasyarakat::STATUS_DIPROSES, $report->status);
        $this->assertSame('Sedang berkoordinasi dengan warga.', $report->catatan_penyelesaian);
        $this->assertNull($report->ditutup_at);
        $this->assertNull($report->disetujui_oleh ?: null);

        $this->actingAs($staff)
            ->patch(route('laporan.tindak-lanjut.update', $report), [
                'status' => LaporanMasyarakat::STATUS_SELESAI,
                'catatan' => 'Tindak lanjut selesai.',
            ])
            ->assertRedirect(route('laporan.index'));

        $report->refresh();

        $this->assertSame(LaporanMasyarakat::STATUS_SELESAI, $report->status);
        $this->assertNotNull($report->ditutup_at);

        $this->assertDatabaseHas('aktivitas_sistem', [
            'user_id' => $staff->id,
            'event' => 'laporan.tindak_lanjut',
            'subject_id' => $report->id,
        ]);
    }

    public function test_staff_cannot_update_unassigned_report(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => null,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($staff)
            ->patch(route('laporan.tindak-lanjut.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
            ])
            ->assertForbidden();

        $report->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);
    }

    public function test_administrator_can_move_status_through_valid_transitions(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $assignment = LaporanPenugasan::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'released_at' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
                'catatan' => 'Sedang dikoordinasikan dengan instansi terkait.',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();
        $assignment->refresh();

        $this->assertSame(LaporanMasyarakat::STATUS_DIPROSES, $report->status);
        $this->assertSame('Sedang dikoordinasikan dengan instansi terkait.', $report->catatan_penyelesaian);
        $this->assertNull($report->ditutup_at);
        $this->assertNull($assignment->released_at);

        $this->actingAs($admin)
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_SELESAI,
                'catatan' => 'Perbaikan selesai dan sudah diverifikasi.',
            ])
            ->assertRedirect(route('laporan.index'));

        $report->refresh();
        $assignment->refresh();

        $this->assertSame(LaporanMasyarakat::STATUS_SELESAI, $report->status);
        $this->assertNotNull($report->ditutup_at);
        $this->assertNotNull($assignment->released_at);
    }

    public function test_returning_report_to_waiting_queue_clears_assignment_and_approval(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'disetujui_oleh' => $admin->id,
            'disetujui_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        LaporanPenugasan::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'released_at' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertNull($report->assigned_to);
        $this->assertNull($report->assigned_at);
        $this->assertNull($report->disetujui_oleh);
        $this->assertNull($report->disetujui_at);
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);

        $this->assertDatabaseMissing('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
            'released_at' => null,
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $admin = $this->administrator();

        $report = $this->report([
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
            ])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('status', ['status']);

        $report->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_SELESAI, $report->status);
    }

    public function test_staff_can_download_and_view_attachment_for_assigned_report_only(): void
    {
        Storage::fake('local');

        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = User::factory()->staff($memberA)->create();
        User::factory()->staff($memberB)->create();

        $pathA = 'laporan-masyarakat/2026/10/alpha.pdf';
        $pathB = 'laporan-masyarakat/2026/10/beta.pdf';
        Storage::disk('local')->put($pathA, 'lampiran alpha');
        Storage::disk('local')->put($pathB, 'lampiran beta');

        $reportA = $this->report([
            'dprd_member_id' => $memberA->id,
            'assigned_to' => $staffA->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DIPROSES,
            'lampiran_path' => $pathA,
            'lampiran_nama' => 'alpha.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 1024,
        ]);

        $reportB = $this->report([
            'dprd_member_id' => $memberB->id,
            'status' => LaporanMasyarakat::STATUS_DIPROSES,
            'lampiran_path' => $pathB,
            'lampiran_nama' => 'beta.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 1024,
        ]);

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran', $reportA))
            ->assertOk()
            ->assertDownload('alpha.pdf');

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran.lihat', $reportA))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran', $reportB))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran.lihat', $reportB))
            ->assertNotFound();
    }

    public function test_report_detail_escapes_user_supplied_content(): void
    {
        $admin = $this->administrator();

        $report = $this->report([
            'ringkasan' => '<script>alert("xss")</script>',
            'isi' => '<script>alert("isi")</script>',
        ]);

        $response = $this->actingAs($admin)->get(route('laporan.show', $report));

        $response->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertDontSee('<script>alert("isi")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_report_detail_has_back_button_and_uses_validated_return_url(): void
    {
        $admin = $this->administrator();
        $report = $this->report(['nomor_tiket' => 'LPR-RETURN-001']);

        $returnUrl = route('laporan.index', [
            'q' => 'LPR-RETURN',
            'status' => 'Menunggu Penugasan',
            'page' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', ['laporan' => $report, 'return_to' => $returnUrl]))
            ->assertOk()
            ->assertSee('Kembali ke Daftar Laporan')
            ->assertSee('value="/laporan?q=LPR-RETURN&amp;status=Menunggu+Penugasan&amp;page=2"', false);

        $this->actingAs($admin)
            ->get(route('laporan.show', [
                'laporan' => $report,
                'return_to' => 'https://evil.example/laporan?status=Menunggu+Penugasan',
            ]))
            ->assertOk()
            ->assertDontSee('evil.example', false)
            ->assertSee('href="'.route('laporan.index').'"', false);

        $this->actingAs($admin)
            ->get(route('laporan.show', ['laporan' => $report, 'return_to' => '/dashboard']))
            ->assertOk()
            ->assertDontSee('value="/dashboard"', false)
            ->assertDontSee('return_to=%2Fdashboard', false);
    }

    public function test_waiting_report_detail_uses_single_setuju_action_without_top_verification_button(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        User::factory()->staff($member)->create();

        $report = $this->report([
            'nomor_tiket' => 'LPR-SETUJU-001',
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            'disetujui_at' => null,
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Kembali ke Daftar Laporan')
            ->assertSee('Setuju')
            ->assertDontSee('SETUJUI')
            ->assertDontSee('Setujui &amp; Disposisikan', false)
            ->assertDontSee('href="#persetujuan"', false)
            ->assertSee('id="persetujuan"', false);
    }

    public function test_assigned_report_detail_hides_assignment_and_reassignment_controls(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Ditugaskan']);
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Penerima Tugas']);

        $report = $this->report([
            'nomor_tiket' => 'LPR-DITUGASKAN-001',
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
            'disetujui_at' => now(),
            'disetujui_oleh' => $admin->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Kembali ke Daftar Laporan')
            ->assertSee('Ditugaskan')
            ->assertSee('Staf Penerima Tugas')
            ->assertDontSee('Penugasan Tindak Lanjut')
            ->assertDontSee('Verifikasi &amp; Disposisi', false)
            ->assertDontSee('Disposisi Ulang')
            ->assertDontSee('id="persetujuan"', false)
            ->assertDontSee('href="#persetujuan"', false);
    }
}
