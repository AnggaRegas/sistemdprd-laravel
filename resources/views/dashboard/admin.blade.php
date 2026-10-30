@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Dashboard Administrator'],
    ],
])

@section('title', 'Dashboard Administrator')

@section('content')
    <x-admin.page-header
        eyebrow="Ringkasan hari ini"
        title="Dashboard Administrator"
        description="Pantau arsip dokumen, laporan masyarakat, dan penugasan staf dari satu tempat."
    >
        <button type="button" class="btn btn-outline-soft" data-demo-toast="Fitur unduh ringkasan belum terhubung ke backend. Ini hanya pratinjau tampilan.">
            <i class="ri-download-2-line" aria-hidden="true"></i>
            Unduh Ringkasan
        </button>
        <a href="{{ route('arsip.index', ['modal' => 'tambah']) }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Arsip
        </a>
    </x-admin.page-header>

    <div class="demo-note">
        <i class="ri-information-line" aria-hidden="true"></i>
        <div>
            <strong>Data sistem.</strong> Statistik dan daftar pada halaman ini dihitung langsung dari database. Form tambah arsip dan anggota masih berupa pratinjau dan belum menyimpan data.
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-admin.stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :tint="$stat['tint']"
                    :color="$stat['color']"
                    :trend="$stat['trend']"
                    :trend-type="$stat['trend_type']"
                    :meta="$stat['meta']"
                    class="h-100"
                />
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="app-card h-100">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Aktivitas Arsip</h2>
                        <p class="app-card-subtitle">Arsip masuk dan keluar &middot; 12 bulan terakhir</p>
                    </div>

                    <div class="chart-toolbar" role="group" aria-label="Rentang grafik">
                        <button type="button" data-chart-range="6" aria-pressed="false">6 bulan</button>
                        <button type="button" data-chart-range="12" class="is-active" aria-pressed="true">12 bulan</button>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="chart-legend">
                        <span><i class="is-in" aria-hidden="true"></i> Arsip masuk</span>
                        <span><i class="is-out" aria-hidden="true"></i> Arsip keluar</span>
                    </div>

                    <div id="archiveActivityChart" role="img" aria-label="Grafik aktivitas arsip masuk dan keluar"></div>
                    <script type="application/json" id="archiveActivityData">@json($chart)</script>

                    @if (array_sum($chart['series12']['in']) + array_sum($chart['series12']['out']) === 0)
                        <p class="text-center text-muted-soft fs-10 mt-3 mb-0">
                            Belum ada data arsip pada 12 bulan terakhir.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="app-card h-100 d-flex flex-column">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Aksi Cepat</h2>
                        <p class="app-card-subtitle">Pekerjaan yang sering dilakukan</p>
                    </div>
                </div>

                <div class="app-card-body flex-grow-1">
                    <div class="quick-grid">
                        <a href="{{ route('arsip.index', ['modal' => 'tambah']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-file-add-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Tambah Arsip</strong>
                                <span>Daftarkan dokumen baru</span>
                            </span>
                        </a>

                        <a href="{{ route('anggota.index', ['modal' => 'tambah-anggota']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-user-add-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Tambah Anggota</strong>
                                <span>Lengkapi data dewan</span>
                            </span>
                        </a>

                        <a href="{{ route('laporan.index', ['status' => 'Baru']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-inbox-unarchive-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Laporan Baru</strong>
                                <span>{{ $statusCounts['Baru'] }} laporan masuk</span>
                            </span>
                        </a>

                        <button type="button" class="quick-action" data-demo-toast="Rekap bulanan belum tersedia pada tahap UI. Fitur ini akan dihubungkan pada tahap backend.">
                            <span class="quick-action-icon"><i class="ri-file-chart-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Rekap Bulanan</strong>
                                <span>Unduh ringkasan kerja</span>
                            </span>
                        </button>

                        <a href="{{ route('arsip.index', ['status' => 'Menunggu Verifikasi']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-shield-check-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Verifikasi Arsip</strong>
                                <span>Tinjau dokumen masuk</span>
                            </span>
                        </a>

                        <a href="{{ route('anggota.index') }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-team-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Kelola Anggota</strong>
                                <span>Data &amp; staf pendamping</span>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="app-card-footer">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="fs-10 text-muted-soft">Butuh panduan alur kerja?</span>
                        <button type="button" class="app-card-link border-0 bg-transparent p-0" data-demo-toast="Panduan penggunaan akan disediakan pada tahap berikutnya.">
                            Panduan
                            <i class="ri-arrow-right-line" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="app-card h-100">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Arsip Terbaru</h2>
                        <p class="app-card-subtitle">Enam dokumen terakhir</p>
                    </div>
                    <a href="{{ route('arsip.index') }}" class="app-card-link">
                        Lihat semua
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th scope="col">Dokumen</th>
                                <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                                <th scope="col" class="d-none d-sm-table-cell">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentArchives as $archive)
                                <tr>
                                    <td>
                                        <span class="cell-title" title="{{ $archive['nama'] }}">{{ $archive['nama'] }}</span>
                                        <span class="cell-sub">{{ $archive['nomor'] }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $archive['kategori'] }}</span></td>
                                    <td class="d-none d-sm-table-cell"><span class="cell-muted">{{ \App\Support\PortalData::date($archive['tanggal']) }}</span></td>
                                    <td><x-admin.status-badge :status="$archive['status']" /></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{ route('arsip.index', ['q' => $archive['nomor']]) }}" class="action-menu-btn" aria-label="Buka arsip {{ $archive['nomor'] }}">
                                                <i class="ri-arrow-right-up-line" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-empty">
                                        <x-admin.empty-state
                                            icon="ri-archive-2-line"
                                            title="Belum ada arsip"
                                            description="Arsip dokumen yang ditambahkan akan muncul di sini."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="app-card h-100 d-flex flex-column">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Laporan Terbaru</h2>
                        <p class="app-card-subtitle">Empat laporan masyarakat terbaru</p>
                    </div>
                    <a href="{{ route('laporan.index') }}" class="app-card-link">
                        Lihat
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="app-card-body p-0 flex-grow-1">
                    <div class="report-feed">
                        @forelse ($latestReports as $report)
                            <a href="{{ route('laporan.index', ['q' => $report['tiket']]) }}" class="report-feed-item">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="report-feed-ticket">{{ $report['tiket'] }}</span>
                                    <x-admin.status-badge :status="$report['status']" />
                                </div>
                                <strong>{{ $report['ringkasan'] }}</strong>
                                <span>{{ $report['pelapor'] }} &middot; {{ \App\Support\PortalData::date($report['tanggal']) }}</span>
                            </a>
                        @empty
                            <x-admin.empty-state
                                icon="ri-chat-3-line"
                                title="Belum ada laporan"
                                description="Laporan masyarakat yang masuk akan muncul di sini."
                            />
                        @endforelse
                    </div>
                </div>

                <div class="app-card-footer">
                    <a href="{{ route('laporan.index') }}" class="app-card-link w-100 justify-content-center">
                        Buka semua laporan
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
