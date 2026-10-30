<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
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

    public function test_administrator_report_list_supports_kecamatan_date_and_status_filters(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Filter Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Filter Beta']);

        $reportA = $this->report([
            'nomor_tiket' => 'LPR-FILTER-ALPHA',
            'ringkasan' => 'Laporan Alpha Filter',
            'dprd_member_id' => $memberA->id,
            'kategori' => 'Pendidikan',
            'kecamatan' => 'Kecamatan Bacukiki',
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            'tanggal_laporan' => '2026-09-10',
        ]);

        $reportB = $this->report([
            'nomor_tiket' => 'LPR-FILTER-BETA',
            'ringkasan' => 'Laporan Beta Filter',
            'dprd_member_id' => $memberB->id,
            'kategori' => 'Kesehatan',
            'kecamatan' => 'Kecamatan Soreang',
            'status' => LaporanMasyarakat::STATUS_DIPROSES,
            'tanggal_laporan' => '2026-10-10',
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee($reportA->ringkasan)
            ->assertSee($reportB->ringkasan)
            ->assertSee('Kecamatan Bacukiki')
            ->assertSee('Kecamatan Soreang');

        $this->actingAs($admin)
            ->get(route('laporan.index', ['kecamatan' => 'Kecamatan Bacukiki']))
            ->assertOk()
            ->assertSee($reportA->ringkasan)
            ->assertDontSee($reportB->ringkasan);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['kategori' => 'Kesehatan']))
            ->assertOk()
            ->assertSee($reportB->ringkasan)
            ->assertDontSee($reportA->ringkasan);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['tanggal_dari' => '2026-10-01', 'tanggal_sampai' => '2026-10-31']))
            ->assertOk()
            ->assertSee($reportB->ringkasan)
            ->assertDontSee($reportA->ringkasan);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['status' => 'Menunggu Penugasan']))
            ->assertOk()
            ->assertSee($reportA->ringkasan)
            ->assertDontSee($reportB->ringkasan);
    }

    public function test_administrator_can_open_report_detail_and_download_private_attachment(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $path = 'laporan-masyarakat/2026/10/bukti.pdf';
        Storage::disk('local')->put($path, 'isi lampiran uji');

        $report = $this->report([
            'nomor_tiket' => 'LPR-DETAIL-001',
            'ringkasan' => 'Detail laporan dengan lampiran',
            'pelapor_nama' => 'Pelapor Detail',
            'pelapor_kontak' => 'pelapor@example.com',
            'kecamatan' => 'Kecamatan Ujung',
            'lampiran_path' => $path,
            'lampiran_nama' => 'bukti.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Detail laporan dengan lampiran')
            ->assertSee('Pelapor Detail')
            ->assertSee('pelapor@example.com')
            ->assertSee('Kecamatan Ujung')
            ->assertSee('bukti.pdf')
            ->assertSee(route('laporan.lampiran', $report), false);

        $this->actingAs($admin)
            ->get(route('laporan.lampiran', $report))
            ->assertOk()
            ->assertDownload('bukti.pdf');
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
            ->assertSee($reportA->nomor_tiket)
            ->assertDontSee($reportB->nomor_tiket);

        $this->actingAs($staffA)
            ->get(route('laporan.show', $reportA))
            ->assertOk()
            ->assertSee($reportA->ringkasan);

        $this->actingAs($staffA)
            ->get(route('laporan.show', $reportB))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->get(route('laporan.lampiran', $reportB))
            ->assertNotFound();
    }

    public function test_administrator_can_assign_member_and_matching_active_staff(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Pendamping Uji']);

        $report = $this->report([
            'nomor_tiket' => 'LPR-ASSIGN-001',
            'dprd_member_id' => null,
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => $staff->id,
                'catatan' => 'Mohon ditindaklanjuti pekan ini.',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame($member->id, $report->dprd_member_id);
        $this->assertSame($staff->id, $report->assigned_to);
        $this->assertSame(LaporanMasyarakat::STATUS_DITUGASKAN, $report->status);
        $this->assertNotNull($report->assigned_at);

        $this->assertDatabaseHas('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'released_at' => null,
            'catatan' => 'Mohon ditindaklanjuti pekan ini.',
        ]);

        $this->assertDatabaseHas('aktivitas_sistem', [
            'user_id' => $admin->id,
            'event' => 'laporan.penugasan',
            'subject_type' => $report->getMorphClass(),
            'subject_id' => $report->id,
        ]);
    }

    public function test_reassignment_releases_previous_assignment_and_creates_new_history(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = User::factory()->staff($memberA)->create();
        $staffB = User::factory()->staff($memberB)->create();

        $report = $this->report([
            'dprd_member_id' => $memberA->id,
            'assigned_to' => $staffA->id,
            'assigned_at' => now()->subDay(),
            'status' => LaporanMasyarakat::STATUS_DIPROSES,
        ]);

        $oldAssignment = LaporanPenugasan::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staffA->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now()->subDay(),
            'released_at' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $memberB->id,
                'assigned_to' => $staffB->id,
                'catatan' => 'Dialihkan karena wilayah tugas.',
            ])
            ->assertRedirect(route('laporan.index'));

        $report->refresh();
        $oldAssignment->refresh();

        $this->assertSame($memberB->id, $report->dprd_member_id);
        $this->assertSame($staffB->id, $report->assigned_to);
        $this->assertSame(LaporanMasyarakat::STATUS_DIPROSES, $report->status);
        $this->assertNotNull($oldAssignment->released_at);

        $this->assertDatabaseHas('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
            'user_id' => $staffB->id,
            'released_at' => null,
            'catatan' => 'Dialihkan karena wilayah tugas.',
        ]);
    }

    public function test_removing_staff_returns_report_to_waiting_queue(): void
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
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => '',
            ])
            ->assertRedirect(route('laporan.index'));

        $report->refresh();

        $this->assertNull($report->assigned_to);
        $this->assertNull($report->assigned_at);
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);

        $this->assertDatabaseMissing('laporan_penugasans', [
            'laporan_masyarakat_id' => $report->id,
            'released_at' => null,
        ]);
    }

    public function test_assignment_rejects_staff_from_a_different_member(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffB = User::factory()->staff($memberB)->create();

        $report = $this->report([
            'dprd_member_id' => $memberA->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $memberA->id,
                'assigned_to' => $staffB->id,
            ])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('assignment', ['assigned_to']);

        $report->refresh();

        $this->assertNull($report->assigned_to);
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);
    }

    public function test_closed_report_cannot_be_reassigned(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => $staff->id,
            ])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('assignment', ['dprd_member_id']);
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
        $this->assertSame('Perbaikan selesai dan sudah diverifikasi.', $report->catatan_penyelesaian);
        $this->assertNotNull($report->ditutup_at);
        $this->assertNotNull($assignment->released_at);
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

    public function test_staff_cannot_update_assignment_or_status(): void
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
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => $staff->id,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('laporan.status.update', $report), [
                'status' => LaporanMasyarakat::STATUS_DIPROSES,
            ])
            ->assertForbidden();

        $report->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_DITUGASKAN, $report->status);
    }

    public function test_staff_can_download_attachment_for_assigned_report_only(): void
    {
        Storage::fake('local');

        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = User::factory()->staff($memberA)->create();
        $staffB = User::factory()->staff($memberB)->create();

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
            'assigned_to' => $staffB->id,
            'assigned_at' => now(),
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
            ->get(route('laporan.lampiran', $reportB))
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

    public function test_assignment_redirects_to_validated_report_list_return_url(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => null,
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => $staff->id,
                'return_to' => '/laporan?status=Menunggu+Penugasan&q=lampu&page=1&redirect=https%3A%2F%2Fevil.example',
            ])
            ->assertRedirect('/laporan?status=Menunggu+Penugasan&q=lampu&page=1')
            ->assertSessionHas('success');

        $this->assertSame($staff->id, $report->refresh()->assigned_to);
    }

    public function test_assignment_rejects_external_return_url(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => null,
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.penugasan.update', $report), [
                'dprd_member_id' => $member->id,
                'assigned_to' => $staff->id,
                'return_to' => 'https://evil.example/laporan?status=Menunggu+Penugasan',
            ])
            ->assertRedirect(route('laporan.index'))
            ->assertSessionHas('success');

        $this->assertSame($staff->id, $report->refresh()->assigned_to);
    }

    public function test_administrator_can_confirm_and_correct_final_commission_without_overwriting_classification(): void
    {
        $this->seed(\Database\Seeders\KomisiSeeder::class);

        $admin = $this->administrator();
        $commissions = \App\Models\DprdCommission::query()->orderBy('id')->get();
        $first = $commissions[0];
        $second = $commissions[1];
        $third = $commissions[2];

        $report = $this->report([
            'klasifikasi_komisi_id' => $second->id,
            'klasifikasi_skor' => 9,
            'klasifikasi_skor_kedua' => 0,
            'klasifikasi_status' => LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN,
            'klasifikasi_metode' => 'rule_based_weighted_keywords_v1',
            'klasifikasi_alasan' => 'Uji rekomendasi otomatis.',
            'klasifikasi_dianalisis_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.komisi.update', $report), [
                'komisi_final_id' => $third->id,
                'catatan' => 'Dikoreksi setelah rapat koordinasi.',
                'return_to' => '/laporan?status=Menunggu+Penugasan',
            ])
            ->assertRedirect('/laporan?status=Menunggu+Penugasan')
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame($third->id, $report->komisi_final_id);
        $this->assertSame($second->id, $report->klasifikasi_komisi_id);
        $this->assertSame($admin->id, $report->komisi_final_ditetapkan_oleh);
        $this->assertNotNull($report->komisi_final_ditetapkan_at);
        $this->assertSame($third->name, $report->komisi_efektif_label);

        $this->assertDatabaseHas('aktivitas_sistem', [
            'user_id' => $admin->id,
            'event' => 'laporan.komisi',
            'subject_type' => $report->getMorphClass(),
            'subject_id' => $report->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('laporan.komisi.update', $report), [
                'komisi_final_id' => $first->id,
            ])
            ->assertRedirect(route('laporan.index'));

        $report->refresh();

        $this->assertSame($first->id, $report->komisi_final_id);
        $this->assertSame($second->id, $report->klasifikasi_komisi_id);
    }

    public function test_final_commission_requires_an_active_commission(): void
    {
        $admin = $this->administrator();
        $report = $this->report();
        $inactive = \App\Models\DprdCommission::factory()->inactive()->create();

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.komisi.update', $report), [
                'komisi_final_id' => $inactive->id,
            ])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('commission', ['komisi_final_id']);

        $this->assertNull($report->refresh()->komisi_final_id);

        $this->actingAs($admin)
            ->from(route('laporan.show', $report))
            ->patch(route('laporan.komisi.update', $report), [
                'komisi_final_id' => 999999,
            ])
            ->assertRedirect(route('laporan.show', $report))
            ->assertSessionHasErrorsIn('commission', ['komisi_final_id']);

        $this->assertNull($report->refresh()->komisi_final_id);
    }

    public function test_staff_cannot_set_final_commission(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();

        $report = $this->report([
            'dprd_member_id' => $member->id,
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $commission = \App\Models\DprdCommission::factory()->create();

        $this->actingAs($staff)
            ->patch(route('laporan.komisi.update', $report), [
                'komisi_final_id' => $commission->id,
            ])
            ->assertForbidden();

        $this->assertNull($report->refresh()->komisi_final_id);
    }
}
