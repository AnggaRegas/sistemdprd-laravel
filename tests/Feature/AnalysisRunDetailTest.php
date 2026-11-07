<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use App\Services\AspirationSimilarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisRunDetailTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Superadmin Analisis',
        ]);
    }

    private function analyzedReport(DprdMember $member, string $text = 'Jalan berlubang di depan pasar perlu segera diperbaiki karena membahayakan pengendara.'): LaporanMasyarakat
    {
        LaporanMasyarakat::factory()->create([
            'ringkasan' => 'Aspirasi terdahulu tentang jalan berlubang',
            'isi' => $text,
            'dprd_member_id' => $member->id,
        ]);

        $report = LaporanMasyarakat::factory()->create([
            'nomor_tiket' => 'LPR-ANALISIS-001',
            'ringkasan' => 'Aspirasi baru tentang jalan berlubang',
            'isi' => $text,
            'dprd_member_id' => $member->id,
        ]);

        app(AspirationSimilarityService::class)->analyzeAndStore($report);

        return $report;
    }

    public function test_administrator_can_open_detail_and_see_stored_snapshot(): void
    {
        config()->set('aspiration_similarity.threshold', 0.80);

        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Analisis']);
        $report = $this->analyzedReport($member);

        $run = LaporanAnalysisRun::query()
            ->where('laporan_masyarakat_id', $report->id)
            ->firstOrFail();

        $this->assertTrue($run->has_detail);
        $this->assertSame(AspirationSimilarityService::ALGORITHM_VERSION, $run->algorithm_version);
        $this->assertNotNull($run->snapshot);

        $this->actingAs($admin)
            ->get(route('laporan.analisis', $report))
            ->assertOk()
            ->assertSee('Detail Perhitungan')
            ->assertSee('Pra-pemrosesan Teks')
            ->assertSee('TF-IDF Dokumen Baru')
            ->assertSee('Cosine Similarity')
            ->assertSee('Aspirasi terdahulu tentang jalan berlubang')
            ->assertSee('Snapshot tersimpan')
            ->assertSee(AspirationSimilarityService::ALGORITHM_VERSION);
    }

    public function test_report_detail_links_to_analysis_page_for_administrator(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $report = LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $member->id,
            'ringkasan' => 'Aspirasi dengan tautan analisis',
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Lihat Rincian Analisis')
            ->assertSee(route('laporan.analisis', $report), false);
    }

    public function test_staff_cannot_open_analysis_page(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $report = LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $member->id,
        ]);

        $this->actingAs($staff)
            ->get(route('laporan.analisis', $report))
            ->assertForbidden();
    }

    public function test_legacy_run_without_snapshot_is_shown_honestly(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $report = LaporanMasyarakat::factory()->create([
            'nomor_tiket' => 'LPR-LEGACY-001',
            'dprd_member_id' => $member->id,
        ]);

        LaporanAnalysisRun::query()->create([
            'laporan_masyarakat_id' => $report->id,
            'dprd_member_id' => $member->id,
            'threshold' => null,
            'max_results' => null,
            'candidate_count' => 1,
            'highest_similarity' => 0.9,
            'is_duplicate' => true,
            'has_detail' => false,
            'analyzed_at' => now(),
            'algorithm_version' => 'legacy',
            'snapshot' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.analisis', $report))
            ->assertOk()
            ->assertSee('Ringkasan legacy')
            ->assertSee('Tanpa detail')
            ->assertDontSee('TF-IDF Dokumen Baru')
            ->assertDontSee('Pra-pemrosesan Teks');
    }

    public function test_report_without_analysis_run_shows_empty_state(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $report = LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $member->id,
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.analisis', $report))
            ->assertOk()
            ->assertSee('belum memiliki riwayat analisis');
    }
}
