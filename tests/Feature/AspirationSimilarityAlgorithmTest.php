<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use App\Services\AspirationGroupingService;
use App\Services\AspirationSimilarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AspirationSimilarityAlgorithmTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = 'Jalan Melati No. 12, Kelurahan Bukit Indah';

    private const OTHER_ADDRESS = 'Jalan Mawar No. 5, Kelurahan Bukit Indah';

    private const KECAMATAN = 'Kecamatan Ujung';

    private const OTHER_KECAMATAN = 'Kecamatan Soreang';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aspiration_similarity.location_threshold', 0.70);
        config()->set('aspiration_similarity.problem_threshold', 0.60);
        config()->set('aspiration_similarity.high_threshold', 0.60);
        config()->set('aspiration_similarity.threshold', 0.60);
    }

    private function member(): DprdMember
    {
        return DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota DPRD Uji Algoritma',
        ]);
    }

    private function createReport(DprdMember $member, array $attributes = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'dprd_member_id' => $member->id,
            'kecamatan' => self::KECAMATAN,
            'pelapor_alamat' => self::ADDRESS,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ], $attributes));
    }

    /**
     * Skenario 1: Dua aspirasi identik -> skor 1.0 (100%).
     */
    public function test_scenario_1_identical_aspirations_yield_exact_100_percent_similarity(): void
    {
        $member = $this->member();
        $text = 'Jalan berlubang parah di depan pasar tradisional membahayakan pengendara motor setiap malam.';

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-IDEN-001',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-IDEN-002',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $service = app(AspirationSimilarityService::class);
        $analysis = $service->analyze($r2);

        $this->assertTrue($analysis['is_duplicate']);
        $this->assertNotEmpty($analysis['matches']);

        $match = $analysis['matches'][0];
        $this->assertSame((int) $r1->id, (int) $match['id']);
        $this->assertEquals(1.0, (float) $match['problem_score']);
        $this->assertEquals(1.0, (float) $match['similarity']);
        $this->assertEquals(1.0, (float) $match['location_score']);

        // Uji penyimpanan
        $service->analyzeAndStore($r2);
        $stored = LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $r2->id)
            ->where('compared_laporan_masyarakat_id', $r1->id)
            ->firstOrFail();

        $this->assertEquals(1.0, (float) $stored->similarity_score);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, $stored->similarity_level);
    }

    /**
     * Skenario 2: Dua aspirasi dengan inti masalah serupa tetapi variasi kata -> skor tinggi di atas ambang.
     */
    public function test_scenario_2_similar_core_problem_with_word_variations_yields_score_above_threshold(): void
    {
        $member = $this->member();

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-VAR-001',
            'ringkasan' => 'jalan rusak di dekat rumah saya di Jalan Mappangara',
            'isi' => 'jalan rusak di dekat rumah saya di Jalan Mappangara',
            'pelapor_alamat' => 'Jl. Mappangara No. 12',
            'kecamatan' => 'Bacukiki Barat',
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-VAR-002',
            'ringkasan' => 'jalan rusak parah di Mappangara',
            'isi' => 'jalan rusak parah di Mappangara',
            'pelapor_alamat' => 'Jl. Mappangara No. 15',
            'kecamatan' => 'Bacukiki Barat',
        ]);

        $service = app(AspirationSimilarityService::class);
        $analysis = $service->analyze($r2);

        $this->assertTrue($analysis['is_duplicate']);
        $match = $analysis['matches'][0];
        $this->assertGreaterThanOrEqual(0.60, (float) $match['problem_score']);
        $this->assertGreaterThanOrEqual(0.70, (float) $match['location_score']);
        $this->assertSame('ok', $match['location_status']);
    }

    /**
     * Skenario 3: Dua aspirasi dengan masalah berbeda -> skor rendah di bawah ambang.
     */
    public function test_scenario_3_different_problems_yield_score_below_threshold(): void
    {
        $member = $this->member();

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-DIFF-001',
            'ringkasan' => 'Jalan berlubang aspal terkelupas membahayakan pengendara',
            'isi' => 'Jalan berlubang aspal terkelupas membahayakan pengendara',
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-DIFF-002',
            'ringkasan' => 'Bantuan beasiswa siswa miskin berprestasi jenjang sekolah menengah',
            'isi' => 'Bantuan beasiswa siswa miskin berprestasi jenjang sekolah menengah',
        ]);

        $service = app(AspirationSimilarityService::class);
        $analysis = $service->analyze($r2);

        $this->assertFalse($analysis['is_duplicate']);
        $this->assertEmpty($analysis['matches']);
        $this->assertEmpty($analysis['manual_review']);

        $candidate = $analysis['snapshot']['cosine']['candidates'][0] ?? null;
        $this->assertNotNull($candidate);
        $this->assertLessThan(0.60, (float) $candidate['problem_score']);
    }

    /**
     * Skenario 4: Dua aspirasi dengan kecamatan sama tetapi masalah berbeda -> tidak terdeteksi mirip.
     */
    public function test_scenario_4_same_kecamatan_with_different_problems_not_flagged(): void
    {
        $member = $this->member();
        $admin = User::factory()->administrator()->create();

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-KEC-001',
            'kecamatan' => 'Kecamatan Ujung',
            'pelapor_alamat' => 'Jalan Melati No. 10',
            'ringkasan' => 'Jalan rusak parah dan berlubang membahayakan warga',
            'isi' => 'Jalan rusak parah dan berlubang membahayakan warga',
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-KEC-002',
            'kecamatan' => 'Kecamatan Ujung',
            'pelapor_alamat' => 'Jalan Melati No. 10',
            'ringkasan' => 'Permohonan bantuan bibit jagung dan pupuk subsidi kelompok tani',
            'isi' => 'Permohonan bantuan bibit jagung dan pupuk subsidi kelompok tani',
        ]);

        $result = app(AspirationGroupingService::class)->regroupMember($member, $admin);

        // Gerbang AND: masalah tidak mencapai ambang sehingga tidak terbentuk kelompok.
        $this->assertSame(0, $result['pairs']);
        $this->assertSame(0, $result['groups']);
        $this->assertSame(0, LaporanSimilarityGroup::query()->count());
    }

    /**
     * Skenario 5: Dua aspirasi dengan negasi -> kata negasi dipertahankan dan skor berbeda signifikan.
     */
    public function test_scenario_5_negation_words_preserved_and_not_confused_with_affirmative(): void
    {
        $member = $this->member();

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-NEG-001',
            'ringkasan' => 'Jalan berlubang dan rusak',
            'isi' => 'Jalan berlubang dan rusak',
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-NEG-002',
            'ringkasan' => 'Jalan tidak berlubang dan tidak rusak',
            'isi' => 'Jalan tidak berlubang dan tidak rusak',
        ]);

        $service = app(AspirationSimilarityService::class);
        $analysis = $service->analyze($r2);

        // Pastikan kata negasi 'tidak' dipertahankan dalam token hasil preprocessing.
        $preprocessing = $analysis['snapshot']['preprocessing'];
        $this->assertContains('tidak', $preprocessing['final_tokens']);
        $this->assertContains('tidak', $preprocessing['negation_words_preserved']);

        // Skor kedua aspirasi tidak boleh 1.0 (tidak identik karena ada negasi).
        $candidate = $analysis['snapshot']['cosine']['candidates'][0] ?? null;
        $this->assertNotNull($candidate);
        $this->assertLessThan(1.0, (float) $candidate['problem_score']);
    }

    /**
     * Skenario 6: Dua aspirasi dari anggota DPRD berbeda -> tidak dibandingkan / tidak menghasilkan kesamaan.
     */
    public function test_scenario_6_reports_for_different_members_are_never_compared(): void
    {
        $memberA = $this->member();
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota DPRD Lain']);

        $text = 'Jalan berlubang di depan balai warga perlu segera diperbaiki.';

        $r1 = $this->createReport($memberA, [
            'nomor_tiket' => 'LPR-MEM-001',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $r2 = $this->createReport($memberB, [
            'nomor_tiket' => 'LPR-MEM-002',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $service = app(AspirationSimilarityService::class);

        // Analisis terhadap r2 (milik memberB) tidak boleh menemukan r1 (milik memberA) sebagai kandidat.
        $analysis = $service->analyze($r2);
        $this->assertSame(0, $analysis['candidate_count']);
        $this->assertEmpty($analysis['matches']);

        // Corpus campuran dipisahkan per anggota.
        $corpusAnalysis = $service->analyzeCorpus(collect([$r1, $r2]));
        // Karena berbeda anggota, korpus r1 hanya memproses r1 sendiri (count < 2).
        $this->assertSame(0, count($corpusAnalysis['pairs']));
    }

    /**
     * Skenario 7: Aspirasi dengan data alamat tidak lengkap/tidak valid -> memerlukan peninjauan manual dan tidak otomatis mirip tinggi.
     */
    public function test_scenario_7_incomplete_address_requires_manual_review_and_not_auto_flagged(): void
    {
        $member = $this->member();
        $admin = User::factory()->administrator()->create();
        $text = 'Jalan rusak parah dan aspal mengelupas membahayakan warga sekitar.';

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-INC-001',
            'pelapor_alamat' => self::ADDRESS,
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-INC-002',
            'pelapor_alamat' => '-', // Alamat tidak lengkap
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $service = app(AspirationSimilarityService::class);
        $analysis = $service->analyze($r2);

        // Tidak boleh lulus otomatis sebagai duplikat kemiripan tinggi.
        $this->assertFalse($analysis['is_duplicate']);
        $this->assertEmpty($analysis['matches']);

        // Tetapi harus masuk daftar manual_review.
        $this->assertNotEmpty($analysis['manual_review']);
        $review = $analysis['manual_review'][0];
        $this->assertSame('insufficient', $review['location_status']);
        $this->assertTrue($review['manual_review_required']);

        // Pemeriksaan grouping: tidak terbentuk kelompok otomatis.
        $groupResult = app(AspirationGroupingService::class)->regroupMember($member, $admin);
        $this->assertSame(0, $groupResult['groups']);
        $this->assertSame(0, LaporanSimilarityGroup::query()->count());

        // Baris tersimpan berlabel perlu_ditinjau.
        $stored = LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $r2->id)
            ->firstOrFail();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU, $stored->similarity_level);
    }

    /**
     * Skenario 8: Konsistensi antara analyze() dan analyzeCorpus() untuk pasangan yang sama.
     */
    public function test_scenario_8_consistency_between_analyze_and_analyze_corpus(): void
    {
        $member = $this->member();
        $text = 'Lampu jalan padam total di pertigaan jalan mawar membuat rawan kejahatan.';

        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-CNS-001',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-CNS-002',
            'ringkasan' => $text,
            'isi' => $text,
        ]);

        $service = app(AspirationSimilarityService::class);

        $singleAnalysis = $service->analyze($r2);
        $corpusAnalysis = $service->analyzeCorpus(collect([$r1, $r2]));

        $singleMatch = $singleAnalysis['matches'][0] ?? null;
        $this->assertNotNull($singleMatch);

        $corpusPair = $corpusAnalysis['pairs'][0] ?? null;
        $this->assertNotNull($corpusPair);

        // Skor masalah (cosine TF-IDF) harus konsisten antara kedua metode.
        $this->assertEqualsWithDelta(
            (float) $singleMatch['problem_score'],
            (float) $corpusPair['problem_score'],
            0.0001
        );

        // Skor lokasi harus konsisten.
        $this->assertEqualsWithDelta(
            (float) $singleMatch['location_score'],
            (float) $corpusPair['location_score'],
            0.0001
        );

        // Skor kemiripan yang disimpan adalah skor masalah (cosine TF-IDF).
        $this->assertEqualsWithDelta(
            (float) $singleMatch['similarity'],
            (float) $corpusPair['score'],
            0.0001
        );
    }

    /**
     * Skenario 9: Perilaku analisis ulang ketika laporan diperbarui (diedit).
     */
    public function test_scenario_9_reanalysis_when_report_is_updated_compares_all_relevant_reports(): void
    {
        $member = $this->member();
        $service = app(AspirationSimilarityService::class);

        $textLama = 'Permohonan bantuan bibit ikan lele.';
        $textBaru = 'Jalan berlubang parah di depan pasar tradisional membahayakan warga.';

        // Laporan pertama (id lebih kecil) awalnya bertopik ikan.
        $r1 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-UPD-001',
            'ringkasan' => $textLama,
            'isi' => $textLama,
        ]);

        // Laporan kedua (id lebih besar) bertopik jalan rusak.
        $r2 = $this->createReport($member, [
            'nomor_tiket' => 'LPR-UPD-002',
            'ringkasan' => $textBaru,
            'isi' => $textBaru,
        ]);

        $service->analyzeAndStore($r2);

        // Awalnya tidak ada pasangan mirip.
        $this->assertSame(0, LaporanSimilarityResult::query()->count());

        // Laporan pertama diedit menjadi topik jalan rusak (menyamai r2).
        $r1->update([
            'ringkasan' => $textBaru,
            'isi' => $textBaru,
        ]);

        // Jalankan analisis ulang pada r1.
        $service->analyzeAndStore($r1);

        // Analisis ulang harus membandingkan seluruh laporan relevan (termasuk r2 yang id-nya lebih besar).
        $pair = LaporanSimilarityResult::query()
            ->where(function ($q) use ($r1, $r2) {
                $q->where('laporan_masyarakat_id', $r2->id)
                    ->where('compared_laporan_masyarakat_id', $r1->id);
            })
            ->orWhere(function ($q) use ($r1, $r2) {
                $q->where('laporan_masyarakat_id', $r1->id)
                    ->where('compared_laporan_masyarakat_id', $r2->id);
            })
            ->firstOrFail();

        $this->assertEquals(1.0, (float) $pair->similarity_score);
        $this->assertSame(LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, $pair->similarity_level);

        $run = LaporanAnalysisRun::query()->where('laporan_masyarakat_id', $r1->id)->firstOrFail();
        $this->assertTrue($run->is_duplicate);
        $this->assertEquals(1.0, (float) $run->highest_similarity);
    }
}
