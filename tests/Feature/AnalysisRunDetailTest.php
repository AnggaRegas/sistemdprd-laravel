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
            'pelapor_alamat' => 'Jalan Melati No. 12, Kelurahan Bukit Indah',
            'kecamatan' => 'Kecamatan Ujung',
            'dprd_member_id' => $member->id,
        ]);

        $report = LaporanMasyarakat::factory()->create([
            'nomor_tiket' => 'LPR-ANALISIS-001',
            'ringkasan' => 'Aspirasi baru tentang jalan berlubang',
            'isi' => $text,
            'pelapor_alamat' => 'Jalan Melati No. 12, Kelurahan Bukit Indah',
            'kecamatan' => 'Kecamatan Ujung',
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
            ->assertSee('Ringkasan Kemiripan')
            ->assertSee('Aspirasi Pembanding')
            ->assertSee('Kemiripan tertinggi')
            ->assertSee('Aspirasi terdahulu tentang jalan berlubang')
            ->assertSee(number_format((float) $run->highest_similarity * 100, 1, ',', '.').' %')
            ->assertSee('Cosine Similarity')
            ->assertSee('Skor Masalah')
            ->assertSee('Skor Lokasi')
            ->assertSee('Kemiripan Tinggi')
            ->assertDontSee('TF-IDF')
            ->assertDontSee('Detail Perhitungan');
    }

    public function test_report_detail_does_not_show_analysis_button_when_no_similarity(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $report = LaporanMasyarakat::factory()->create([
            'dprd_member_id' => $member->id,
            'ringkasan' => 'Aspirasi mandiri tanpa kemiripan',
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertDontSee('Lihat Analisis Kemiripan')
            ->assertDontSee(route('laporan.analisis', $report), false);
    }

    public function test_report_detail_shows_analysis_button_for_superadmin_when_similarity_exists(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Uji']);
        $report = $this->analyzedReport($member);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Lihat Analisis Kemiripan')
            ->assertSee(route('laporan.analisis', $report), false);
    }

    public function test_staff_cannot_see_analysis_button_even_when_similarity_exists(): void
    {
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Staf']);
        $staff = User::factory()->staff($member)->create();
        $report = $this->analyzedReport($member);
        $report->update([
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DITUGASKAN,
        ]);

        $this->actingAs($staff)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertDontSee('Lihat Analisis Kemiripan')
            ->assertDontSee(route('laporan.analisis', $report), false);
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
            ->assertSee('Ringkasan Kemiripan')
            ->assertSee('90,0 %')
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
