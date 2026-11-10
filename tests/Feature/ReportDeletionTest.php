<?php

namespace Tests\Feature;

use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityDecision;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use App\Services\AspirationGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = 'Jalan Melati No. 12, Kelurahan Bukit Indah';

    private const KECAMATAN = 'Kecamatan Ujung';

    private const PROBLEM = 'Jalan berlubang di depan pasar perlu segera diperbaiki karena membahayakan pengendara dan warga sekitar.';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aspiration_similarity.location_threshold', 0.70);
        config()->set('aspiration_similarity.problem_threshold', 0.70);
        config()->set('aspiration_similarity.high_threshold', 0.70);
        config()->set('aspiration_similarity.threshold', 0.70);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['name' => 'Super Admin Hapus']);
    }

    private function report(array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'ringkasan' => 'Perbaikan jalan berlubang di depan pasar',
            'isi' => self::PROBLEM,
            'pelapor_alamat' => self::ADDRESS,
            'kecamatan' => self::KECAMATAN,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ], $overrides));
    }

    /**
     * @return Collection<int, LaporanMasyarakat>
     */
    private function similarReports(DprdMember $member, int $count = 3): Collection
    {
        return collect(range(1, $count))->map(function (int $index) use ($member) {
            return $this->report([
                'dprd_member_id' => $member->id,
                'nomor_tiket' => 'LPR-HAPUS-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function test_administrator_can_soft_delete_report_and_relations_are_cleaned(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $target = $reports->last();
        $this->assertSame(1, LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $target->id)
            ->orWhere('compared_laporan_masyarakat_id', $target->id)
            ->count());

        $this->actingAs($admin)
            ->delete(route('laporan.destroy', $target), [
                'alasan' => 'Duplikat dan sudah ditangani pada aspirasi lain.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('laporan_masyarakat', ['id' => $target->id]);

        // Hasil perhitungan kemiripan yang melibatkan aspirasi terhapus dibersihkan.
        $this->assertSame(0, LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $target->id)
            ->orWhere('compared_laporan_masyarakat_id', $target->id)
            ->count());

        // Keanggotaan kelompok ikut dibersihkan.
        $this->assertSame(0, LaporanSimilarityGroupMember::query()
            ->where('laporan_masyarakat_id', $target->id)
            ->count());

        // Jejak audit tersimpan.
        $activity = AktivitasSistem::query()->where('event', 'laporan.dihapus')->first();
        $this->assertNotNull($activity);
        $this->assertSame($target->nomor_tiket, $activity->properties['nomor_tiket']);

        // Aspirasi yang dihapus tidak lagi tampil pada daftar.
        $this->flushSession();

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertDontSee($target->nomor_tiket);
    }

    public function test_deleting_main_report_promotes_replacement_in_group(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 3);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $main = $reports->get(1);
        $replacement = $reports->first();

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.decide', $main), [
                'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                'main_laporan_id' => $main->id,
                'catatan' => 'Aspirasi utama untuk pengujian penghapusan.',
            ])
            ->assertSessionHas('success');

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame($main->id, $group->main_laporan_id);

        $this->actingAs($admin)
            ->delete(route('laporan.destroy', $main))
            ->assertSessionHas('success');

        $group->refresh();

        $this->assertSame($replacement->id, $group->main_laporan_id);
        $this->assertSame(
            LaporanSimilarityGroupMember::PERAN_UTAMA,
            LaporanSimilarityGroupMember::query()
                ->where('laporan_similarity_group_id', $group->id)
                ->where('laporan_masyarakat_id', $replacement->id)
                ->value('peran')
        );

        $replacement->refresh();
        $this->assertSame(LaporanMasyarakat::SIMILARITY_STATUS_UTAMA, $replacement->similarity_status);
        $this->assertStringContainsString('dipromosikan sebagai aspirasi utama pengganti', (string) $group->catatan);
    }

    public function test_deleting_member_leaves_group_with_too_few_members_and_group_is_dissolved(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $reports = $this->similarReports($member, 2);

        app(AspirationGroupingService::class)->regroupMember($member, $admin);

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $target = $reports->last();
        $remaining = $reports->first();

        $this->actingAs($admin)
            ->delete(route('laporan.destroy', $target))
            ->assertSessionHas('success');

        $group->refresh();

        $this->assertSame(LaporanSimilarityGroup::STATUS_DIBUBARKAN, $group->status);
        $this->assertNull($group->main_laporan_id);

        $remaining->refresh();
        $this->assertNull($remaining->similarity_group_id);
        $this->assertNull($remaining->similarity_status);
    }

    public function test_non_administrator_cannot_delete_report(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $report = $this->report(['dprd_member_id' => $member->id]);

        $this->actingAs($staff)
            ->delete(route('laporan.destroy', $report))
            ->assertForbidden();

        $this->assertNotSoftDeleted('laporan_masyarakat', ['id' => $report->id]);
    }

    public function test_guest_cannot_delete_report(): void
    {
        $report = $this->report();

        $this->delete(route('laporan.destroy', $report))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted('laporan_masyarakat', ['id' => $report->id]);
    }

    public function test_administrator_index_and_detail_expose_delete_confirmation_modal(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $report = $this->report(['dprd_member_id' => $member->id]);

        $this->actingAs($admin)
            ->get(route('laporan.index', ['anggota' => $member->id]))
            ->assertOk()
            ->assertSee('Hapus aspirasi?')
            ->assertSee('id="deleteReportModal'.$report->id.'"', false)
            ->assertSee('Hapus Aspirasi');

        $this->actingAs($admin)
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('id="deleteReportDetailModal"', false)
            ->assertSee('Hapus aspirasi?');
    }
}
