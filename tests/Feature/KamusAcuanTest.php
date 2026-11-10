<?php

namespace Tests\Feature;

use App\Models\DprdCommission;
use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\LaporanKamusHasil;
use App\Models\LaporanKamusVerifikasi;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use App\Services\KamusAcuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KamusAcuanTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['name' => 'Super Admin Kamus']);
    }

    private function version(int $tahun = 2026, bool $active = true, array $overrides = []): KamusAcuanVersi
    {
        $factory = KamusAcuanVersi::factory();

        if ($active) {
            $factory = $factory->active();
        }

        return $factory->create(array_merge([
            'tahun' => $tahun,
            'nama' => 'Kamus Acuan '.$tahun,
        ], $overrides));
    }

    private function entry(KamusAcuanVersi $version, array $overrides = []): KamusAcuanEntri
    {
        return KamusAcuanEntri::factory()->create(array_merge([
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'KOM-01',
            'klasifikasi' => 'Infrastruktur jalan dan drainase',
            'kata_kunci' => [
                ['term' => 'jalan rusak', 'weight' => 3],
                ['term' => 'drainase', 'weight' => 2],
            ],
            'sinonim' => [],
            'prioritas' => 'Tinggi',
            'is_active' => true,
        ], $overrides));
    }

    private function report(string $text = 'Jalan rusak di depan pasar sangat membahayakan pengendara dan drainase tersumbat.'): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create([
            'ringkasan' => \Illuminate\Support\Str::limit($text, 120, '…'),
            'isi' => $text,
        ]);
    }

    public function test_administrator_can_manage_versions_and_entries(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->get(route('kamus.index'))
            ->assertOk()
            ->assertSee('Kamus Acuan')
            ->assertSee('Tambah Versi');

        $this->actingAs($admin)
            ->post(route('kamus.versi.store'), [
                'tahun' => 2026,
                'nama' => 'Kamus Acuan 2026',
                'keterangan' => 'Versi awal pengujian.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('kamus.index', ['versi' => KamusAcuanVersi::first()->id]));

        $version = KamusAcuanVersi::firstOrFail();
        $this->assertTrue($version->is_active);
        $this->assertSame($admin->id, $version->created_by);
        $this->assertDatabaseHas('kamus_acuan_riwayat', [
            'kamus_acuan_versi_id' => $version->id,
            'aksi' => 'versi_dibuat',
        ]);

        $this->actingAs($admin)
            ->post(route('kamus.entri.store', $version), [
                'kode' => 'KOM-01',
                'klasifikasi' => 'Infrastruktur jalan dan drainase',
                'uraian' => 'Jalan, jembatan, dan drainase.',
                'kata_kunci_text' => "jalan|3\njalan rusak|3\ndrainase|2",
                'sinonim_text' => "jalanan|2",
                'kriteria' => 'Kerusakan jalan yang membahayakan pengguna.',
                'prioritas' => 'Tinggi',
                'tindak_lanjut' => 'Koordinasikan dengan dinas terkait.',
                'dprd_commission_id' => null,
                'is_active' => '1',
            ])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]));

        $entry = KamusAcuanEntri::firstOrFail();
        $this->assertSame('KOM-01', $entry->kode);
        $this->assertCount(3, $entry->kata_kunci);
        $this->assertCount(1, $entry->sinonim);
        $this->assertSame('Tinggi', $entry->prioritas);
        $this->assertDatabaseHas('kamus_acuan_riwayat', [
            'kamus_acuan_entri_id' => $entry->id,
            'aksi' => 'entri_dibuat',
        ]);

        $this->actingAs($admin)
            ->get(route('kamus.index', ['versi' => $version->id]))
            ->assertOk()
            ->assertSee('KOM-01')
            ->assertSee('Infrastruktur jalan dan drainase');

        $this->actingAs($admin)
            ->patch(route('kamus.entri.status', $entry))
            ->assertRedirect();

        $this->assertFalse($entry->refresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('kamus.entri.destroy', $entry))
            ->assertRedirect();

        $this->assertSoftDeleted('kamus_acuan_entri', ['id' => $entry->id]);
    }

    public function test_single_clear_match_is_marked_sesuai_and_explained(): void
    {
        $version = $this->version();
        $entry = $this->entry($version);
        $report = $this->report();

        $hasil = app(KamusAcuanService::class)->periksaOtomatis($report);

        $this->assertNotNull($hasil);
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $hasil->status);
        $this->assertSame($entry->id, $hasil->entri_utama_id);
        $this->assertSame($version->tahun, $hasil->tahun_kamus);
        $this->assertSame(1, $hasil->jumlah_kandidat);
        $this->assertStringContainsString('Istilah yang cocok', $hasil->alasan);

        $terms = collect($hasil->kandidat[0]['matched_terms'])->pluck('term')->all();
        $this->assertContains('jalan rusak', $terms);
        $this->assertContains('drainase', $terms);

        $report->refresh();
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $report->kamus_status);
        $this->assertSame($version->id, $report->kamus_versi_id);
        $this->assertSame($entry->id, $report->kamus_entri_id);
        $this->assertSame('Tinggi', $report->kamus_prioritas);
        $this->assertNotNull($report->kamus_diperiksa_at);

        // Pemeriksaan ulang menyimpan hasil baru, bukan menimpa hasil lama.
        app(KamusAcuanService::class)->periksaOtomatis($report);
        $this->assertSame(2, LaporanKamusHasil::query()->where('laporan_masyarakat_id', $report->id)->count());
    }

    public function test_multiple_candidates_always_require_verification(): void
    {
        $version = $this->version();
        $this->entry($version);

        $this->entry($version, [
            'kode' => 'KOM-02',
            'klasifikasi' => 'Lingkungan dan drainase',
            'kata_kunci' => [
                ['term' => 'drainase', 'weight' => 3],
            ],
            'prioritas' => null,
        ]);

        $report = $this->report();

        $hasil = app(KamusAcuanService::class)->periksaOtomatis($report);

        $this->assertSame(LaporanKamusHasil::STATUS_PERLU_VERIFIKASI, $hasil->status);
        $this->assertSame(2, $hasil->jumlah_kandidat);
        $this->assertNull($hasil->prioritas_rekomendasi);
        $this->assertStringContainsString('2 kandidat', $hasil->alasan);

        $report->refresh();
        $this->assertSame(LaporanKamusHasil::STATUS_PERLU_VERIFIKASI, $report->kamus_status);
        $this->assertNull($report->kamus_prioritas);
    }

    public function test_no_match_and_unclear_text_have_honest_statuses(): void
    {
        $version = $this->version();
        $this->entry($version);

        $tidakCocok = $this->report('Lampu taman dan bangku publik perlu diperbaiki karena sudah lama tidak dirawat.');
        $hasilTidakCocok = app(KamusAcuanService::class)->periksaOtomatis($tidakCocok);
        $this->assertSame(LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN, $hasilTidakCocok->status);
        $this->assertSame(0, $hasilTidakCocok->jumlah_kandidat);

        $pendek = $this->report('jalan rusak');
        $hasilPendek = app(KamusAcuanService::class)->periksaOtomatis($pendek);
        $this->assertSame(LaporanKamusHasil::STATUS_PERLU_VERIFIKASI, $hasilPendek->status);
        $this->assertStringContainsString('terlalu pendek', $hasilPendek->alasan);
    }

    public function test_manual_decision_is_preserved_when_dictionary_is_reevaluated(): void
    {
        $admin = $this->administrator();
        $versionA = $this->version(2026);
        $entryA = $this->entry($versionA);
        $report = $this->report();

        app(KamusAcuanService::class)->periksaOtomatis($report);

        $this->actingAs($admin)
            ->post(route('kamus.laporan.verifikasi', $report), [
                'keputusan' => LaporanKamusVerifikasi::KEPUTUSAN_SESUAI,
                'entri_id' => $entryA->id,
                'catatan' => 'Sudah dipastikan sesuai oleh Super Admin.',
            ])
            ->assertRedirect(route('laporan.show', $report));

        $report->refresh();
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $report->kamus_status);
        $this->assertSame($entryA->id, $report->kamus_entri_id);
        $this->assertNotNull($report->kamus_diverifikasi_at);

        $versionB = $this->version(2027, false);
        $this->entry($versionB, [
            'kode' => 'KOM-03',
            'klasifikasi' => 'Pendidikan dan beasiswa',
            'kata_kunci' => [
                ['term' => 'beasiswa', 'weight' => 3],
            ],
            'prioritas' => 'Sedang',
        ]);

        app(KamusAcuanService::class)->activateVersion($versionB, $admin);
        app(KamusAcuanService::class)->periksaOtomatis($report->refresh(), $admin);

        $report->refresh();
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $report->kamus_status);
        $this->assertSame(LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN, $report->kamus_status_otomatis);
        $this->assertSame($entryA->id, $report->kamus_entri_id);
        $this->assertSame($versionA->id, $report->kamus_versi_id);
        $this->assertSame('Tinggi', $report->kamus_prioritas);
        $this->assertSame(1, LaporanKamusVerifikasi::query()->count());
        $this->assertSame(2, LaporanKamusHasil::query()->where('laporan_masyarakat_id', $report->id)->count());
    }

    public function test_staff_cannot_manage_kamus_or_submit_manual_verification(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $report = $this->report();

        $this->actingAs($staff)->get(route('kamus.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('kamus.versi.create'))->assertForbidden();
        $this->actingAs($staff)
            ->post(route('kamus.laporan.verifikasi', $report), [
                'keputusan' => LaporanKamusVerifikasi::KEPUTUSAN_TIDAK_DITEMUKAN,
            ])
            ->assertForbidden();
    }

    public function test_csv_import_and_export_round_trip(): void
    {
        $admin = $this->administrator();
        $version = $this->version();
        DprdCommission::factory()->create(['name' => 'Komisi 2', 'slug' => 'komisi-2']);

        $csv = implode("\n", [
            'kode;klasifikasi;uraian;kata_kunci;sinonim;kriteria;prioritas;tindak_lanjut;komisi;is_active',
            'KOM-01;Infrastruktur jalan;Uraian jalan;"jalan|3; jalan rusak|3";"jalanan|2";Kriteria jalan;Tinggi;Tindak lanjut jalan;Komisi 2;1',
            'KOM-02;Pendidikan dan beasiswa;Uraian pendidikan;beasiswa|3;sekolah|2;Kriteria pendidikan;;;Komisi 2;',
        ]);

        $file = UploadedFile::fake()->createWithContent('kamus.csv', $csv);

        $this->actingAs($admin)
            ->post(route('kamus.versi.impor.store', $version), ['file' => $file])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]))
            ->assertSessionHas('success');

        $this->assertSame(2, KamusAcuanEntri::query()->where('kamus_acuan_versi_id', $version->id)->count());

        $entry = KamusAcuanEntri::query()->where('kode', 'KOM-01')->firstOrFail();
        $this->assertCount(2, $entry->kata_kunci);
        $this->assertCount(1, $entry->sinonim);
        $this->assertSame('Tinggi', $entry->prioritas);
        $this->assertNotNull($entry->dprd_commission_id);

        // Kolom is_active opsional; sel kosong berarti entri tetap aktif.
        $entryKedua = KamusAcuanEntri::query()->where('kode', 'KOM-02')->firstOrFail();
        $this->assertTrue($entryKedua->is_active);

        $response = $this->actingAs($admin)->get(route('kamus.versi.ekspor', $version));
        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('KOM-01', $content);
        $this->assertStringContainsString('KOM-02', $content);
        $this->assertStringContainsString('jalan rusak|3', $content);
    }

    public function test_public_aspiration_submission_triggers_automatic_kamus_check(): void
    {
        $version = $this->version();
        $this->entry($version, [
            'kode' => 'KOM-LAMPU',
            'klasifikasi' => 'Penerangan jalan umum',
            'kata_kunci' => [
                ['term' => 'lampu jalan', 'weight' => 3],
                ['term' => 'jalan', 'weight' => 2],
            ],
            'prioritas' => 'Tinggi',
        ]);

        $region = DprdRegion::query()->where('name', 'Kecamatan Ujung')->firstOrFail();
        $member = DprdMember::factory()->create([
            'dprd_region_id' => $region->id,
            'is_active' => true,
        ]);

        $this->post(route('aspirasi.store'), [
            'pelapor_nama' => 'Andi Saputra',
            'pelapor_kontak' => '081234567890',
            'pelapor_alamat' => 'Jalan Lagaligo No. 12',
            'dprd_region_id' => $region->id,
            'dprd_member_id' => $member->id,
            'isi' => 'Lampu jalan di depan pasar mati sejak seminggu terakhir sehingga jalan menjadi gelap pada malam hari.',
            'persetujuan' => '1',
        ])->assertRedirect(route('aspirasi.success'));

        $report = LaporanMasyarakat::query()->latest('id')->firstOrFail();

        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $report->kamus_status);
        $this->assertSame($version->id, $report->kamus_versi_id);
        $this->assertSame(1, LaporanKamusHasil::query()->where('laporan_masyarakat_id', $report->id)->count());
    }

    public function test_report_detail_shows_kamus_section_and_badges(): void
    {
        $admin = $this->administrator();
        $version = $this->version();
        $entry = $this->entry($version);
        $report = $this->report();

        app(KamusAcuanService::class)->periksaOtomatis($report);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $report->dprd_member_id]))
            ->assertOk()
            ->assertSee('Kamus Acuan')
            ->assertSee('SESUAI KAMUS')
            ->assertSee($entry->kode);

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Terdaftar di Kamus Acuan')
            ->assertDontSee('Mengapa hasil ini muncul?')
            ->assertDontSee('Riwayat Pemeriksaan Otomatis')
            ->assertSee('Periksa Ulang Otomatis')
            ->assertSee('Simpan Keputusan Manual');
    }

    public function test_activation_of_new_version_does_not_change_existing_auto_result_without_reevaluation(): void
    {
        $admin = $this->administrator();
        $versionA = $this->version(2026);
        $entryA = $this->entry($versionA);
        $report = $this->report();

        $first = app(KamusAcuanService::class)->periksaOtomatis($report);
        $this->assertSame($entryA->id, $first->entri_utama_id);

        $versionB = $this->version(2027, false);
        $this->entry($versionB, [
            'kode' => 'KOM-09',
            'klasifikasi' => 'Pendidikan',
            'kata_kunci' => [['term' => 'sekolah', 'weight' => 3]],
        ]);

        app(KamusAcuanService::class)->activateVersion($versionB, $admin);

        $this->assertFalse($versionA->refresh()->is_active);
        $this->assertTrue($versionB->refresh()->is_active);

        $report->refresh();
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $report->kamus_status);
        $this->assertSame($entryA->id, $report->kamus_entri_id);
        $this->assertSame(1, LaporanKamusHasil::query()->where('laporan_masyarakat_id', $report->id)->count());
        $this->assertSame(1, KamusAcuanRiwayat::query()->where('aksi', 'versi_diaktifkan')->count());
        $this->assertSame(1, KamusAcuanRiwayat::query()
            ->where('aksi', 'versi_dinonaktifkan')
            ->where('kamus_acuan_versi_id', $versionA->id)
            ->count());
    }

    public function test_soft_deleted_entry_code_can_be_reused_without_altering_history(): void
    {
        $admin = $this->administrator();
        $version = $this->version();
        $entry = $this->entry($version);
        $report = $this->report();

        $hasil = app(KamusAcuanService::class)->periksaOtomatis($report);
        $this->assertSame($entry->id, $hasil->entri_utama_id);

        $this->actingAs($admin)
            ->delete(route('kamus.entri.destroy', $entry))
            ->assertRedirect();

        $this->assertSoftDeleted('kamus_acuan_entri', ['id' => $entry->id]);

        $this->actingAs($admin)
            ->post(route('kamus.entri.store', $version), [
                'kode' => 'KOM-01',
                'klasifikasi' => 'Infrastruktur jalan versi baru',
                'kata_kunci_text' => "jalan|3\njalan rusak|3",
                'prioritas' => 'Sedang',
                'is_active' => '1',
            ])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]));

        $baru = KamusAcuanEntri::query()->where('kode', 'KOM-01')->firstOrFail();

        $this->assertNotSame($entry->id, $baru->id);
        $this->assertSame(2, KamusAcuanEntri::withTrashed()->where('kamus_acuan_versi_id', $version->id)->count());

        // Hasil otomatis lama tetap menunjuk entri lama, bukan entri baru.
        $this->assertSame($entry->id, $hasil->refresh()->entri_utama_id);
        $this->assertSame('Infrastruktur jalan dan drainase', $hasil->entri_utama_snapshot['klasifikasi']);

        $report->refresh();
        $this->assertSame($entry->id, $report->kamus_entri_id);
        $this->assertStringContainsString('Infrastruktur jalan dan drainase', $report->kamus_entri_label);
    }

    public function test_manual_decision_label_prefers_the_manual_entry(): void
    {
        $admin = $this->administrator();
        $version = $this->version();
        $entryOtomatis = $this->entry($version);
        $entryManual = $this->entry($version, [
            'kode' => 'KOM-02',
            'klasifikasi' => 'Lingkungan dan drainase',
            'kata_kunci' => [['term' => 'drainase', 'weight' => 3]],
        ]);
        $report = $this->report();

        app(KamusAcuanService::class)->periksaOtomatis($report);

        $this->actingAs($admin)
            ->post(route('kamus.laporan.verifikasi', $report), [
                'keputusan' => LaporanKamusVerifikasi::KEPUTUSAN_SESUAI,
                'entri_id' => $entryManual->id,
                'catatan' => 'Dipilih manual oleh Super Admin.',
            ])
            ->assertRedirect(route('laporan.show', $report));

        $report->refresh();

        $this->assertSame($entryManual->id, $report->kamus_entri_id);
        $this->assertStringContainsString('KOM-02', $report->kamus_entri_label);
        $this->assertStringNotContainsString($entryOtomatis->kode.' —', $report->kamus_entri_label);
    }

    public function test_report_detail_without_automatic_result_is_honest(): void
    {
        $admin = $this->administrator();
        $report = $this->report();

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Tidak Terdaftar di Kamus Acuan')
            ->assertSee('Belum ada versi Kamus Acuan aktif')
            ->assertSee('belum diperiksa terhadap Kamus Acuan');
    }

    public function test_new_version_copies_active_entries_without_reviving_deleted_ones(): void
    {
        $admin = $this->administrator();
        $versionA = $this->version(2026, true);
        $aktif = $this->entry($versionA);
        $dihapus = $this->entry($versionA, [
            'kode' => 'KOM-02',
            'klasifikasi' => 'Entri yang sudah dihapus',
        ]);
        $dihapus->delete();

        $this->actingAs($admin)
            ->post(route('kamus.versi.store'), [
                'tahun' => 2027,
                'nama' => 'Kamus Acuan 2027',
                'keterangan' => 'Salinan dari versi sebelumnya.',
                'is_active' => '0',
                'salin_dari' => $versionA->id,
            ])
            ->assertRedirect();

        $versionB = KamusAcuanVersi::query()->where('tahun', 2027)->firstOrFail();
        $salinan = $versionB->entri()->get();

        $this->assertCount(1, $salinan);
        $this->assertSame($aktif->kode, $salinan->first()->kode);
        $this->assertSame($aktif->klasifikasi, $salinan->first()->klasifikasi);
        $this->assertSame($aktif->kata_kunci, $salinan->first()->kata_kunci);
        $this->assertNotSame($aktif->id, $salinan->first()->id);
        $this->assertTrue($versionA->refresh()->is_active);
        $this->assertFalse($versionB->is_active);
    }
}
