<?php

namespace Tests\Feature;

use App\Models\DprdCommission;
use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\LaporanMasyarakat;
use App\Services\AspirationClassifier;
use Database\Seeders\KomisiSeeder;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAspirationTest extends TestCase
{
    use RefreshDatabase;

    private function region(string $name = 'Kecamatan Ujung'): DprdRegion
    {
        return DprdRegion::query()->where('name', $name)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function member(?DprdRegion $region = null, array $overrides = []): DprdMember
    {
        $region ??= $this->region();

        return DprdMember::factory()->create(array_merge([
            'dprd_region_id' => $region->id,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(DprdMember $member, array $overrides = []): array
    {
        return array_merge([
            'pelapor_nama' => 'Andi Saputra',
            'pelapor_kontak' => '081234567890',
            'dprd_region_id' => $member->dprd_region_id,
            'dprd_member_id' => $member->id,
            'isi' => 'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.',
            'persetujuan' => '1',
        ], $overrides);
    }

    public function test_landing_page_links_to_public_aspiration_form(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('Sampaikan Aspirasi')
            ->assertSee(route('aspirasi.create'), false);
    }

    public function test_public_form_displays_required_fields_and_region_options(): void
    {
        $response = $this->get(route('aspirasi.create'));

        $response->assertOk()
            ->assertSee('Sampaikan Aspirasi Anda')
            ->assertSee('-- Pilih Kecamatan / Dapil --')
            ->assertSee('Kecamatan Bacukiki')
            ->assertSee('Kecamatan Bacukiki Barat')
            ->assertSee('Kecamatan Ujung')
            ->assertSee('Kecamatan Soreang')
            ->assertSee('Nama Lengkap')
            ->assertSee('Nomor Telepon atau Email')
            ->assertSee('Anggota DPRD')
            ->assertSee('Isi Aspirasi')
            ->assertSee('Lampiran')
            ->assertSee('Pilih kecamatan/dapil terlebih dahulu')
            ->assertDontSee('Judul Aspirasi')
            ->assertDontSee('Kategori Aspirasi');
    }

    public function test_public_form_rejects_invalid_input(): void
    {
        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), [])
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors([
                'pelapor_nama',
                'pelapor_kontak',
                'dprd_region_id',
                'dprd_member_id',
                'isi',
                'persetujuan',
            ]);

        $member = $this->member();

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'dprd_region_id' => 999999,
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('dprd_region_id');

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'pelapor_kontak' => 'bukan-kontak-yang-valid',
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('pelapor_kontak');

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'lampiran' => UploadedFile::fake()->create('dokumen.exe', 10, 'application/x-msdownload'),
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('lampiran');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
    }

    public function test_public_form_rejects_member_from_another_region(): void
    {
        $regionA = $this->region('Kecamatan Ujung');
        $regionB = $this->region('Kecamatan Soreang');
        $memberB = $this->member($regionB);

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($memberB, [
                'dprd_region_id' => $regionA->id,
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('dprd_member_id');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
    }

    public function test_public_form_rejects_inactive_member_or_region(): void
    {
        $region = $this->region('Kecamatan Ujung');
        $inactiveMember = $this->member($region, ['is_active' => false]);

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($inactiveMember))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('dprd_member_id');

        $activeMember = $this->member($region);
        $region->update(['is_active' => false]);

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($activeMember))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors('dprd_region_id');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
    }

    public function test_public_submission_stores_report_with_initial_queue_status(): void
    {
        Storage::fake('local');
        $this->seed(KomisiSeeder::class);

        $region = $this->region('Kecamatan Ujung');
        $member = $this->member($region, ['nama_lengkap' => 'Anggota Ujung Uji']);

        $response = $this->post(route('aspirasi.store'), $this->payload($member, [
            'lampiran' => UploadedFile::fake()->create('bukti lampiran.pdf', 120, 'application/pdf'),
        ]));

        $response->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();
        $commission2 = DprdCommission::query()->where('slug', 'komisi-2')->firstOrFail();

        $this->assertMatchesRegularExpression(
            '/^ASP-\d{8}-[A-Z0-9]{6}$/',
            $report->nomor_tiket
        );
        $this->assertSame('Andi Saputra', $report->pelapor_nama);
        $this->assertSame('081234567890', $report->pelapor_kontak);
        $this->assertSame(
            'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.',
            $report->ringkasan
        );
        $this->assertNull($report->kategori);
        $this->assertSame($region->name, $report->kecamatan);
        $this->assertSame($member->id, $report->dprd_member_id);
        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);
        $this->assertNull($report->assigned_to);
        $this->assertNull($report->assigned_at);
        $this->assertNull($report->ditutup_at);
        $this->assertSame(now()->toDateString(), $report->tanggal_laporan?->toDateString());

        $this->assertSame($commission2->id, $report->klasifikasi_komisi_id);
        $this->assertSame(
            LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN,
            $report->klasifikasi_status
        );
        $this->assertSame('rule_based_weighted_keywords_v1', $report->klasifikasi_metode);
        $this->assertNotNull($report->klasifikasi_dianalisis_at);
        $this->assertGreaterThanOrEqual(4, (float) $report->klasifikasi_skor);
        $this->assertNotEmpty($report->klasifikasi_keywords);
        $this->assertNotEmpty($report->klasifikasi_alasan);
        $this->assertIsArray($report->klasifikasi_skor_detail);
        $this->assertNull($report->komisi_final_id);

        $this->assertNotNull($report->lampiran_path);
        $this->assertStringStartsWith('laporan-masyarakat/', $report->lampiran_path);
        $this->assertSame('bukti lampiran.pdf', $report->lampiran_nama);
        $this->assertSame('application/pdf', $report->lampiran_mime);
        $this->assertGreaterThan(0, $report->lampiran_size);
        Storage::disk('local')->assertExists($report->lampiran_path);

        $this->assertDatabaseHas('aktivitas_sistem', [
            'event' => 'laporan.dibuat',
            'subject_type' => $report->getMorphClass(),
            'subject_id' => $report->id,
        ]);

        $response->assertSessionHas('aspiration_ticket', $report->nomor_tiket);

        $this->withSession(['aspiration_ticket' => $report->nomor_tiket])
            ->get(route('aspirasi.success'))
            ->assertOk()
            ->assertSee($report->nomor_tiket)
            ->assertSee('Aspirasi Berhasil Dikirim');
    }

    public function test_public_submission_ignores_internal_fields_sent_by_client(): void
    {
        $this->seed(KomisiSeeder::class);

        $member = $this->member();

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'assigned_to' => 999,
            'ditutup_at' => now()->toDateTimeString(),
            'klasifikasi_komisi_id' => 999,
            'klasifikasi_status' => LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU,
            'komisi_final_id' => 999,
        ]))->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();

        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);
        $this->assertNull($report->assigned_to);
        $this->assertNull($report->ditutup_at);
        $this->assertSame($member->id, $report->dprd_member_id);
        $this->assertNull($report->komisi_final_id);
        $this->assertSame(
            DprdCommission::query()->where('slug', 'komisi-2')->value('id'),
            $report->klasifikasi_komisi_id
        );
    }

    public function test_public_submission_with_low_score_is_saved_as_needing_review(): void
    {
        $this->seed(KomisiSeeder::class);

        $member = $this->member();

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => 'Kami memohon perhatian pemerintah kota terhadap kebutuhan warga di lingkungan kami.',
        ]))->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();

        $this->assertSame(LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU, $report->klasifikasi_status);
        $this->assertNull($report->klasifikasi_komisi_id);
        $this->assertNotNull($report->klasifikasi_alasan);
    }

    public function test_public_submission_creates_unique_ticket_numbers(): void
    {
        $member = $this->member();

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => 'Usulan pertama ini berisi kebutuhan penerangan jalan lingkungan permukiman warga.',
        ]))->assertRedirect(route('aspirasi.success'));

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => 'Usulan kedua ini berisi kebutuhan perbaikan saluran air di lingkungan permukiman warga.',
        ]))->assertRedirect(route('aspirasi.success'));

        $tickets = LaporanMasyarakat::query()->pluck('nomor_tiket');

        $this->assertCount(2, $tickets);
        $this->assertCount(2, $tickets->unique());
    }

    public function test_success_page_only_displays_ticket_from_session(): void
    {
        $this->get(route('aspirasi.success'))
            ->assertRedirect(route('welcome'));

        $report = LaporanMasyarakat::factory()->create();

        $this->withSession(['aspiration_ticket' => $report->nomor_tiket])
            ->get(route('aspirasi.success'))
            ->assertOk()
            ->assertSee($report->nomor_tiket);

        $this->withSession(['aspiration_ticket' => 'ASP-TIDAK-ADA'])
            ->get(route('aspirasi.success'))
            ->assertRedirect(route('welcome'));
    }

    public function test_public_form_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('aspirasi.store'), [])
                ->assertStatus(302);
        }

        $this->post(route('aspirasi.store'), [])
            ->assertStatus(429);
    }

    public function test_member_options_only_include_active_members_of_selected_region(): void
    {
        $regionA = $this->region('Kecamatan Ujung');
        $regionB = $this->region('Kecamatan Soreang');

        $memberA = $this->member($regionA, ['nama_lengkap' => 'Anggota Wilayah Ujung']);
        $this->member($regionA, ['nama_lengkap' => 'Anggota Nonaktif', 'is_active' => false]);
        $this->member($regionB, ['nama_lengkap' => 'Anggota Wilayah Soreang']);

        $this->getJson(route('aspirasi.members', ['dprd_region_id' => $regionA->id]))
            ->assertOk()
            ->assertJsonPath('region.id', $regionA->id)
            ->assertJsonCount(1, 'members')
            ->assertJsonPath('members.0.id', $memberA->id)
            ->assertJsonPath('members.0.name', 'Anggota Wilayah Ujung')
            ->assertJsonPath('message', null);
    }

    public function test_member_options_explain_when_region_has_no_members(): void
    {
        $region = $this->region('Kecamatan Bacukiki Barat');

        $this->getJson(route('aspirasi.members', ['dprd_region_id' => $region->id]))
            ->assertOk()
            ->assertJsonCount(0, 'members')
            ->assertJsonPath('message', 'Belum ada anggota DPRD aktif yang terhubung dengan wilayah ini. Silakan pilih wilayah lain atau hubungi Sekretariat DPRD.');
    }

    public function test_member_options_require_a_valid_active_region(): void
    {
        $this->getJson(route('aspirasi.members'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dprd_region_id');

        $this->getJson(route('aspirasi.members', ['dprd_region_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dprd_region_id');

        $region = $this->region('Kecamatan Ujung');
        $region->update(['is_active' => false]);

        $this->getJson(route('aspirasi.members', ['dprd_region_id' => $region->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dprd_region_id');
    }

    public function test_analysis_endpoint_recommends_commission_from_weighted_keywords(): void
    {
        $this->seed(KomisiSeeder::class);

        $response = $this->postJson(route('aspirasi.analyze'), [
            'isi' => 'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN)
            ->assertJsonPath('status_label', 'Direkomendasikan')
            ->assertJsonPath('komisi_nama', 'Komisi 2')
            ->assertJsonPath('metode', 'rule_based_weighted_keywords_v1');

        $keywords = $response->json('keywords');

        $this->assertIsArray($keywords);
        $this->assertContains('jalan', $keywords);
        $this->assertGreaterThanOrEqual(4, (float) $response->json('skor'));
    }

    public function test_analysis_endpoint_marks_ambiguous_text_as_needing_review(): void
    {
        $this->seed(KomisiSeeder::class);

        $this->postJson(route('aspirasi.analyze'), [
            'isi' => 'Jalan menuju sekolah rusak dan perlu diperbaiki.',
        ])
            ->assertOk()
            ->assertJsonPath('status', LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU)
            ->assertJsonPath('status_label', 'Perlu Ditinjau')
            ->assertJsonPath('komisi_id', null);

        $this->postJson(route('aspirasi.analyze'), [
            'isi' => 'Kami memohon perhatian pemerintah kota terhadap kebutuhan warga.',
        ])
            ->assertOk()
            ->assertJsonPath('status', LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU)
            ->assertJsonPath('komisi_id', null);
    }

    public function test_analysis_endpoint_validates_text_length(): void
    {
        $this->postJson(route('aspirasi.analyze'), ['isi' => 'Terlalu pendek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isi');

        $this->postJson(route('aspirasi.analyze'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isi');
    }

    public function test_classifier_never_overwrites_administrator_final_commission(): void
    {
        $this->seed(KomisiSeeder::class);

        $commissions = DprdCommission::query()->orderBy('id')->get();
        $finalCommission = $commissions->last();

        $report = LaporanMasyarakat::factory()->create([
            'komisi_final_id' => $finalCommission->id,
            'komisi_final_ditetapkan_at' => now(),
        ]);

        $classifier = app(AspirationClassifier::class);
        $classifier->apply($report, $classifier->analyze(
            'Lampu jalan di depan pasar mati sehingga jalan menjadi gelap pada malam hari.'
        ));
        $report->save();
        $report->refresh();

        $this->assertSame($finalCommission->id, $report->komisi_final_id);
        $this->assertSame(
            DprdCommission::query()->where('slug', 'komisi-2')->value('id'),
            $report->klasifikasi_komisi_id
        );
    }

    public function test_failed_attachment_storage_never_shows_success_page(): void
    {
        $member = $this->member();

        $disk = \Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')
            ->once()
            ->andThrow(new \RuntimeException('Simulasi penyimpanan lampiran gagal.'));

        $factory = \Mockery::mock(FilesystemFactory::class);
        $factory->shouldReceive('disk')
            ->with('local')
            ->andReturn($disk);

        $this->app->instance(FilesystemFactory::class, $factory);
        $this->app->instance('filesystem', $factory);

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'lampiran' => UploadedFile::fake()->create('bukti.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('pelapor_nama', 'Andi Saputra')
            ->assertSessionMissing('aspiration_ticket');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
    }

    public function test_failed_database_write_rolls_back_attachment_and_never_shows_success(): void
    {
        Storage::fake('local');

        $member = $this->member();

        LaporanMasyarakat::creating(function (): void {
            throw new \RuntimeException('Simulasi pembuatan laporan gagal.');
        });

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'lampiran' => UploadedFile::fake()->create('bukti.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput(
                'isi',
                'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.'
            )
            ->assertSessionMissing('aspiration_ticket');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('laporan-masyarakat'));
    }
}
