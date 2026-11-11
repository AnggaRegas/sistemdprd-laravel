<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityDecision;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\User;
use App\Services\AspirationGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SimilarityGroupingTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = 'Jalan Melati No. 12, Kelurahan Bukit Indah';

    private const OTHER_ADDRESS = 'Jalan Mawar No. 5, Kelurahan Bukit Indah';

    private const KECAMATAN = 'Kecamatan Ujung';

    private const OTHER_KECAMATAN = 'Kecamatan Soreang';

    private const PROBLEM = 'Jalan berlubang di depan pasar perlu segera diperbaiki karena membahayakan pengendara dan warga sekitar.';

    private const OTHER_PROBLEM = 'Pengadaan lampu penerangan jalan umum di lingkungan perumahan warga agar tidak gelap pada malam hari.';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aspiration_similarity.location_threshold', 0.70);
        config()->set('aspiration_similarity.problem_threshold', 0.60);
        config()->set('aspiration_similarity.high_threshold', 0.60);
        config()->set('aspiration_similarity.threshold', 0.60);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Superadmin Kemiripan',
        ]);
    }

    private function report(DprdMember $member, array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'ringkasan' => 'Perbaikan jalan berlubang di depan pasar',
            'isi' => self::PROBLEM,
            'pelapor_alamat' => self::ADDRESS,
            'kecamatan' => self::KECAMATAN,
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ], $overrides));
    }

    /**
     * @return Collection<int, LaporanMasyarakat>
     */
    private function similarReports(DprdMember $member, int $count = 3): Collection
    {
        return collect(range(1, $count))->map(function (int $index) use ($member) {
            return $this->report($member, [
                'nomor_tiket' => 'LPR-SIM-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function test_same_location_and_problem_is_flagged_as_high_similarity(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Mirip Uji']);
        $reports = $this->similarReports($member, 2);

        $result = app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->assertSame(2, $result['reports']);
        $this->assertSame(1, $result['pairs']);
        $this->assertSame(1, $result['groups']);
        $this->assertSame(1, LaporanSimilarityGroup::query()->count());

        $latest = $reports->last()->refresh();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, $latest->similarity_level);

        $result = $latest->similarityResults()->firstOrFail();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, $result->similarity_level);
        $this->assertGreaterThanOrEqual(0.70, (float) $result->evidence['location_score']);
        $this->assertGreaterThanOrEqual(0.70, (float) $result->evidence['problem_score']);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Aspirasi Utama')
            ->assertSee('Ada Kemiripan')
            ->assertSee('Bandingkan')
            ->assertSee('Perbandingan Aspirasi')
            ->assertSee('Ditemukan 2 aspirasi serupa')
            ->assertSee('Tingkat kemiripan tertinggi')
            ->assertSee('Aspirasi Baru')
            ->assertSee('Aspirasi yang Mirip')
            ->assertSee('Akan Dijadikan Aspirasi Utama')
            ->assertSee('Simpan sebagai Arsip Terkait')
            ->assertSee('Pilih sebagai Aspirasi Utama')
            ->assertSee('Aspirasi lainnya tetap tersimpan sebagai arsip terkait');
    }

    public function test_same_location_with_different_problem_is_not_flagged(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $this->report($member);
        $this->report($member, [
            'ringkasan' => 'Pengadaan lampu penerangan jalan',
            'isi' => self::OTHER_PROBLEM,
            'nomor_tiket' => 'LPR-SIM-002',
        ]);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->assertSame(0, LaporanSimilarityGroup::query()->count());
        $this->assertSame(0, \App\Models\LaporanSimilarityResult::query()->count());
        $this->assertSame(
            0,
            LaporanMasyarakat::query()->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI)->count()
        );

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertDontSee('Aspirasi Mirip Ditemukan')
            ->assertDontSee('Bandingkan');
    }

    public function test_same_problem_with_different_address_requires_manual_review_and_is_not_auto_grouped(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $this->report($member);
        $this->report($member, [
            'pelapor_alamat' => self::OTHER_ADDRESS,
            'nomor_tiket' => 'LPR-SIM-002',
        ]);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        // Gerbang AND: alamat berbeda tidak membentuk kelompok otomatis.
        $this->assertSame(0, LaporanSimilarityGroup::query()->count());

        // Hasil disimpan untuk peninjauan manual dengan skor cosine murni.
        $this->assertSame(1, \App\Models\LaporanSimilarityResult::query()->count());
        $result = \App\Models\LaporanSimilarityResult::query()->firstOrFail();
        $this->assertEquals(1.0, (float) $result->similarity_score);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU, $result->similarity_level);
        $this->assertTrue($result->evidence['manual_review_required'] ?? false);

        // Tidak diberi label kemiripan tinggi otomatis pada tabel admin.
        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertDontSee('Ada Kemiripan');
    }

    public function test_different_location_and_problem_is_not_flagged(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $this->report($member);
        $this->report($member, [
            'ringkasan' => 'Pengadaan lampu penerangan jalan',
            'isi' => self::OTHER_PROBLEM,
            'pelapor_alamat' => self::OTHER_ADDRESS,
            'kecamatan' => self::OTHER_KECAMATAN,
            'nomor_tiket' => 'LPR-SIM-002',
        ]);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->assertSame(0, LaporanSimilarityGroup::query()->count());
        $this->assertSame(0, \App\Models\LaporanSimilarityResult::query()->count());
    }

    public function test_missing_location_information_requires_manual_review_and_is_not_auto_grouped(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $this->report($member);
        $this->report($member, [
            'pelapor_alamat' => '-',
            'nomor_tiket' => 'LPR-SIM-002',
        ]);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        // Alamat tidak lengkap tidak boleh otomatis dianggap mirip tinggi.
        $this->assertSame(0, LaporanSimilarityGroup::query()->count());

        $this->assertSame(1, \App\Models\LaporanSimilarityResult::query()->count());
        $result = \App\Models\LaporanSimilarityResult::query()->firstOrFail();
        $this->assertEquals(1.0, (float) $result->similarity_score);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU, $result->similarity_level);
        $this->assertSame('insufficient', $result->evidence['location_status'] ?? null);
        $this->assertTrue($result->evidence['manual_review_required'] ?? false);
    }

    public function test_location_words_inside_short_problem_require_both_thresholds(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $this->report($member, [
            'nomor_tiket' => 'LPR-SIM-001',
            'ringkasan' => 'jalanan rusak di petta oddo',
            'isi' => 'jalanan rusak di petta oddo',
            'pelapor_alamat' => 'jl petta oddo soreang',
            'kecamatan' => self::KECAMATAN,
        ]);

        $this->report($member, [
            'nomor_tiket' => 'LPR-SIM-002',
            'ringkasan' => 'jalanan rusak parah di petta oddo',
            'isi' => 'jalanan rusak parah di petta oddo',
            'pelapor_alamat' => 'jl petta oddo',
            'kecamatan' => self::KECAMATAN,
        ]);

        $result = app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->assertSame(1, $result['pairs']);

        $pair = \App\Models\LaporanSimilarityResult::query()->firstOrFail();
        $this->assertGreaterThanOrEqual(0.70, (float) $pair->evidence['location_score']);
        $this->assertGreaterThanOrEqual(0.60, (float) $pair->evidence['problem_score']);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, $pair->similarity_level);
    }

    public function test_user_example_reports_detected_with_accurate_similarity_percentage(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $r1 = $this->report($member, [
            'nomor_tiket' => 'LPR-USR-001',
            'pelapor_nama' => 'Pelapor Satu',
            'pelapor_alamat' => 'Jl. Mappangara No. 1',
            'kecamatan' => 'Bacukiki Barat',
            'ringkasan' => 'jalan rusak di dekat rumah saya tepatnya di jalan mappangara',
            'isi' => 'jalan rusak di dekat rumah saya tepatnya di jalan mappangara',
        ]);

        $r2 = $this->report($member, [
            'nomor_tiket' => 'LPR-USR-002',
            'pelapor_nama' => 'Pelapor Dua',
            'pelapor_alamat' => 'Jl. Mappangara No. 5',
            'kecamatan' => 'Bacukiki Barat',
            'ringkasan' => 'jalan rusak di dekat rumah saya tepatnya di jalan mappangara',
            'isi' => 'jalan rusak di dekat rumah saya tepatnya di jalan mappangara',
        ]);

        $r3 = $this->report($member, [
            'nomor_tiket' => 'LPR-USR-003',
            'pelapor_nama' => 'Pelapor Tiga',
            'pelapor_alamat' => 'Jl. Mappangara No. 12',
            'kecamatan' => 'Bacukiki Barat',
            'ringkasan' => 'jalan rusak bro di mappangara',
            'isi' => 'jalan rusak bro di mappangara',
        ]);

        $result = app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->assertSame(3, $result['reports']);
        $this->assertSame(3, $result['pairs']);
        $this->assertSame(1, $result['groups']);

        // Verifikasi sepasang identik (R1 dan R2) menghasilkan kemiripan 100%
        $pair12 = \App\Models\LaporanSimilarityResult::query()
            ->where(function ($q) use ($r1, $r2) {
                $q->where('laporan_masyarakat_id', $r2->id)
                    ->where('compared_laporan_masyarakat_id', $r1->id);
            })->firstOrFail();

        $this->assertEquals(1.0, (float) $pair12->similarity_score);

        // Verifikasi perbandingan R3 dengan R1/R2 terdeteksi dengan skor kemiripan algoritma
        $pair13 = \App\Models\LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $r3->id)
            ->where('compared_laporan_masyarakat_id', $r1->id)
            ->firstOrFail();

        $this->assertGreaterThanOrEqual(0.60, (float) $pair13->similarity_score);

        // Verifikasi pada halaman Laporan Masyarakat admin: badge "Ada Kemiripan"
        // menggantikan persentase kemiripan pada kolom Kemiripan.
        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Ada Kemiripan')
            ->assertDontSee('<strong>100%</strong>', false)
            ->assertDontSee('similarity-dot')
            ->assertDontSee('class="cell-muted">—</span>', false);
    }

    public function test_legacy_result_without_dual_score_evidence_is_not_flagged(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $current = $this->report($member, ['nomor_tiket' => 'LPR-SIM-001']);
        $previous = $this->report($member, ['nomor_tiket' => 'LPR-SIM-002']);

        \App\Models\LaporanSimilarityResult::query()->create([
            'laporan_masyarakat_id' => $current->id,
            'compared_laporan_masyarakat_id' => $previous->id,
            'similarity_score' => 0.95,
            'similarity_level' => null,
            'evidence' => null,
            'checked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertDontSee('Aspirasi Mirip Ditemukan')
            ->assertDontSee('Bandingkan')
            ->assertDontSee('Ada Kemiripan')
            ->assertSee('class="cell-muted">—</span>', false);
    }

    public function test_reports_for_different_members_are_not_compared(): void
    {
        $admin = $this->administrator();
        $firstMember = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Pertama']);
        $secondMember = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Kedua']);

        $this->report($firstMember);
        $this->report($secondMember, ['nomor_tiket' => 'LPR-SIM-002']);

        app(AspirationGroupingService::class)->regroupMember($firstMember, $admin);
        app(AspirationGroupingService::class)->regroupMember($secondMember, $admin);

        $this->assertSame(0, \App\Models\LaporanSimilarityResult::query()->count());
        $this->assertSame(0, LaporanSimilarityGroup::query()->count());
    }

    public function test_multiple_candidates_are_shown_in_one_modal(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Aspirasi Utama')
            ->assertSee('Bandingkan')
            ->assertSee('Ada Kemiripan')
            ->assertSee('Ditemukan 3 aspirasi serupa')
            ->assertSee('LPR-SIM-001')
            ->assertSee('LPR-SIM-002')
            ->assertSee('LPR-SIM-003')
            ->assertSee('id="similarityModal'.$reports->last()->id.'"', false);
    }

    public function test_super_admin_can_choose_main_aspiration_from_modal(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->get(1);
        $current = $reports->last();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $main->id,
                'catatan' => 'Aspirasi ini paling lengkap.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $main->refresh();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $main->similarity_status);
        $this->assertNull($main->duplicate_of_id);

        foreach ($reports as $report) {
            $report->refresh();

            if ($report->id === $main->id) {
                continue;
            }

            $this->assertSame($main->id, $report->duplicate_of_id);
            $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN, $report->similarity_status);
            $this->assertDatabaseHas('laporan_masyarakat', ['id' => $report->id]);
        }

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($main->id, $group->main_laporan_id);
        $this->assertSame(LaporanSimilarityGroup::STATUS_DIPUTUSKAN, $group->status);
        $this->assertNotNull($group->decided_at);

        $this->assertSame(
            $reports->count(),
            LaporanSimilarityDecision::query()->where('laporan_similarity_group_id', $group->id)->count()
        );

        $decision = LaporanSimilarityDecision::query()
            ->where('action', LaporanSimilarityDecision::ACTION_PILIH_UTAMA)
            ->where('laporan_masyarakat_id', $current->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $decision->user_id);
        $this->assertSame($main->id, $decision->payload['main_laporan_id']);
        $this->assertNotNull($decision->payload['similarity_scores']['location']);
        $this->assertNotNull($decision->payload['similarity_scores']['problem']);

        // Duplikat tidak masuk antrean penugasan baru.
        $this->assertSame(1, LaporanMasyarakat::badgeCountFor($admin));
    }

    public function test_modal_displays_real_highest_similarity_and_action_controls(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $current = $reports->last();
        $pair = $current->similarityResults()->firstOrFail();
        $score = (float) $pair->similarity_score;

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('id="similarityModal'.$current->id.'"', false)
            ->assertSee('Perbandingan Aspirasi')
            ->assertSee('Ditemukan 2 aspirasi serupa')
            ->assertSee('Tingkat kemiripan tertinggi')
            ->assertSee(number_format((int) round($score * 100), 0, ',', '.').'%')
            ->assertSee(number_format($score * 100, 1, ',', '.').'%')
            ->assertSee('name="main_laporan_id"', false)
            ->assertSee('value="'.$current->id.'"', false)
            ->assertSee('value="'.$reports->first()->id.'"', false)
            ->assertSee('data-sim-action="arsip"', false)
            ->assertSee('data-sim-action="utama"', false)
            ->assertSee('disabled', false);
    }

    public function test_super_admin_can_archive_new_report_as_related_to_old_main(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->first();
        $current = $reports->last();

        $current->forceFill([
            'lampiran_path' => 'lampiran/aspirasi/contoh.pdf',
            'lampiran_nama' => 'contoh.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 2048,
        ])->save();

        $original = $current->only([
            'ringkasan',
            'isi',
            'pelapor_nama',
            'pelapor_alamat',
            'lampiran_path',
            'lampiran_nama',
            'lampiran_mime',
            'lampiran_size',
        ]);
        $originalDate = $current->tanggal_laporan?->toDateString();

        $response = $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
                'main_laporan_id' => $main->id,
                'catatan' => 'Laporan baru adalah duplikat dari aspirasi lama.',
            ]);

        $response->assertRedirectContains(route('laporan.show', $main));
        $response->assertSessionHas('success');

        $main->refresh();
        $current->refresh();

        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $main->similarity_status);
        $this->assertNull($main->duplicate_of_id);

        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN, $current->similarity_status);
        $this->assertSame($main->id, $current->duplicate_of_id);
        $this->assertSame($admin->id, $current->similarity_reviewed_by);
        $this->assertNotNull($current->similarity_reviewed_at);
        $this->assertSame($original, $current->only(array_keys($original)));
        $this->assertSame($originalDate, $current->tanggal_laporan?->toDateString());

        $this->assertDatabaseHas('laporan_masyarakat', array_merge(
            ['id' => $current->id],
            $original
        ));

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($main->id, $group->main_laporan_id);
        $this->assertSame(LaporanSimilarityGroup::STATUS_DIPUTUSKAN, $group->status);
        $this->assertSame($admin->id, $group->decided_by);
        $this->assertNotNull($group->decided_at);

        $this->assertSame(
            2,
            LaporanSimilarityDecision::query()
                ->where('laporan_similarity_group_id', $group->id)
                ->where('action', LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT)
                ->count()
        );

        $this->assertDatabaseHas('laporan_similarity_group_members', [
            'laporan_similarity_group_id' => $group->id,
            'laporan_masyarakat_id' => $main->id,
            'peran' => LaporanSimilarityGroupMember::PERAN_UTAMA,
        ]);
        $this->assertDatabaseHas('laporan_similarity_group_members', [
            'laporan_similarity_group_id' => $group->id,
            'laporan_masyarakat_id' => $current->id,
            'peran' => LaporanSimilarityGroupMember::PERAN_DUPLIKAT,
        ]);
    }

    public function test_archive_as_related_rejects_current_report_as_main(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $current = $reports->last();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
                'main_laporan_id' => $current->id,
                'decided_report_id' => $current->id,
            ])
            ->assertSessionHasErrorsIn('similarity', ['main_laporan_id']);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Pilih salah satu aspirasi lama sebagai aspirasi utama untuk arsip terkait.')
            ->assertSee('name="decided_report_id"', false)
            ->assertSee('var errorReportId = '.$current->id.';', false);

        $current->refresh();
        $this->assertNull($current->duplicate_of_id);
        $this->assertNotSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $current->similarity_status);

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($reports->first()->id, $group->main_laporan_id);
        $this->assertNull($group->decided_at);
    }

    public function test_current_report_can_be_selected_as_main_aspiration(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $current = $reports->last();

        $response = $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $current->id,
                'catatan' => 'Aspirasi baru paling lengkap.',
            ]);

        $response->assertRedirectContains(route('laporan.show', $current));
        $response->assertSessionHas('success');

        $current->refresh();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $current->similarity_status);
        $this->assertNull($current->duplicate_of_id);

        foreach ($reports as $report) {
            $report->refresh();

            if ($report->id === $current->id) {
                continue;
            }

            $this->assertSame($current->id, $report->duplicate_of_id);
            $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN, $report->similarity_status);
            $this->assertDatabaseHas('laporan_masyarakat', ['id' => $report->id]);
        }

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($current->id, $group->main_laporan_id);
        $this->assertSame(LaporanSimilarityGroup::STATUS_DIPUTUSKAN, $group->status);

        $this->assertSame(
            $reports->count(),
            LaporanSimilarityDecision::query()
                ->where('laporan_similarity_group_id', $group->id)
                ->where('action', LaporanSimilarityDecision::ACTION_PILIH_UTAMA)
                ->count()
        );
    }

    public function test_archive_as_related_cannot_replace_an_existing_main(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->first();
        $alternative = $reports->get(1);
        $current = $reports->last();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $main->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
                'main_laporan_id' => $alternative->id,
            ])
            ->assertSessionHasErrorsIn('similarity', ['main_laporan_id']);

        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $main->refresh()->similarity_status);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN, $alternative->refresh()->similarity_status);
        $this->assertSame($main->id, $alternative->duplicate_of_id);
        $this->assertSame($main->id, $current->refresh()->duplicate_of_id);

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($main->id, $group->main_laporan_id);
    }

    public function test_main_aspiration_can_continue_to_existing_approval_flow(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Pendamping Uji']);
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->last();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $main), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $main->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('laporan.persetujuan', $main), [])
            ->assertRedirect()
            ->assertSessionHas('success');

        $main->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_DITUGASKAN, $main->status);
        $this->assertSame($staff->id, $main->assigned_to);

        $duplicate = $reports->first()->refresh();
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $duplicate->status);
        $this->assertNull($duplicate->assigned_to);
    }

    public function test_super_admin_can_separate_incorrectly_flagged_reports(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $current = $reports->last();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $current), [
                'action' => LaporanSimilarityDecision::ACTION_TETAP_TERPISAH,
                'selected_ids' => $reports->pluck('id')->all(),
                'catatan' => 'Lokasi kejadian sebenarnya berbeda.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($reports as $report) {
            $report->refresh();
            $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH, $report->similarity_status);
            $this->assertNull($report->duplicate_of_id);
            $this->assertDatabaseHas('laporan_masyarakat', ['id' => $report->id]);
        }

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame(LaporanSimilarityGroup::STATUS_DIBUBARKAN, $group->status);
        $this->assertSame(
            $reports->count(),
            LaporanSimilarityDecision::query()
                ->where('action', LaporanSimilarityDecision::ACTION_TETAP_TERPISAH)
                ->count()
        );
    }

    public function test_single_duplicate_can_be_unlinked_without_deleting_report(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->first();
        $duplicate = $reports->last();

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $main), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $main->id,
        ])->assertRedirect();

        $this->assertSame($main->id, $duplicate->refresh()->duplicate_of_id);

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $duplicate), [
                'action' => LaporanSimilarityDecision::ACTION_TETAP_TERPISAH,
                'selected_ids' => [$duplicate->id],
            ])
            ->assertRedirect();

        $duplicate->refresh();
        $this->assertNull($duplicate->duplicate_of_id);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH, $duplicate->similarity_status);
        $this->assertDatabaseHas('laporan_masyarakat', ['id' => $duplicate->id]);
    }

    public function test_recheck_preserves_decided_group_and_history(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $group = LaporanSimilarityGroup::query()->firstOrFail();

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $reports->last()), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $reports->first()->id,
        ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.recheck', $member))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('laporan_similarity_groups', [
            'id' => $group->id,
            'main_laporan_id' => $reports->first()->id,
        ]);
        $this->assertSame(
            $reports->count(),
            LaporanSimilarityDecision::query()->where('laporan_similarity_group_id', $group->id)->count()
        );
        $this->assertSame(3, $group->members()->count());
        $this->assertSame(1, LaporanSimilarityGroup::query()->where('dprd_member_id', $member->id)->count());
    }

    public function test_staff_cannot_manage_similarity_decisions(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $this->administrator());

        $this->actingAs($staff)
            ->post(route('laporan.kemiripan.decide', $reports->last()), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $reports->first()->id,
            ])
            ->assertForbidden();
    }
}
