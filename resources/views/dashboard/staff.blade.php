@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Dashboard Staf'],
    ],
])

@section('title', 'Dashboard Staf Pendamping')

@section('content')
    <x-admin.page-header
        eyebrow="Ruang kerja pribadi"
        title="Dashboard Staf Pendamping"
        description="Fokus pada anggota dewan yang Anda dampingi, laporan yang ditugaskan, dan pekerjaan yang perlu diselesaikan."
    >
        <button type="button" class="btn btn-outline-soft" data-demo-toast="Ekspor tugas belum terhubung ke backend. Ini hanya pratinjau tampilan.">
            <i class="ri-download-2-line" aria-hidden="true"></i>
            Ekspor Tugas
        </button>
        <a href="{{ route('arsip.index', ['modal' => 'tambah']) }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Arsip
        </a>
    </x-admin.page-header>

    <div class="demo-note">
        <i class="ri-information-line" aria-hidden="true"></i>
        <div>
            <strong>Data sistem.</strong> Statistik, arsip, dan laporan pada halaman ini dihitung langsung dari database serta dibatasi untuk anggota DPRD yang Anda dampingi.
        </div>
    </div>

    <div class="app-card assignment-card mb-3">
        <div class="assignment-card-inner">
            <div class="assignment-person">
                <span class="avatar avatar-lg" aria-hidden="true">{{ $assignment['inisial'] }}</span>
                <div>
                    <div class="assignment-eyebrow">
                        <i class="ri-user-star-line" aria-hidden="true"></i>
                        Anggota DPRD binaan
                    </div>
                    <h2>{{ $assignment['nama'] }}</h2>
                    <p>{{ $assignment['jabatan'] }} &middot; {{ $assignment['partai'] }} &middot; Dapil {{ $assignment['dapil'] }}</p>
                </div>
            </div>

            <div class="assignment-metrics">
                <div class="assignment-metric">
                    <strong>{{ $assignment['arsip'] }}</strong>
                    <span>Arsip</span>
                </div>
                <div class="assignment-metric">
                    <strong>{{ $assignment['laporan'] }}</strong>
                    <span>Laporan</span>
                </div>
                <div class="assignment-metric">
                    <strong>{{ $assignment['selesai'] }}</strong>
                    <span>Selesai</span>
                </div>
            </div>
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
                        <h2 class="app-card-title">Laporan Ditugaskan kepada Saya</h2>
                        <p class="app-card-subtitle">Laporan aktif yang membutuhkan tindak lanjut</p>
                    </div>
                    <a href="{{ route('laporan.index', ['status' => 'Diproses']) }}" class="app-card-link">
                        Lihat semua
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th scope="col">Laporan</th>
                                <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                                <th scope="col" class="d-none d-sm-table-cell">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignedReports as $report)
                                <tr>
                                    <td>
                                        <span class="cell-title" title="{{ $report['ringkasan'] }}">{{ $report['ringkasan'] }}</span>
                                        <span class="cell-sub">{{ $report['tiket'] }} &middot; {{ $report['pelapor'] }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $report['kategori'] }}</span></td>
                                    <td class="d-none d-sm-table-cell"><span class="cell-muted">{{ \App\Support\PortalData::date($report['tanggal']) }}</span></td>
                                    <td><x-admin.status-badge :status="$report['status']" /></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{ route('laporan.index', ['q' => $report['tiket']]) }}" class="action-menu-btn" aria-label="Buka laporan {{ $report['tiket'] }}">
                                                <i class="ri-arrow-right-up-line" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-empty">
                                        <x-admin.empty-state
                                            icon="ri-checkbox-circle-line"
                                            title="Tidak ada laporan aktif"
                                            description="Semua laporan yang ditugaskan kepada Anda sudah selesai."
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
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Tindak Lanjut</h2>
                        <p class="app-card-subtitle">Ringkasan status penugasan</p>
                    </div>
                </div>

                <div class="app-card-body">
                    @php
                        $followUpTotal = array_sum($followUp);
                        $followUpColors = [
                            'Selesai' => '#16794f',
                            'Diproses' => '#2c6aa0',
                            'Menunggu Penugasan' => '#c9a45c',
                            'Baru' => '#93a09a',
                        ];
                    @endphp

                    @foreach ($followUp as $status => $count)
                        <div class="{{ $loop->last ? '' : 'mb-3' }}">
                            <div class="status-progress-row">
                                <span>{{ $status }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                            <div class="progress-modern">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ round(($count / max($followUpTotal, 1)) * 100) }}%; background: {{ $followUpColors[$status] ?? '#93a09a' }};"
                                     aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ $followUpTotal }}"
                                     aria-label="{{ $status }}: {{ $count }} laporan"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="app-card-footer">
                    <span class="fs-10 text-muted-soft">Total {{ $followUpTotal }} penugasan pada tampilan ini.</span>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Aksi Cepat</h2>
                        <p class="app-card-subtitle">Akses pekerjaan harian</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="quick-grid">
                        <a href="{{ route('arsip.index', ['modal' => 'tambah']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-file-add-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Tambah Arsip</strong>
                                <span>Dokumen anggota binaan</span>
                            </span>
                        </a>

                        <a href="{{ route('laporan.index', ['status' => 'Diproses']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-chat-check-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Laporan Aktif</strong>
                                <span>Pantau tindak lanjut</span>
                            </span>
                        </a>

                        <button type="button" class="quick-action" data-demo-toast="Rekap tugas belum tersedia pada tahap UI. Fitur ini akan dihubungkan pada tahap backend.">
                            <span class="quick-action-icon"><i class="ri-file-list-3-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Rekap Tugas</strong>
                                <span>Ringkasan mingguan</span>
                            </span>
                        </button>

                        <button type="button" class="quick-action" data-demo-toast="Kanal komunikasi internal belum tersedia pada tahap UI.">
                            <span class="quick-action-icon"><i class="ri-customer-service-2-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Hubungi Admin</strong>
                                <span>Konsultasi penugasan</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Pekerjaan Terbaru</h2>
                <p class="app-card-subtitle">Tugas yang perlu diselesaikan &middot; centang hanya mengubah tampilan</p>
            </div>
        </div>

        <div class="app-card-body">
            @if (empty($tasks))
                <x-admin.empty-state
                    icon="ri-task-line"
                    title="Belum ada tugas aktif"
                    description="Laporan yang ditugaskan kepada Anda akan muncul di sini."
                />
            @else
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="task-list">
                        @foreach (array_slice($tasks, 0, 3) as $task)
                            <div class="task-item {{ $task['selesai'] ? 'is-done' : '' }}">
                                <button type="button" class="task-check {{ $task['selesai'] ? 'is-done' : '' }}"
                                        data-task-toggle aria-pressed="{{ $task['selesai'] ? 'true' : 'false' }}"
                                        aria-label="Tandai {{ $task['judul'] }}">
                                    <i class="ri-check-line" aria-hidden="true"></i>
                                </button>
                                <div class="task-item-body">
                                    <strong>{{ $task['judul'] }}</strong>
                                    <p>{{ $task['keterangan'] }}</p>
                                </div>
                                <span class="task-due">
                                    <i class="ri-time-line" aria-hidden="true"></i>
                                    {{ $task['jatuh_tempo'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="task-list">
                        @foreach (array_slice($tasks, 3) as $task)
                            <div class="task-item {{ $task['selesai'] ? 'is-done' : '' }}">
                                <button type="button" class="task-check {{ $task['selesai'] ? 'is-done' : '' }}"
                                        data-task-toggle aria-pressed="{{ $task['selesai'] ? 'true' : 'false' }}"
                                        aria-label="Tandai {{ $task['judul'] }}">
                                    <i class="ri-check-line" aria-hidden="true"></i>
                                </button>
                                <div class="task-item-body">
                                    <strong>{{ $task['judul'] }}</strong>
                                    <p>{{ $task['keterangan'] }}</p>
                                </div>
                                <span class="task-due">
                                    <i class="ri-time-line" aria-hidden="true"></i>
                                    {{ $task['jatuh_tempo'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
