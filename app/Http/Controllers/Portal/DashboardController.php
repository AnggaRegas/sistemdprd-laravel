<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
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

        $assignedBase = LaporanMasyarakat::query()->where('assigned_to', $user->id);

        $assignedTotal = (clone $assignedBase)->count();
        $assignedDone = (clone $assignedBase)
            ->where('status', LaporanMasyarakat::STATUS_SELESAI)
            ->count();
        $activeTotal = (clone $assignedBase)
            ->whereIn('status', LaporanMasyarakat::ACTIVE_STATUSES)
            ->count();

        $assignedReports = (clone $assignedBase)
            ->with(['member', 'assignee'])
            ->whereIn('status', LaporanMasyarakat::ACTIVE_STATUSES)
            ->latest('tanggal_laporan')
            ->latest('id')
            ->limit(9)
            ->get()
            ->map(static fn (LaporanMasyarakat $report) => PortalPresenter::report($report))
            ->all();

        $rawCounts = (clone $assignedBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $followUp = [
            'Selesai' => (int) ($rawCounts[LaporanMasyarakat::STATUS_SELESAI] ?? 0),
            'Diproses' => (int) ($rawCounts[LaporanMasyarakat::STATUS_DIPROSES] ?? 0),
            'Menunggu Penugasan' => (int) ($rawCounts[LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN] ?? 0),
            'Baru' => (int) ($rawCounts[LaporanMasyarakat::STATUS_BARU] ?? 0),
        ];

        $memberReportsDone = LaporanMasyarakat::query()
            ->where('dprd_member_id', $member->id)
            ->where('status', LaporanMasyarakat::STATUS_SELESAI)
            ->count();

        $assignment = [
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
                'label' => 'Arsip Anggota Binaan',
                'value' => $assignment['arsip'],
                'icon' => 'ri-archive-2-line',
                'tint' => '#eef6f1',
                'color' => '#155043',
                'trend' => $member->nama_lengkap,
                'trend_type' => 'ok',
                'meta' => 'dokumen',
            ],
            [
                'label' => 'Laporan Ditugaskan',
                'value' => $assignedTotal,
                'icon' => 'ri-chat-3-line',
                'tint' => '#eaf2fb',
                'color' => '#2c6aa0',
                'trend' => $activeTotal.' aktif',
                'trend_type' => 'flat',
                'meta' => 'ditangani',
            ],
            [
                'label' => 'Laporan Selesai',
                'value' => $assignedDone,
                'icon' => 'ri-checkbox-circle-line',
                'tint' => '#e7f5ee',
                'color' => '#16794f',
                'trend' => $assignedTotal > 0
                    ? round(($assignedDone / $assignedTotal) * 100).'%'
                    : 'Belum ada',
                'trend_type' => $assignedDone > 0 ? 'up' : 'flat',
                'meta' => 'tingkat selesai',
            ],
            [
                'label' => 'Tugas Aktif',
                'value' => $activeTotal,
                'icon' => 'ri-task-line',
                'tint' => '#fcf2e1',
                'color' => '#996414',
                'trend' => $activeTotal > 0 ? 'Perlu tindak lanjut' : 'Tidak ada tugas',
                'trend_type' => $activeTotal > 0 ? 'warn' : 'ok',
                'meta' => 'laporan',
            ],
        ];

        $tasks = collect($assignedReports)
            ->take(5)
            ->map(static function (array $report) {
                return [
                    'judul' => 'Tindak lanjut: '.$report['ringkasan'],
                    'keterangan' => 'Laporan · '.$report['tiket'],
                    'jatuh_tempo' => $report['tanggal']
                        ? PortalData::date($report['tanggal'])
                        : 'Tanpa tenggat',
                    'selesai' => false,
                ];
            })
            ->all();

        return view('dashboard.staff', [
            'stats' => $stats,
            'assignment' => $assignment,
            'tasks' => $tasks,
            'assignedReports' => $assignedReports,
            'followUp' => $followUp,
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
