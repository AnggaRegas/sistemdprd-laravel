<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
use App\Models\User;
use App\Support\PortalData;
use App\Support\PortalPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(Request $request): View
    {
        $user = $request->user();

        $archiveTotal = Arsip::query()->count();
        $archiveVerified = Arsip::query()->where('status', Arsip::STATUS_TERVERIFIKASI)->count();
        $memberTotal = DprdMember::query()->where('is_active', true)->count();
        $staffTotal = User::query()->where('role', User::ROLE_STAF_PENDAMPING)->count();
        $reportTotal = LaporanMasyarakat::query()->count();
        $reportDone = LaporanMasyarakat::query()->where('status', LaporanMasyarakat::STATUS_SELESAI)->count();
        $pendingReports = LaporanMasyarakat::query()
            ->where('status', LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN)
            ->count();

        $statusCounts = LaporanMasyarakat::statusLabelCountsFor($user);

        $assignmentStats = [
            'total' => LaporanPenugasan::query()->count(),
            'ditugaskan' => LaporanPenugasan::query()
                ->where('status', LaporanPenugasan::STATUS_DITUGASKAN)
                ->whereNull('released_at')
                ->count(),
            'sedang' => LaporanPenugasan::query()
                ->where('status', LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI)
                ->count(),
            'selesai' => LaporanPenugasan::query()
                ->where('status', LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI)
                ->count(),
        ];

        $latestPenugasan = LaporanPenugasan::query()
            ->with(['laporan.member', 'user', 'completedBy'])
            ->latest('assigned_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $stats = [
            [
                'label' => 'Total Arsip Dokumen',
                'value' => $archiveTotal,
                'icon' => 'ri-archive-2-line',
                'tint' => '#eef6f1',
                'color' => '#155043',
                'trend' => $archiveVerified > 0
                    ? number_format($archiveVerified, 0, ',', '.').' terverifikasi'
                    : 'Belum ada verifikasi',
                'trend_type' => $archiveVerified > 0 ? 'ok' : 'flat',
                'meta' => 'dokumen',
            ],
            [
                'label' => 'Anggota DPRD',
                'value' => $memberTotal,
                'icon' => 'ri-team-line',
                'tint' => '#fbf3e2',
                'color' => '#a9853f',
                'trend' => $staffTotal > 0
                    ? number_format($staffTotal, 0, ',', '.').' staf'
                    : 'Belum ada staf',
                'trend_type' => $staffTotal > 0 ? 'ok' : 'flat',
                'meta' => 'pendamping',
            ],
            [
                'label' => 'Laporan Masyarakat',
                'value' => $reportTotal,
                'icon' => 'ri-chat-3-line',
                'tint' => '#eaf2fb',
                'color' => '#2c6aa0',
                'trend' => $reportDone > 0
                    ? number_format($reportDone, 0, ',', '.').' selesai'
                    : 'Belum ada yang selesai',
                'trend_type' => $reportDone > 0 ? 'up' : 'flat',
                'meta' => 'laporan',
            ],
            [
                'label' => 'Menunggu Penugasan',
                'value' => $pendingReports,
                'icon' => 'ri-time-line',
                'tint' => '#fcf2e1',
                'color' => '#996414',
                'trend' => $pendingReports > 0 ? 'Perlu tindakan' : 'Tidak ada antrean',
                'trend_type' => $pendingReports > 0 ? 'warn' : 'ok',
                'meta' => 'laporan',
            ],
        ];

        $recentArchives = Arsip::query()
            ->with(['kategoriArsip', 'member'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(static fn (Arsip $archive) => PortalPresenter::archive($archive))
            ->all();

        $latestReports = LaporanMasyarakat::query()
            ->with(['member', 'assignee'])
            ->latest('tanggal_laporan')
            ->latest('id')
            ->limit(4)
            ->get()
            ->map(static fn (LaporanMasyarakat $report) => PortalPresenter::report($report))
            ->all();

        return view('dashboard.admin', [
            'stats' => $stats,
            'chart' => $this->chart(),
            'recentArchives' => $recentArchives,
            'latestReports' => $latestReports,
            'statusCounts' => $statusCounts,
            'assignmentStats' => $assignmentStats,
            'latestPenugasan' => $latestPenugasan,
        ]);
    }

    public function staff(Request $request): View
    {
        $user = $request->user();

        $member = $user->member()
            ->withCount(['arsips', 'laporan'])
            ->first();

        if (! $member) {
            abort(403, 'Akun staf pendamping belum dikaitkan dengan anggota DPRD.');
        }

        /*
         * Status penyelesaian tugas dihitung dari data penugasan staf, bukan
         * dari status aspirasi, sehingga penyelesaian penugasan tidak
         * otomatis menutup laporan masyarakat.
         */
        $penugasanBase = LaporanPenugasan::query()->where('user_id', $user->id);

        $penugasanStats = [
            'total' => (clone $penugasanBase)->count(),
            'baru' => (clone $penugasanBase)
                ->whereNull('released_at')
                ->where('status', LaporanPenugasan::STATUS_DITUGASKAN)
                ->count(),
            'sedang' => (clone $penugasanBase)
                ->where('status', LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI)
                ->count(),
            'selesai' => (clone $penugasanBase)
                ->where('status', LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI)
                ->count(),
        ];

        $penugasanAktif = (clone $penugasanBase)
            ->whereNull('released_at')
            ->whereIn('status', LaporanPenugasan::ACTIVE_STATUSES)
            ->with(['laporan.member'])
            ->latest('assigned_at')
            ->latest('id')
            ->limit(9)
            ->get();

        $riwayatPenugasan = (clone $penugasanBase)
            ->where('status', LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI)
            ->with(['laporan'])
            ->latest('completed_at')
            ->latest('id')
            ->limit(6)
            ->get();

        $rawCounts = (clone $penugasanBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $followUp = [
            'Ditugaskan' => (int) ($rawCounts[LaporanPenugasan::STATUS_DITUGASKAN] ?? 0),
            'Sedang Ditindaklanjuti' => (int) ($rawCounts[LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI] ?? 0),
            'Selesai Ditindaklanjuti' => (int) ($rawCounts[LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI] ?? 0),
        ];

        $memberReportsDone = LaporanMasyarakat::query()
            ->where('dprd_member_id', $member->id)
            ->where('status', LaporanMasyarakat::STATUS_SELESAI)
            ->count();

        $assignment = [
            'id' => $member->id,
            'nama' => $member->nama_lengkap,
            'jabatan' => $member->jabatan ?: 'Anggota DPRD',
            'partai' => $member->partai ?: '—',
            'dapil' => $member->dapil ?: '—',
            'komisi' => $member->komisi ?: '—',
            'inisial' => PortalData::initials($member->nama_lengkap),
            'arsip' => (int) $member->arsips_count,
            'laporan' => (int) $member->laporan_count,
            'selesai' => $memberReportsDone,
        ];

        $stats = [
            [
                'label' => 'Total Penugasan',
                'value' => $penugasanStats['total'],
                'icon' => 'ri-task-line',
                'tint' => '#eef6f1',
                'color' => '#155043',
                'trend' => $penugasanStats['selesai'].' selesai',
                'trend_type' => $penugasanStats['selesai'] > 0 ? 'ok' : 'flat',
                'meta' => 'penugasan',
            ],
            [
                'label' => 'Penugasan Baru',
                'value' => $penugasanStats['baru'],
                'icon' => 'ri-inbox-unarchive-line',
                'tint' => '#fbf3e2',
                'color' => '#a9853f',
                'trend' => $penugasanStats['baru'] > 0 ? 'Belum dimulai' : 'Tidak ada',
                'trend_type' => $penugasanStats['baru'] > 0 ? 'warn' : 'ok',
                'meta' => 'ditugaskan',
            ],
            [
                'label' => 'Sedang Ditindaklanjuti',
                'value' => $penugasanStats['sedang'],
                'icon' => 'ri-loader-4-line',
                'tint' => '#eaf2fb',
                'color' => '#2c6aa0',
                'trend' => $penugasanStats['sedang'] > 0 ? 'Sedang dikerjakan' : 'Tidak ada',
                'trend_type' => 'flat',
                'meta' => 'proses',
            ],
            [
                'label' => 'Selesai Ditindaklanjuti',
                'value' => $penugasanStats['selesai'],
                'icon' => 'ri-checkbox-circle-line',
                'tint' => '#e7f5ee',
                'color' => '#16794f',
                'trend' => $penugasanStats['total'] > 0
                    ? round(($penugasanStats['selesai'] / $penugasanStats['total']) * 100).'%'
                    : 'Belum ada',
                'trend_type' => $penugasanStats['selesai'] > 0 ? 'up' : 'flat',
                'meta' => 'tingkat selesai',
            ],
        ];

        return view('dashboard.staff', [
            'stats' => $stats,
            'assignment' => $assignment,
            'followUp' => $followUp,
            'penugasanStats' => $penugasanStats,
            'penugasanAktif' => $penugasanAktif,
            'riwayatPenugasan' => $riwayatPenugasan,
        ]);
    }

    /**
     * @return array<string, array<int, string>|array<int, int>>
     */
    private function chart(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(11);
        $end = Carbon::now()->endOfMonth();

        $archives = Arsip::query()
            ->whereNotNull('tanggal')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get(['tanggal', 'jenis']);

        $buckets = [];

        for ($index = 0; $index < 12; $index++) {
            $month = $start->copy()->addMonths($index);

            $buckets[$month->format('Y-m')] = [
                'label' => $month->locale('id')->translatedFormat('M'),
                'in' => 0,
                'out' => 0,
            ];
        }

        foreach ($archives as $archive) {
            $key = $archive->tanggal?->format('Y-m');

            if (! $key || ! isset($buckets[$key])) {
                continue;
            }

            if ($archive->jenis === Arsip::JENIS_MASUK) {
                $buckets[$key]['in']++;
            } elseif ($archive->jenis === Arsip::JENIS_KELUAR) {
                $buckets[$key]['out']++;
            }
        }

        $labels = array_column($buckets, 'label');
        $incoming = array_column($buckets, 'in');
        $outgoing = array_column($buckets, 'out');

        return [
            'labels12' => $labels,
            'labels6' => array_slice($labels, -6),
            'series12' => [
                'in' => $incoming,
                'out' => $outgoing,
            ],
            'series6' => [
                'in' => array_slice($incoming, -6),
                'out' => array_slice($outgoing, -6),
            ],
        ];
    }
}
