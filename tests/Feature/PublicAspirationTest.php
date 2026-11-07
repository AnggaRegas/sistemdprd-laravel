<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityResult;
use Database\Seeders\MemberSeeder;
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
            'pelapor_alamat' => 'Jalan Lagaligo No. 12, Kelurahan Ujung Bulu',
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
            ->assertSee('ALURAKSI')
            ->assertSeeText('Sampaikan Aspirasi & Pengaduan Anda')
            ->assertSeeText('Langsung ke Wakil Rakyat')
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
            ->assertSee('Alamat Lengkap')
            ->assertSee('Anggota DPRD')
            ->assertSee('Isi Aspirasi')
            ->assertSee('Lampiran')
            ->assertSee('Pilih kecamatan/dapil terlebih dahulu')
            ->assertDontSee('Judul Aspirasi')
            ->assertDontSee('Kategori Aspirasi')
            ->assertDontSee('Rekomendasi komisi');
    }

    public function test_public_form_rejects_invalid_input(): void
    {
        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), [])
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors([
                'pelapor_nama',
                'pelapor_kontak',
                'pelapor_alamat',
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
            ->assertSessionHasErrors([
                'lampiran' => 'Format file tidak didukung. Silakan gunakan format file yang diperbolehkan.',
            ]);

        $this->from(route('aspirasi.create'))
            ->post(route('aspirasi.store'), $this->payload($member, [
                'lampiran' => UploadedFile::fake()->create('bukti.pdf', 20481, 'application/pdf'),
            ]))
            ->assertRedirect(route('aspirasi.create'))
            ->assertSessionHasErrors([
                'lampiran' => 'Ukuran file terlalu besar. Maksimal ukuran lampiran adalah 20 MB.',
            ]);

        $this->assertDatabaseCount('laporan_masyarakat', 0);
    }

    public function test_public_form_accepts_attachments_up_to_twenty_megabytes(): void
    {
        Storage::fake('local');

        $member = $this->member();

        foreach ([2048, 10240, 20480] as $sizeKb) {
            $this->post(route('aspirasi.store'), $this->payload($member, [
                'lampiran' => UploadedFile::fake()->create(
                    'bukti-'.$sizeKb.'.pdf',
                    $sizeKb,
                    'application/pdf'
                ),
            ]))->assertRedirect(route('aspirasi.success'));
        }

        $this->assertDatabaseCount('laporan_masyarakat', 3);

        $this->assertSame(
            [2048 * 1024, 10240 * 1024, 20480 * 1024],
            LaporanMasyarakat::query()->orderBy('id')->pluck('lampiran_size')->all()
        );
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

    public function test_public_form_accepts_member_linked_through_region_pivot(): void
    {
        $region = $this->region('Kecamatan Ujung');
        $member = DprdMember::factory()->create([
            'dprd_region_id' => null,
            'is_active' => true,
        ]);
        $member->regions()->attach($region->id);

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'dprd_region_id' => $region->id,
        ]))->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();

        $this->assertSame($member->id, $report->dprd_member_id);
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

        $region = $this->region('Kecamatan Ujung');
        $member = $this->member($region, ['nama_lengkap' => 'Anggota Ujung Uji']);

        $response = $this->post(route('aspirasi.store'), $this->payload($member, [
            'lampiran' => UploadedFile::fake()->create('bukti lampiran.pdf', 120, 'application/pdf'),
        ]));

        $response->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();

        $this->assertMatchesRegularExpression(
            '/^ASP-\d{8}-[A-Z0-9]{6}$/',
            $report->nomor_tiket
        );
        $this->assertSame('Andi Saputra', $report->pelapor_nama);
        $this->assertSame('081234567890', $report->pelapor_kontak);
        $this->assertSame(
            'Jalan Lagaligo No. 12, Kelurahan Ujung Bulu',
            $report->pelapor_alamat
        );
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
        $this->assertNull($report->disetujui_oleh);
        $this->assertNull($report->disetujui_at);
        $this->assertNull($report->ditutup_at);
        $this->assertNull($report->klasifikasi_komisi_id);
        $this->assertSame(now()->toDateString(), $report->tanggal_laporan?->toDateString());

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

        $response->assertSessionHas('aspiration_submitted', true)
            ->assertSessionMissing('aspiration_ticket');

        $this->withSession(['aspiration_submitted' => true])
            ->get(route('aspirasi.success'))
            ->assertOk()
            ->assertSee('Aspirasi Berhasil Dikirim!')
            ->assertDontSee($report->nomor_tiket);
    }

    public function test_public_submission_accepts_short_and_long_content_without_limits(): void
    {
        $member = $this->member();

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => 'tolong',
        ]))->assertRedirect(route('aspirasi.success'));

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => $this->longAspiration(),
        ]))->assertRedirect(route('aspirasi.success'));

        $this->assertSame(2, LaporanMasyarakat::query()->count());

        $this->assertSame(
            $this->longAspiration(),
            LaporanMasyarakat::query()->orderByDesc('id')->firstOrFail()->isi
        );
    }

    private function longAspiration(): string
    {
        return rtrim(str_repeat(
            'Aspirasi perbaikan jalan lingkungan yang panjang ini menggambarkan kebutuhan detail warga. ',
            120
        ));
    }

    public function test_public_submission_ignores_internal_fields_sent_by_client(): void
    {
        $member = $this->member();

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'status' => LaporanMasyarakat::STATUS_SELESAI,
            'assigned_to' => 999,
            'ditutup_at' => now()->toDateTimeString(),
            'disetujui_oleh' => 999,
            'disetujui_at' => now()->toDateTimeString(),
        ]))->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->firstOrFail();

        $this->assertSame(LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN, $report->status);
        $this->assertNull($report->assigned_to);
        $this->assertNull($report->ditutup_at);
        $this->assertNull($report->disetujui_oleh);
        $this->assertNull($report->disetujui_at);
        $this->assertSame($member->id, $report->dprd_member_id);
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

    public function test_success_page_requires_a_recent_submission_and_never_displays_internal_ticket(): void
    {
        $this->get(route('aspirasi.success'))
            ->assertRedirect(route('welcome'));

        $report = LaporanMasyarakat::factory()->create();

        $this->withSession(['aspiration_submitted' => true])
            ->get(route('aspirasi.success'))
            ->assertOk()
            ->assertSee('Aspirasi Berhasil Dikirim!')
            ->assertSee('Kembali ke Portal Publik')
            ->assertDontSee($report->nomor_tiket);

        // Penanda konfirmasi hanya berlaku sekali agar halaman ini tidak dapat
        // dipakai untuk menampilkan ulang informasi internal.
        $this->get(route('aspirasi.success'))
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

    public function test_member_options_include_pivot_linked_members(): void
    {
        $regionA = $this->region('Kecamatan Ujung');
        $pivotMember = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Pivot Ujung',
            'dprd_region_id' => null,
            'is_active' => true,
        ]);
        $pivotMember->regions()->attach($regionA->id);

        $this->getJson(route('aspirasi.members', ['dprd_region_id' => $regionA->id]))
            ->assertOk()
            ->assertJsonCount(1, 'members')
            ->assertJsonPath('members.0.name', 'Anggota Pivot Ujung');
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

    public function test_classification_endpoint_is_retired(): void
    {
        $this->postJson(route('aspirasi.analyze'), [
            'isi' => 'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.',
        ])->assertStatus(410);
    }

    public function test_similarity_results_are_computed_and_stored_for_same_member(): void
    {
        config()->set('aspiration_similarity.threshold', 0.80);

        $region = $this->region('Kecamatan Ujung');
        $member = $this->member($region);

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'isi' => 'Jalan Polobulu blok berlubang sejak bulan lalu sudah mengganggu lalu lintas warga yang sering melintasi perumahan ini.',
        ]))->assertRedirect(route('aspirasi.success'));

        $this->post(route('aspirasi.store'), $this->payload($member, [
            'pelapor_nama' => 'Aminah Rahman',
            'isi' => 'Jalan Polobulu blok berlubang sejak bulan lalu sudah mengganggu lalu lintas warga yang sering melintasi perumahan ini.',
        ]))->assertRedirect(route('aspirasi.success'));

        $latest = LaporanMasyarakat::query()->orderByDesc('id')->firstOrFail();

        $this->assertDatabaseCount('laporan_similarity_results', 1);

        $result = LaporanSimilarityResult::query()->firstOrFail();

        $this->assertSame($latest->id, (int) $result->laporan_masyarakat_id);
        $this->assertGreaterThanOrEqual(0.80, (float) $result->similarity_score);
    }

    public function test_similarity_results_stay_private_to_other_members(): void
    {
        $region = $this->region('Kecamatan Ujung');

        $this->post(route('aspirasi.store'), $this->payload(
            $this->member($region, ['nama_lengkap' => 'Anggota Ujung A']),
            [
                'isi' => 'Jalan berlubang di Siring Siwa membutuhkan penanganan segera karena transportasi warga terganggu.',
            ]
        ))->assertRedirect(route('aspirasi.success'));

        $this->post(route('aspirasi.store'), $this->payload(
            $this->member($region, ['nama_lengkap' => 'Anggota Ujung B']),
            [
                'isi' => 'Jalan berlubang di Siring Siwa membutuhkan penanganan segera karena transportasi warga terganggu.',
            ]
        ))->assertRedirect(route('aspirasi.success'));

        $this->assertDatabaseCount('laporan_similarity_results', 0);
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
            ->assertSessionMissing('aspiration_submitted')
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
            ->assertSessionHasInput('pelapor_alamat', 'Jalan Lagaligo No. 12, Kelurahan Ujung Bulu')
            ->assertSessionMissing('aspiration_submitted')
            ->assertSessionMissing('aspiration_ticket');

        $this->assertDatabaseCount('laporan_masyarakat', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('laporan-masyarakat'));
    }

    public function test_member_seeder_stays_source_of_truth(): void
    {
        $this->seed(MemberSeeder::class);

        $this->assertSame(25, DprdMember::query()->count());
    }
}
