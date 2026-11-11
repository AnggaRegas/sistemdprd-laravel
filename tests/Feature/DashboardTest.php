<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Dashboard',
        ]);
    }

    public function test_admin_dashboard_chart_counts_only_aspirations_per_month(): void
    {
        $admin = $this->administrator();
        $year = (int) now()->year;

        LaporanMasyarakat::factory()->create([
            'tanggal_laporan' => $year.'-01-15',
        ]);
        LaporanMasyarakat::factory()->create([
            'tanggal_laporan' => $year.'-05-10',
        ]);
        LaporanMasyarakat::factory()->create([
            'tanggal_laporan' => $year.'-05-20',
        ]);
        LaporanMasyarakat::factory()->create([
            'tanggal_laporan' => ($year - 1).'-05-20',
        ]);

        // Arsip tidak boleh ikut terhitung pada grafik aspirasi.
        Arsip::factory()->count(4)->create([
            'tanggal' => $year.'-05-12',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Arsip Terbaru')
            ->assertViewHas('chart', function (array $chart) use ($year): bool {
                return $chart['year'] === $year
                    && count($chart['labels']) === 12
                    && count($chart['series']) === 12
                    && $chart['series'][0] === 1
                    && $chart['series'][4] === 2
                    && array_sum($chart['series']) === 3;
            });
    }

    public function test_staff_dashboard_hides_follow_up_and_completed_history_sections(): void
    {
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Dashboard Staf']);
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Dashboard']);

        $this->actingAs($staff)
            ->get(route('dashboard.staf'))
            ->assertOk()
            ->assertSee('Ekspor Tugas')
            ->assertSee(route('dashboard.staf.ekspor'), false)
            ->assertDontSee('Riwayat Penugasan Selesai')
            ->assertDontSee('Ringkasan status penugasan');
    }
}
