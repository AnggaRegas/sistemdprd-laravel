<?php

namespace Tests\Feature;

use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\KamusKriteria;
use App\Models\LaporanKamusHasil;
use App\Models\User;
use App\Services\KamusAcuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KamusKriteriaTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['name' => 'Super Admin Kriteria']);
    }

    private function version(User $admin, int $tahun = 2026): KamusAcuanVersi
    {
        return app(KamusAcuanService::class)->createVersion([
            'tahun' => $tahun,
            'nama' => 'Kamus Acuan '.$tahun,
            'is_active' => true,
        ], $admin);
    }

    private function entry(KamusAcuanVersi $version): KamusAcuanEntri
    {
        return KamusAcuanEntri::factory()->create([
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'KOM-01',
            'klasifikasi' => 'Infrastruktur jalan dan drainase',
            'kata_kunci' => [
                ['term' => 'jalan rusak', 'weight' => 3],
            ],
            'sinonim' => [],
            'is_active' => true,
        ]);
    }

    public function test_new_version_seeds_default_criteria(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $this->assertSame(count(KamusKriteria::FAKTORS), $version->kriteria()->count());
        $this->assertDatabaseHas('kamus_kriteria', [
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'skor_minimum',
            'bobot' => 4,
        ]);
        $this->assertSame(
            1,
            KamusAcuanRiwayat::query()
                ->where('kamus_acuan_versi_id', $version->id)
                ->where('aksi', 'kriteria_dibuat')
                ->count()
        );
    }

    public function test_skor_minimum_criteria_changes_spk_evaluation_result(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);
        $this->entry($version);

        $service = app(KamusAcuanService::class);
        $text = 'Jalan rusak di depan pasar sangat membahayakan pengendara.';

        // Skor kata kunci 3 × bobot 1 × bonus frasa 1,25 = 3,75, di bawah skor minimum bawaan 4.
        $defaultResult = $service->evaluateText($text, $version);
        $this->assertSame(3.75, (float) $defaultResult['kandidat'][0]['skor']);
        $this->assertSame(LaporanKamusHasil::STATUS_PERLU_VERIFIKASI, $defaultResult['status']);

        // Ubah kriteria SPK: skor minimum menjadi 1.
        $criteria = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', 'skor_minimum')
            ->firstOrFail();

        $service->updateCriteria($criteria, ['bobot' => 1], $admin);

        $updatedResult = $service->evaluateText($text, $version);
        $this->assertSame(LaporanKamusHasil::STATUS_SESUAI, $updatedResult['status']);
        $this->assertSame((float) 1, $service->criteriaForVersion($version)['skor_minimum']);
    }

    public function test_keyword_weight_changes_computed_score(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);
        $this->entry($version);

        $service = app(KamusAcuanService::class);
        $text = 'Jalan rusak di depan pasar sangat membahayakan pengendara.';

        $this->assertSame(3.75, (float) $service->evaluateText($text, $version)['kandidat'][0]['skor']);

        $criteria = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', 'bobot_kata_kunci')
            ->firstOrFail();

        $service->updateCriteria($criteria, ['bobot' => 2], $admin);

        $result = $service->evaluateText($text, $version);
        $this->assertSame(7.5, (float) $result['kandidat'][0]['skor']);
        $this->assertSame(7.5, (float) $result['kandidat'][0]['matched_terms'][0]['effective_weight']);
    }

    public function test_inactive_criteria_falls_back_to_previous_value(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $criteria = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', 'bobot_kata_kunci')
            ->firstOrFail();

        app(KamusAcuanService::class)->updateCriteria($criteria, [
            'bobot' => 5,
            'is_active' => false,
        ], $admin);

        $this->assertSame(1.0, (float) app(KamusAcuanService::class)->criteriaForVersion($version)['bobot_kata_kunci']);
    }

    public function test_administrator_can_add_update_and_delete_criteria_through_ui(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $this->actingAs($admin)
            ->get(route('kamus.index', ['versi' => $version->id]))
            ->assertOk()
            ->assertSee('Kriteria &amp; Bobot Perhitungan (SPK)', false)
            ->assertSee('Bobot kata kunci')
            ->assertSee('Skor minimum sesuai kamus')
            ->assertSee('Seluruh faktor perhitungan yang didukung sudah tersedia pada versi ini.');

        // Hapus satu kriteria agar faktor tersebut tersedia untuk ditambahkan lagi.
        $criteria = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', 'bobot_frasa')
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('kamus.kriteria.destroy', $criteria))
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kamus_kriteria', ['id' => $criteria->id]);
        $this->assertSame(
            1,
            KamusAcuanRiwayat::query()->where('aksi', 'kriteria_dihapus')->count()
        );

        $this->actingAs($admin)
            ->get(route('kamus.index', ['versi' => $version->id]))
            ->assertOk()
            ->assertSee('Tambah Kriteria')
            ->assertSee('Bonus frasa');

        $this->actingAs($admin)
            ->post(route('kamus.kriteria.store', $version), [
                'kode' => 'bobot_frasa',
                'nama' => 'Bonus frasa khusus',
                'bobot' => 1.5,
                'keterangan' => 'Diatur melalui pengujian.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]))
            ->assertSessionHas('success');

        $created = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', 'bobot_frasa')
            ->firstOrFail();

        $this->assertSame(1.5, $created->bobot);
        $this->assertSame('Bonus frasa khusus', $created->nama);
        $this->assertSame(2, KamusAcuanRiwayat::query()->where('aksi', 'kriteria_dibuat')->count());

        $this->actingAs($admin)
            ->put(route('kamus.kriteria.update', $created), [
                'nama' => 'Bonus frasa khusus',
                'bobot' => 2.25,
                'keterangan' => 'Nilai diperbarui.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]))
            ->assertSessionHas('success');

        $created->refresh();
        $this->assertSame(2.25, $created->bobot);
        $this->assertSame(
            1,
            KamusAcuanRiwayat::query()->where('aksi', 'kriteria_diubah')->count()
        );
    }

    public function test_duplicate_criteria_code_is_rejected_without_partial_change(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);
        $before = KamusKriteria::query()->count();

        $this->actingAs($admin)
            ->post(route('kamus.kriteria.store', $version), [
                'kode' => 'skor_minimum',
                'bobot' => 9,
            ])
            ->assertSessionHasErrors('kode');

        $this->assertSame($before, KamusKriteria::query()->count());
    }

    public function test_staff_cannot_manage_spk_criteria(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('kamus.index', ['versi' => $version->id]))
            ->assertForbidden();

        $criteria = KamusKriteria::query()->where('kamus_acuan_versi_id', $version->id)->firstOrFail();

        $this->actingAs($staff)
            ->put(route('kamus.kriteria.update', $criteria), [
                'nama' => 'Diubah staf',
                'bobot' => 99,
            ])
            ->assertForbidden();

        $this->assertNotSame('Diubah staf', $criteria->refresh()->nama);
    }

    public function test_copying_version_copies_criteria_values(): void
    {
        $admin = $this->administrator();
        $source = $this->version($admin, 2026);
        $criteria = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $source->id)
            ->where('kode', 'skor_minimum')
            ->firstOrFail();

        app(KamusAcuanService::class)->updateCriteria($criteria, ['bobot' => 7], $admin);

        $copy = app(KamusAcuanService::class)->createVersion([
            'tahun' => 2027,
            'nama' => 'Kamus Acuan 2027',
            'is_active' => false,
            'salin_dari' => $source->id,
        ], $admin);

        $this->assertSame(
            7.0,
            (float) KamusKriteria::query()
                ->where('kamus_acuan_versi_id', $copy->id)
                ->where('kode', 'skor_minimum')
                ->value('bobot')
        );
    }
}
