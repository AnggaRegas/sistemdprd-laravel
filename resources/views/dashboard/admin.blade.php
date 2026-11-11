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
        description="Pantau arsip dokumen dan laporan masyarakat dari satu tempat."
    >
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
                        <p class="app-card-subtitle">Aspirasi masyarakat masuk per bulan &middot; Januari–Desember {{ $chart['year'] }}</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="chart-legend">
                        <span><i class="is-in" aria-hidden="true"></i> Aspirasi masyarakat</span>
                    </div>

                    <div id="archiveActivityChart" role="img" aria-label="Grafik aspirasi masyarakat masuk per bulan"></div>
                    <script type="application/json" id="archiveActivityData">@json($chart)</script>

                    @if (array_sum($chart['series']) === 0)
                        <p class="text-center text-muted-soft fs-10 mt-3 mb-0">
                            Belum ada aspirasi masyarakat pada tahun {{ $chart['year'] }}.
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
@endsection
