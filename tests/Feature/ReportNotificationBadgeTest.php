<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportNotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(string $name = 'Administrator Notifikasi'): User
    {
        return User::factory()->administrator()->create(['name' => $name]);
    }

    private function createReport(DprdMember $member, array $overrides = []): LaporanMasyarakat
    {
        return LaporanMasyarakat::factory()->create(array_merge([
            'dprd_member_id' => $member->id,
            'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            'assigned_to' => null,
            'assigned_at' => null,
        ], $overrides));
    }

    /**
     * Skenario 1 & 3:
     * Aspirasi baru masuk untuk anggota A: kartu A memiliki badge merah.
     * Anggota B tidak memiliki laporan baru: kartu B tidak memiliki badge merah.
     */
    public function test_scenario_1_and_3_new_aspiration_shows_red_badge_on_target_member_and_not_on_others(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Achmad Ariady, S.E.']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'H. Bambang Hermanto']);

        $this->createReport($memberA, ['ringkasan' => 'Aspirasi jalan berlubang']);

        $response = $this->actingAs($admin)->get(route('laporan.index'));

        $response->assertOk();

        // Kartu Anggota A memiliki badge merah [1 BARU]
        $response->assertSee('Achmad Ariady, S.E.');
        $response->assertSee('1 BARU');
        $response->assertSee('member-unread-badge', false);

        // Kartu Anggota B tidak memiliki badge baru
        $response->assertSee('H. Bambang Hermanto');
        $response->assertDontSee('data-member-unread-slot="'.$memberB->id.'">\s*<span class="member-unread-badge"', false);

        // Sidebar menunjukkan badge 1
        $response->assertSee('<span class="sidebar-badge">1</span>', false);
    }

    /**
     * Skenario 2:
     * Anggota A memiliki 5 laporan (3 lama dan 2 baru):
     * tampilkan "5 laporan" dan "2 BARU" secara terpisah tanpa mencampur hitungan.
     */
    public function test_scenario_2_total_reports_and_new_reports_are_displayed_separately(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Achmad Ariady, S.E.']);

        // 3 laporan lama yang sudah berstatus selesai
        $this->createReport($memberA, ['status' => LaporanMasyarakat::STATUS_SELESAI, 'ditutup_at' => now()]);
        $this->createReport($memberA, ['status' => LaporanMasyarakat::STATUS_SELESAI, 'ditutup_at' => now()]);
        $this->createReport($memberA, ['status' => LaporanMasyarakat::STATUS_SELESAI, 'ditutup_at' => now()]);

        // 2 aspirasi baru yang belum dibaca
        $this->createReport($memberA, ['status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN]);
        $this->createReport($memberA, ['status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN]);

        $response = $this->actingAs($admin)->get(route('laporan.index'));

        $response->assertOk();

        // Total 5 laporan tampil terpisah dari 2 BARU
        $response->assertSee('5 laporan');
        $response->assertSee('2 BARU');
        $response->assertSee('2 aspirasi baru belum dibaca');

        // Sidebar konsisten menghitung 2 aspirasi baru belum dibaca, bukan 5
        $response->assertSee('<span class="sidebar-badge">2</span>', false);
        $response->assertDontSee('<span class="sidebar-badge">5</span>', false);
    }

    /**
     * Skenario 4:
     * Ketika folder anggota dibuka, tampilkan label BARU pada aspirasi yang belum dibaca.
     * Membuka folder TIDAK menghapus badge.
     * Satu aspirasi dibuka: jumlah badge berkurang satu.
     */
    public function test_scenario_4_opening_folder_shows_baru_label_and_reading_one_decrements_badge(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Achmad Ariady, S.E.']);

        $report1 = $this->createReport($memberA, [
            'pelapor_nama' => 'Warga Pertama',
            'ringkasan' => 'Aspirasi Pertama',
        ]);
        $report2 = $this->createReport($memberA, [
            'pelapor_nama' => 'Warga Kedua',
            'ringkasan' => 'Aspirasi Kedua',
        ]);

        // 1. Membuka folder anggota A
        $folderResponse = $this->actingAs($admin)->get(route('laporan.index', ['anggota' => $memberA->id]));
        $folderResponse->assertOk();

        // Label BARU tampil pada baris tabel laporan yang belum dibaca
        $folderResponse->assertSee('report-row-unread-badge', false);
        $folderResponse->assertSee('BARU');
        $folderResponse->assertSee('Warga Pertama');
        $folderResponse->assertSee('Warga Kedua');

        // Membuka folder tidak otomatis menghapus badge (sidebar tetap menunjukkan 2 dan hitungan tetap 2)
        $this->assertSame(2, LaporanMasyarakat::badgeCountFor($admin));
        $folderResponse->assertSee('<span class="sidebar-badge">2</span>', false);

        // 2. Super Admin membuka salah satu aspirasi (report1)
        $detailResponse = $this->actingAs($admin)->get(route('laporan.show', $report1));
        $detailResponse->assertOk();

        // Status baca report1 sudah tercatat untuk admin ini
        $this->assertDatabaseHas('notification_reads', [
            'user_id' => $admin->id,
            'notification_key' => 'laporan-baru-'.$report1->id,
        ]);

        // 3. Kembali ke daftar folder: badge berkurang menjadi 1 BARU
        $indexResponse = $this->actingAs($admin)->get(route('laporan.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('1 BARU');
        $indexResponse->assertDontSee('2 BARU');
        $indexResponse->assertSee('<span class="sidebar-badge">1</span>', false);

        // 4. Membuka kembali folder anggota A: report1 tidak ada label BARU, report2 tetap ada
        $folderResponse2 = $this->actingAs($admin)->get(route('laporan.index', ['anggota' => $memberA->id]));
        $folderResponse2->assertOk();
        $folderResponse2->assertSee('<span class="sidebar-badge">1</span>', false);
        $folderResponse2->assertSee('report-row-unread-badge', false);
    }

    /**
     * Skenario 5:
     * Semua aspirasi baru dibaca: badge merah kartu hilang dan badge sidebar hilang.
     */
    public function test_scenario_5_all_aspirations_read_removes_badge(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Achmad Ariady, S.E.']);

        $report = $this->createReport($memberA);

        // Sebelum dibaca: badge ada
        $this->assertSame(1, LaporanMasyarakat::badgeCountFor($admin));

        // Dibaca oleh Super Admin
        $this->actingAs($admin)->get(route('laporan.show', $report))->assertOk();

        $this->assertSame(0, LaporanMasyarakat::badgeCountFor($admin));

        // Tampilan kartu dan sidebar bersih dari badge merah
        $response = $this->actingAs($admin)->get(route('laporan.index'));
        $response->assertOk();
        $response->assertDontSee('1 BARU');
        $response->assertDontSee('aria-label="1 aspirasi baru belum dibaca"', false);
        $response->assertDontSee('class="sidebar-badge"', false);
    }

    /**
     * Skenario 6:
     * Aspirasi baru kembali masuk: badge muncul kembali.
     */
    public function test_scenario_6_new_aspiration_incoming_shows_badge_again(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Achmad Ariady, S.E.']);

        $report1 = $this->createReport($memberA);

        // Baca laporan 1
        $this->actingAs($admin)->get(route('laporan.show', $report1))->assertOk();
        $this->assertSame(0, LaporanMasyarakat::badgeCountFor($admin));

        // Aspirasi baru berikutnya masuk untuk anggota A
        $this->createReport($memberA, ['ringkasan' => 'Aspirasi gelombang dua']);

        $this->assertSame(1, LaporanMasyarakat::badgeCountFor($admin));

        $response = $this->actingAs($admin)->get(route('laporan.index'));
        $response->assertOk();
        $response->assertSee('1 BARU');
        $response->assertSee('<span class="sidebar-badge">1</span>', false);
    }

    /**
     * Skenario 7:
     * Refresh halaman tidak membuat notifikasi ganda.
     * Polling endpoint mengembalikan data yang akurat dan konsisten.
     */
    public function test_scenario_7_refresh_does_not_duplicate_and_polling_endpoint_is_consistent(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create();
        $this->createReport($memberA);
        $this->createReport($memberA);

        // Kunjungan 1
        $this->actingAs($admin)->get(route('laporan.index'))->assertOk()->assertSee('2 BARU');

        // Kunjungan 2 (refresh)
        $this->actingAs($admin)->get(route('laporan.index'))->assertOk()->assertSee('2 BARU');

        // Endpoint polling unread counts
        $pollingResponse = $this->actingAs($admin)->getJson(route('laporan.unread-counts'));
        $pollingResponse->assertOk()
            ->assertJson([
                'total' => 2,
                'members' => [
                    (string) $memberA->id => 2,
                ],
            ]);
    }

    /**
     * Skenario 8:
     * Status baca setiap akun Super Admin tersimpan secara terpisah (isolasi user).
     */
    public function test_scenario_8_super_admin_read_state_is_isolated_per_user(): void
    {
        $admin1 = $this->administrator('Super Admin Satu');
        $admin2 = $this->administrator('Super Admin Dua');
        $memberA = DprdMember::factory()->create();

        $report = $this->createReport($memberA);

        // Admin 1 membaca aspirasi
        $this->actingAs($admin1)->get(route('laporan.show', $report))->assertOk();

        // Bagi Admin 1: tidak ada badge
        $this->assertSame(0, LaporanMasyarakat::badgeCountFor($admin1));
        $this->actingAs($admin1)->get(route('laporan.index'))
            ->assertOk()
            ->assertDontSee('1 BARU')
            ->assertDontSee('aria-label="1 aspirasi baru belum dibaca"', false)
            ->assertDontSee('class="sidebar-badge"', false);

        // Bagi Admin 2: status masih belum dibaca, badge tetap 1 BARU
        $this->assertSame(1, LaporanMasyarakat::badgeCountFor($admin2));
        $this->actingAs($admin2)->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('1 BARU')
            ->assertSee('<span class="sidebar-badge">1</span>', false);

        // Polling response juga terisolasi
        $this->actingAs($admin1)->getJson(route('laporan.unread-counts'))
            ->assertJson([
                'total' => 0,
                'members' => [],
            ]);

        $this->actingAs($admin2)->getJson(route('laporan.unread-counts'))
            ->assertJson([
                'total' => 1,
                'members' => [
                    (string) $memberA->id => 1,
                ],
            ]);
    }
}
