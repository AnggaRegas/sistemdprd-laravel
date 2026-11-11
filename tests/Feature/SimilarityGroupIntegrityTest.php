<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityDecision;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use App\Services\AspirationGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk integritas tabel laporan_similarity_group_members.
 *
 * Unique index pada laporan_masyarakat_id berarti satu aspirasi hanya boleh
 * memiliki SATU baris keanggotaan. Seluruh skenario di bawah tidak boleh
 * memicu UniqueConstraintViolationException.
 */
class SimilarityGroupIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private const PROBLEM = 'Jalan berlubang di depan pasar perlu segera diperbaiki karena membahayakan pengendara dan warga sekitar.';

    private const OTHER_PROBLEM = 'Sampah menumpuk di saluran air menyebabkan banjir saat hujan deras.';

    private const ADDRESS = 'Jalan Melati No. 12, Kelurahan Bukit Indah';

    private const KECAMATAN = 'Kecamatan Ujung';

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
            'name' => 'Superadmin Integritas',
        ]);
    }

    private function report(DprdMember $member, string $problem, string $ticket): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create([
            'nomor_tiket' => $ticket,
            'ringkasan' => $problem,
            'isi' => $problem,
            'pelapor_alamat' => self::ADDRESS,
            'kecamatan' => self::KECAMATAN,
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function membershipCounts(): array
    {
        return LaporanSimilarityGroupMember::query()
            ->selectRaw('laporan_masyarakat_id, COUNT(*) as total')
            ->groupBy('laporan_masyarakat_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('laporan_masyarakat_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function assertSingleMembershipPerReport(): void
    {
        $this->assertSame([], $this->membershipCounts(), 'Ada aspirasi yang terdaftar pada lebih dari satu baris keanggotaan.');
    }

    public function test_duplicate_free_when_report_connects_two_existing_groups(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $first = $this->report($member, self::PROBLEM, 'LPR-INT-001');
        $second = $this->report($member, self::PROBLEM, 'LPR-INT-002');
        $third = $this->report($member, self::OTHER_PROBLEM, 'LPR-INT-003');
        $fourth = $this->report($member, self::OTHER_PROBLEM, 'LPR-INT-004');

        $grouping->regroupMember($member, $admin);

        $this->assertSame(2, LaporanSimilarityGroup::query()->count());

        $firstGroup = LaporanSimilarityGroup::query()->orderBy('id')->firstOrFail();
        $secondGroup = LaporanSimilarityGroup::query()->orderByDesc('id')->firstOrFail();

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $first), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $first->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $third), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $third->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(4, LaporanSimilarityGroupMember::query()->count());

        /*
         * Laporan kelima terdeteksi mirip dengan aspirasi utama dari dua
         * kelompok berbeda. Sebelum perbaikan, keputusan pada laporan ini
         * mencoba menyisipkan ulang laporan ketiga sehingga memicu
         * Duplicate entry pada unique index laporan_masyarakat_id.
         */
        $connector = $this->report($member, self::PROBLEM, 'LPR-INT-005');

        foreach ([$first, $third, $fourth] as $similar) {
            LaporanSimilarityResult::query()->updateOrCreate(
                [
                    'laporan_masyarakat_id' => max($connector->id, $similar->id),
                    'compared_laporan_masyarakat_id' => min($connector->id, $similar->id),
                ],
                [
                    'similarity_score' => 0.95,
                    'similarity_level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                    'evidence' => ['problem_score' => 0.95, 'location_score' => 0.90],
                    'checked_at' => now(),
                ]
            );
        }

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $connector), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $first->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSingleMembershipPerReport();

        $membership = LaporanSimilarityGroupMember::query()
            ->where('laporan_masyarakat_id', $third->id)
            ->firstOrFail();

        $this->assertSame($firstGroup->id, $membership->laporan_similarity_group_id);
        $this->assertSame(
            LaporanSimilarityGroup::STATUS_DIBUBARKAN,
            $secondGroup->refresh()->status
        );

        foreach ([$first, $second, $third, $fourth, $connector] as $report) {
            $report->refresh();
            $this->assertSame($firstGroup->id, $report->similarity_group_id);
        }
    }

    public function test_repeated_recalculation_keeps_one_membership_per_report(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $reports = collect([
            $this->report($member, self::PROBLEM, 'LPR-INT-011'),
            $this->report($member, self::PROBLEM, 'LPR-INT-012'),
            $this->report($member, self::PROBLEM, 'LPR-INT-013'),
            $this->report($member, self::PROBLEM, 'LPR-INT-014'),
        ]);

        for ($iteration = 0; $iteration < 4; $iteration++) {
            $grouping->regroupMember($member, $admin);

            $this->assertSame($reports->count(), LaporanSimilarityGroupMember::query()->count());
            $this->assertSingleMembershipPerReport();
        }

        $this->assertSame(1, LaporanSimilarityGroup::query()->count());
        $this->assertSame(0, LaporanSimilarityResult::query()
            ->whereColumn('laporan_masyarakat_id', 'compared_laporan_masyarakat_id')
            ->count());

        $main = $reports->first();

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $reports->last()), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $main->id,
        ])->assertRedirect()->assertSessionHas('success');

        $group = LaporanSimilarityGroup::query()->firstOrFail();

        for ($iteration = 0; $iteration < 3; $iteration++) {
            $grouping->regroupMember($member, $admin);
        }

        $this->assertSingleMembershipPerReport();
        $this->assertSame($reports->count(), LaporanSimilarityGroupMember::query()->count());
        $this->assertSame(1, LaporanSimilarityGroup::query()->count());
        $this->assertSame($main->id, $group->refresh()->main_laporan_id);
        $this->assertNotNull($group->decided_at);
    }

    public function test_new_report_joins_existing_pending_group_without_duplication(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $first = $this->report($member, self::PROBLEM, 'LPR-INT-021');
        $second = $this->report($member, self::PROBLEM, 'LPR-INT-022');

        $grouping->regroupMember($member, $admin);

        $this->assertSame(1, LaporanSimilarityGroup::query()->count());

        $third = $this->report($member, self::PROBLEM, 'LPR-INT-023');

        $grouping->regroupForReport($third, $admin);

        $this->assertSingleMembershipPerReport();
        $this->assertSame(1, LaporanSimilarityGroup::query()->count());
        $this->assertSame(3, LaporanSimilarityGroupMember::query()->count());

        foreach ([$first, $second, $third] as $report) {
            $report->refresh();
            $this->assertNotNull($report->similarity_group_id);
            $this->assertSame(
                LaporanSimilarityGroup::query()->firstOrFail()->id,
                $report->similarity_group_id
            );
        }
    }

    public function test_report_already_in_group_is_not_assigned_to_a_second_group(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $first = $this->report($member, self::PROBLEM, 'LPR-INT-031');
        $second = $this->report($member, self::PROBLEM, 'LPR-INT-032');

        $grouping->regroupMember($member, $admin);

        // Simulasikan baris keanggotaan lama yang masih tertinggal pada
        // kelompok lain (mis. sisa proses sebelumnya).
        $legacyGroup = LaporanSimilarityGroup::query()->create([
            'dprd_member_id' => $member->id,
            'kode' => 'KLP-99',
            'status' => LaporanSimilarityGroup::STATUS_MENUNGGU,
            'algorithm_version' => 'legacy',
            'threshold_high' => 0.6,
            'threshold_medium' => 0.6,
        ]);

        DB::table('laporan_similarity_group_members')
            ->where('laporan_masyarakat_id', $second->id)
            ->update(['laporan_similarity_group_id' => $legacyGroup->id]);

        $grouping->regroupMember($member, $admin);

        $this->assertSingleMembershipPerReport();
        $this->assertSame(1, LaporanSimilarityGroupMember::query()
            ->where('laporan_masyarakat_id', $second->id)
            ->count());

        $membership = LaporanSimilarityGroupMember::query()
            ->where('laporan_masyarakat_id', $second->id)
            ->firstOrFail();

        $this->assertNotSame($legacyGroup->id, $membership->laporan_similarity_group_id);
        $this->assertSame($membership->laporan_similarity_group_id, $second->refresh()->similarity_group_id);
    }

    public function test_identical_reports_form_one_group_with_full_similarity(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $reports = collect([
            $this->report($member, self::PROBLEM, 'LPR-INT-041'),
            $this->report($member, self::PROBLEM, 'LPR-INT-042'),
            $this->report($member, self::PROBLEM, 'LPR-INT-043'),
        ]);

        $result = $grouping->regroupMember($member, $admin);

        $this->assertSame(3, $result['pairs']);
        $this->assertSame(1, $result['groups']);
        $this->assertSingleMembershipPerReport();
        $this->assertSame(3, LaporanSimilarityGroupMember::query()->count());
        $this->assertSame(3, LaporanSimilarityResult::query()->where('similarity_score', 1.0)->count());

        $groupIds = $reports->map(fn (LaporanMasyarakat $report) => $report->refresh()->similarity_group_id)->unique();

        $this->assertCount(1, $groupIds);
        $this->assertNotNull($groupIds->first());
    }

    public function test_one_report_similar_to_many_reports_has_one_group_only(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $reports = collect(range(1, 5))->map(function (int $index) use ($member) {
            return $this->report($member, self::PROBLEM, 'LPR-INT-05'.$index);
        });

        $grouping->regroupMember($member, $admin);

        $this->assertSingleMembershipPerReport();
        $this->assertSame(5, LaporanSimilarityGroupMember::query()->count());
        $this->assertSame(1, LaporanSimilarityGroup::query()->count());

        $group = LaporanSimilarityGroup::query()->firstOrFail();

        $this->assertSame(5, $group->members()->count());
        $this->assertSame(
            1,
            $reports->map(fn (LaporanMasyarakat $report) => $report->refresh()->similarity_group_id)
                ->unique()
                ->count()
        );
    }

    public function test_deleting_group_member_keeps_membership_table_consistent(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        $first = $this->report($member, self::PROBLEM, 'LPR-INT-061');
        $second = $this->report($member, self::PROBLEM, 'LPR-INT-062');
        $third = $this->report($member, self::PROBLEM, 'LPR-INT-063');

        $grouping->regroupMember($member, $admin);

        $this->actingAs($admin)->post(route('laporan.kemiripan.decide', $third), [
            'action' => LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
            'main_laporan_id' => $first->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->delete(route('laporan.destroy', $first), [
            'alasan' => 'Uji penghapusan anggota kelompok.',
        ])->assertRedirect();

        $this->assertSingleMembershipPerReport();
        $this->assertSame(0, LaporanSimilarityGroupMember::query()
            ->where('laporan_masyarakat_id', $first->id)
            ->count());

        $grouping->regroupMember($member, $admin);

        $this->assertSingleMembershipPerReport();
        $this->assertDatabaseHas('laporan_masyarakat', ['id' => $second->id]);
        $this->assertDatabaseHas('laporan_masyarakat', ['id' => $third->id]);
    }

    public function test_user_three_sample_reports_grouped_properly(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $grouping = app(AspirationGroupingService::class);

        // Contoh dari prompt user:
        // Laporan A: Jalan rusak di dekat rumah saya di Jalan Mappangara.
        // Laporan B: Jalan rusak di dekat rumah saya di Jalan Mappangara.
        // Laporan C: Jalan rusak parah di Mappangara.
        $a = $this->report($member, 'Jalan rusak di dekat rumah saya di Jalan Mappangara.', 'LPR-INT-EX1');
        $b = $this->report($member, 'Jalan rusak di dekat rumah saya di Jalan Mappangara.', 'LPR-INT-EX2');
        $c = $this->report($member, 'Jalan rusak parah di Mappangara.', 'LPR-INT-EX3');

        $result = $grouping->regroupMember($member, $admin);

        $this->assertSame(3, $result['reports']);
        $this->assertSame(3, $result['pairs']);
        $this->assertSame(1, $result['groups']);
        $this->assertSingleMembershipPerReport();

        $group = LaporanSimilarityGroup::query()->firstOrFail();
        $this->assertSame(3, $group->members()->count());

        foreach ([$a, $b, $c] as $report) {
            $report->refresh();
            $this->assertSame($group->id, $report->similarity_group_id);
            $this->assertDatabaseHas('laporan_similarity_group_members', [
                'laporan_similarity_group_id' => $group->id,
                'laporan_masyarakat_id' => $report->id,
            ]);
        }
    }

    public function test_recheck_failure_shows_safe_message_instead_of_exception(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $this->report($member, self::PROBLEM, 'LPR-INT-071');

        $this->mock(AspirationGroupingService::class, function ($mock): void {
            $mock->shouldReceive('regroupMember')->once()->andThrow(new \RuntimeException(
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry'
            ));
        });

        $this->actingAs($admin)
            ->post(route('laporan.kemiripan.recheck', $member))
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
    }
}
