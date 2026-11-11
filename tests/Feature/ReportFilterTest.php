<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\KamusAcuanVersi;
use App\Models\LaporanKamusHasil;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Admin Filter Test',
        ]);
    }

    private function createMember(string $name): DprdMember
    {
        return DprdMember::factory()->create([
            'nama_lengkap' => $name,
            'is_active' => true,
        ]);
    }

    private function createReport(DprdMember $member, array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_BARU,
        ], $overrides));
    }

    public function test_view_all_aspirations_button_shows_all_reports_and_resets_member_filter(): void
    {
        $admin = $this->administrator();
        $memberA = $this->createMember('Achmad Ariady');
        $memberB = $this->createMember('Bambang Hermanto');

        $reportA = $this->createReport($memberA, ['ringkasan' => 'Aspirasi Warga Alpha']);
        $reportB = $this->createReport($memberB, ['ringkasan' => 'Aspirasi Warga Beta']);

        // Ketika tombol 'Lihat Semua Aspirasi Masyarakat' dibuka (anggota=semua)
        $response = $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => 'semua']));

        $response->assertOk();
        $response->assertSee('Daftar Laporan');
        $response->assertSee('Aspirasi Warga Alpha');
        $response->assertSee('Aspirasi Warga Beta');
        $response->assertSee('2 laporan ditemukan');
        $response->assertSee('Lihat Semua Aspirasi Masyarakat');
        $response->assertSee('Semua Anggota DPRD');
    }

    public function test_filter_by_dprd_member_only_shows_reports_for_that_member(): void
    {
        $admin = $this->administrator();
        $memberA = $this->createMember('Achmad Ariady');
        $memberB = $this->createMember('Bambang Hermanto');

        $reportA = $this->createReport($memberA, ['ringkasan' => 'Jalan Rusak Alpha']);
        $reportB = $this->createReport($memberB, ['ringkasan' => 'Banjir Pasar Beta']);

        // Filter anggota A
        $response = $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $memberA->id]));

        $response->assertOk();
        $response->assertSee('Jalan Rusak Alpha');
        $response->assertDontSee('Banjir Pasar Beta');
        $response->assertSee('1 laporan ditemukan');

        // Member tanpa laporan menampilkan empty state
        $memberC = $this->createMember('Calon Anggota Baru');
        $responseEmpty = $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $memberC->id]));

        $responseEmpty->assertOk();
        $responseEmpty->assertSee('Belum ada laporan');
        $responseEmpty->assertSee('Aspirasi untuk anggota DPRD ini akan tampil di sini.');
    }

    public function test_filter_by_similarity_status(): void
    {
        $admin = $this->administrator();
        $member = $this->createMember('Achmad Ariady');

        $reportWithSim1 = $this->createReport($member, [
            'ringkasan' => 'Laporan Serupa 1',
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);
        $reportWithSim2 = $this->createReport($member, [
            'ringkasan' => 'Laporan Serupa 2',
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);
        $reportWithSim3 = $this->createReport($member, [
            'ringkasan' => 'Laporan Serupa 3',
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);

        $group = LaporanSimilarityGroup::query()->create([
            'kode' => 'GRP-001',
            'dprd_member_id' => $member->id,
            'main_laporan_id' => $reportWithSim1->id,
            'status' => 'menunggu',
            'algorithm_version' => 'v1',
        ]);
        foreach ([$reportWithSim1, $reportWithSim2, $reportWithSim3] as $rep) {
            $rep->update(['similarity_group_id' => $group->id]);
            LaporanSimilarityGroupMember::query()->create([
                'laporan_similarity_group_id' => $group->id,
                'laporan_masyarakat_id' => $rep->id,
                'peran' => $rep->id === $reportWithSim1->id ? 'utama' : 'anggota',
            ]);
        }

        $reportUnique = $this->createReport($member, [
            'ringkasan' => 'Laporan Sangat Unik Sendirian',
            'similarity_level' => null,
            'similarity_group_id' => null,
        ]);

        // 1. Filter: memiliki kemiripan
        $responseSim = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => $member->id,
                'kemiripan' => 'memiliki_kemiripan',
            ]));

        $responseSim->assertOk();
        $responseSim->assertSee('Laporan Serupa 1');
        $responseSim->assertSee('Laporan Serupa 2');
        $responseSim->assertSee('Laporan Serupa 3');
        $responseSim->assertDontSee('Laporan Sangat Unik Sendirian');
        $responseSim->assertSee('Terdapat 3 Aspirasi Serupa');

        // 2. Filter: tidak memiliki kemiripan
        $responseNoSim = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => $member->id,
                'kemiripan' => 'tidak_memiliki_kemiripan',
            ]));

        $responseNoSim->assertOk();
        $responseNoSim->assertSee('Laporan Sangat Unik Sendirian');
        $responseNoSim->assertDontSee('Laporan Serupa 1');
        $responseNoSim->assertDontSee('Laporan Serupa 2');
        $responseNoSim->assertDontSee('Laporan Serupa 3');
    }

    public function test_filter_by_dictionary_status_and_badge_display(): void
    {
        $admin = $this->administrator();
        $member = $this->createMember('Achmad Ariady');

        $reportMatched = $this->createReport($member, [
            'ringkasan' => 'Laporan Masuk Kamus',
            'kamus_status' => LaporanKamusHasil::STATUS_SESUAI,
        ]);

        $reportUnmatched = $this->createReport($member, [
            'ringkasan' => 'Laporan Tidak Ditemukan',
            'kamus_status' => LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN,
        ]);

        // Filter: Sesuai Kamus
        $responseMatch = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => $member->id,
                'status_kamus' => 'sesuai',
            ]));

        $responseMatch->assertOk();
        $responseMatch->assertSee('Laporan Masuk Kamus');
        $responseMatch->assertDontSee('Laporan Tidak Ditemukan');
        $responseMatch->assertSee('<span class="status-badge status-success">Sesuai Kamus</span>', false);
        $responseMatch->assertDontSee('<span class="status-badge status-neutral">Tidak Ditemukan di Kamus</span>', false);

        // Filter: Tidak Ditemukan di Kamus
        $responseUnmatch = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => $member->id,
                'status_kamus' => 'tidak_ditemukan',
            ]));

        $responseUnmatch->assertOk();
        $responseUnmatch->assertSee('Laporan Tidak Ditemukan');
        $responseUnmatch->assertDontSee('Laporan Masuk Kamus');
        $responseUnmatch->assertSee('<span class="status-badge status-neutral">Tidak Ditemukan di Kamus</span>', false);
        $responseUnmatch->assertDontSee('<span class="status-badge status-success">Sesuai Kamus</span>', false);
    }

    public function test_combined_and_filters_simultaneously(): void
    {
        $admin = $this->administrator();
        $memberA = $this->createMember('Achmad Ariady');
        $memberB = $this->createMember('Bambang Hermanto');

        // Report 1: Member A, Memiliki Kemiripan, Sesuai Kamus -> TARGET MATCH
        $target = $this->createReport($memberA, [
            'ringkasan' => 'Target Kombinasi Sempurna',
            'kamus_status' => LaporanKamusHasil::STATUS_SESUAI,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);
        LaporanSimilarityResult::query()->create([
            'laporan_masyarakat_id' => $target->id,
            'compared_laporan_masyarakat_id' => $target->id,
            'similarity_score' => 0.9,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
            'checked_at' => now(),
        ]);

        // Report 2: Member A, Memiliki Kemiripan, TIDAK Sesuai Kamus
        $other1 = $this->createReport($memberA, [
            'ringkasan' => 'Member A Mirip Tapi Tidak Sesuai Kamus',
            'kamus_status' => LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);

        // Report 3: Member A, TIDAK Mirip, Sesuai Kamus
        $other2 = $this->createReport($memberA, [
            'ringkasan' => 'Member A Sesuai Tapi Tidak Mirip',
            'kamus_status' => LaporanKamusHasil::STATUS_SESUAI,
            'similarity_level' => null,
        ]);

        // Report 4: Member B, Memiliki Kemiripan, Sesuai Kamus
        $other3 = $this->createReport($memberB, [
            'ringkasan' => 'Member B Mirip Dan Sesuai Kamus',
            'kamus_status' => LaporanKamusHasil::STATUS_SESUAI,
            'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
        ]);

        // Query with ALL 3 filters: Member A + memiliki_kemiripan + sesuai
        $response = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => $memberA->id,
                'kemiripan' => 'memiliki_kemiripan',
                'status_kamus' => 'sesuai',
            ]));

        $response->assertOk();
        $response->assertSee('Target Kombinasi Sempurna');
        $response->assertDontSee('Member A Mirip Tapi Tidak Sesuai Kamus');
        $response->assertDontSee('Member A Sesuai Tapi Tidak Mirip');
        $response->assertDontSee('Member B Mirip Dan Sesuai Kamus');
        $response->assertSee('1 laporan ditemukan');

        // Query with Semua Anggota + tidak_memiliki_kemiripan + tidak_ditemukan
        $noSimNoKamus = $this->createReport($memberB, [
            'ringkasan' => 'Laporan Bebas Segala Hal',
            'kamus_status' => LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN,
            'similarity_level' => null,
        ]);

        $responseAllMembers = $this->actingAs($admin)
            ->get(route('laporan.index', [
                'anggota' => 'semua',
                'kemiripan' => 'tidak_memiliki_kemiripan',
                'status_kamus' => 'tidak_ditemukan',
            ]));

        $responseAllMembers->assertOk();
        $responseAllMembers->assertSee('Laporan Bebas Segala Hal');
        $responseAllMembers->assertDontSee('Target Kombinasi Sempurna');
    }

    public function test_detail_view_does_not_display_any_kamus_acuan_information(): void
    {
        $admin = $this->administrator();
        $member = $this->createMember('Achmad Ariady');

        $report = $this->createReport($member, [
            'nomor_tiket' => 'LPR-NO-KAMUS-01',
            'ringkasan' => 'Aspirasi Detail Bebas Kamus',
            'isi' => 'Isi aspirasi masyarakat mengenai perbaikan gorong-gorong.',
            'pelapor_nama' => 'Ibu Siti Rahma',
            'kamus_status' => LaporanKamusHasil::STATUS_SESUAI,
        ]);

        $response = $this->actingAs($admin)->get(route('laporan.show', $report));

        $response->assertOk();
        // Informasi utama harus tetap ada
        $response->assertSee('Ibu Siti Rahma');
        $response->assertSee('LPR-NO-KAMUS-01');
        $response->assertSee('Aspirasi Detail Bebas Kamus');
        $response->assertSee('Isi aspirasi masyarakat mengenai perbaikan gorong-gorong.');
        $response->assertSee('Achmad Ariady');

        // Seluruh panel dan teks kamus acuan TIDAK BOLEH tampil di detail
        $response->assertDontSee('id="kamus-acuan"', false);
        $response->assertDontSee('Terdaftar di Kamus Acuan');
        $response->assertDontSee('Periksa Ulang Otomatis');
        $response->assertDontSee('Simpan Keputusan Manual');
        $response->assertDontSee('Entri Kamus Acuan');
        $response->assertDontSee('Catatan Verifikasi');
        $response->assertDontSee('Kriteria Acuan');
    }
}
