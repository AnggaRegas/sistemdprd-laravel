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
                    <a href="{{ route('anggota.show', $assignment['id']) }}" class="assignment-folder-link">
                        Buka folder Pokir &amp; Hasil Reses
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
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
                        <h2 class="app-card-title">Penugasan Aktif</h2>
                        <p class="app-card-subtitle">Tugas dari Superadmin yang perlu ditindaklanjuti</p>
                    </div>
                    <a href="{{ route('penugasan.index') }}" class="app-card-link">
                        Lihat semua
                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th scope="col">Penugasan</th>
                                <th scope="col" class="d-none d-md-table-cell">Anggota DPRD</th>
                                <th scope="col" class="d-none d-sm-table-cell">Ditugaskan</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penugasanAktif as $penugasan)
                                <tr>
                                    <td>
                                        <span class="cell-title" title="{{ $penugasan->laporan?->ringkasan }}">
                                            {{ $penugasan->laporan?->ringkasan ?? 'Laporan tidak tersedia' }}
                                        </span>
                                        <span class="cell-sub">{{ $penugasan->laporan?->nomor_tiket ?? '—' }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="cell-muted">{{ $penugasan->laporan?->member?->nama_lengkap ?? '—' }}</span>
                                    </td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="cell-muted">{{ \App\Support\PortalData::dateTime($penugasan->assigned_at) }}</span>
                                    </td>
                                    <td><x-admin.status-badge :status="$penugasan->status_label" /></td>
                                    <td>
                                        <div class="table-actions">
                                            @if ($penugasan->status === \App\Models\LaporanPenugasan::STATUS_DITUGASKAN)
                                                <form method="POST" action="{{ route('penugasan.mulai', $penugasan) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-soft btn-sm" title="Mulai tindak lanjut">
                                                        <i class="ri-play-line" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if (! $penugasan->isSelesai())
                                                <button type="button" class="btn btn-emerald btn-sm"
                                                        data-complete-assignment
                                                        data-complete-action="{{ route('penugasan.selesai', $penugasan) }}"
                                                        data-complete-id="{{ $penugasan->id }}"
                                                        data-complete-title="{{ $penugasan->laporan?->ringkasan ?? 'Penugasan' }}">
                                                    <i class="ri-check-double-line" aria-hidden="true"></i>
                                                    Tandai Selesai
                                                </button>
                                            @endif

                                            <a href="{{ route('penugasan.show', $penugasan) }}" class="action-menu-btn" aria-label="Buka penugasan {{ $penugasan->laporan?->nomor_tiket }}">
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
                                            title="Tidak ada penugasan aktif"
                                            description="Semua penugasan yang diberikan kepada Anda sudah selesai ditindaklanjuti."
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
                            'Selesai Ditindaklanjuti' => '#16794f',
                            'Sedang Ditindaklanjuti' => '#2c6aa0',
                            'Ditugaskan' => '#c9a45c',
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
                                     aria-label="{{ $status }}: {{ $count }} penugasan"></div>
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
                        <a href="{{ route('penugasan.index') }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-task-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Semua Penugasan</strong>
                                <span>Status &amp; riwayat tugas</span>
                            </span>
                        </a>

                        <a href="{{ route('anggota.index') }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-team-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Anggota DPRD</strong>
                                <span>Anggota yang Anda dampingi</span>
                            </span>
                        </a>

                        <a href="{{ route('anggota.show', $assignment['id']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-folder-user-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Pokir &amp; Hasil Reses</strong>
                                <span>Folder anggota binaan</span>
                            </span>
                        </a>

                        <a href="{{ route('arsip.index', ['modal' => 'tambah']) }}" class="quick-action">
                            <span class="quick-action-icon"><i class="ri-file-add-line" aria-hidden="true"></i></span>
                            <span>
                                <strong>Tambah Arsip</strong>
                                <span>Dokumen anggota binaan</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Riwayat Penugasan Selesai</h2>
                <p class="app-card-subtitle">Penugasan yang sudah Anda tandai selesai ditindaklanjuti</p>
            </div>
            <a href="{{ route('penugasan.index', ['status' => \App\Models\LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI]) }}" class="app-card-link">
                Lihat semua
                <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th scope="col">Penugasan</th>
                        <th scope="col" class="d-none d-md-table-cell">Hasil Tindak Lanjut</th>
                        <th scope="col" class="d-none d-sm-table-cell">Tanggal Selesai</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatPenugasan as $penugasan)
                        <tr>
                            <td>
                                <span class="cell-title">{{ $penugasan->laporan?->ringkasan ?? '—' }}</span>
                                <span class="cell-sub">{{ $penugasan->laporan?->nomor_tiket ?? '—' }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="cell-muted">{{ \Illuminate\Support\Str::limit($penugasan->hasil_tindak_lanjut, 90) }}</span>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <span class="cell-muted">{{ \App\Support\PortalData::dateTime($penugasan->completed_at) }}</span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('penugasan.show', $penugasan) }}" class="btn btn-outline-soft btn-sm">
                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-empty">
                                <x-admin.empty-state
                                    icon="ri-history-line"
                                    title="Belum ada riwayat"
                                    description="Penugasan yang selesai akan tercatat di sini."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('penugasan._complete-modal')
@endsection

@push('styles')
    <style>
        .assignment-folder-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 10.5px;
            font-weight: 600;
            color: #155043;
            text-decoration: none;
        }

        .assignment-folder-link:hover {
            text-decoration: underline;
        }
    </style>
@endpush
