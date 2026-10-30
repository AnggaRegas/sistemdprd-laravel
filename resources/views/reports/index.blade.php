@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat'],
    ],
])

@section('title', 'Laporan Masyarakat')

@section('content')
    <x-admin.page-header
        eyebrow="Aspirasi masyarakat"
        title="Laporan Masyarakat"
        description="Pantau laporan yang masuk, status penanganan, serta anggota dan staf yang ditugaskan untuk menindaklanjuti."
    />

    <div class="demo-note">
        <i class="ri-information-line" aria-hidden="true"></i>
        <div>
            <strong>Data sistem.</strong> Laporan pada halaman ini dibaca langsung dari database. Administrator melihat seluruh laporan, sedangkan staf pendamping hanya melihat laporan yang ditugaskan kepadanya.
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="status-strip report-status-strip">
            @php
                $statusMeta = [
                    'Baru' => ['#2c6aa0', 'ri-inbox-line'],
                    'Menunggu Penugasan' => ['#c9a45c', 'ri-time-line'],
                    'Ditugaskan' => ['#a9853f', 'ri-user-star-line'],
                    'Diproses' => ['#6a56a3', 'ri-loader-4-line'],
                    'Selesai' => ['#16794f', 'ri-checkbox-circle-line'],
                    'Ditolak' => ['#b23a34', 'ri-close-circle-line'],
                ];
            @endphp

            @foreach ($statusCounts as $status => $count)
                <div class="status-strip-item">
                    <div class="status-strip-label">
                        <i style="background: {{ $statusMeta[$status][0] ?? '#93a09a' }};" aria-hidden="true"></i>
                        {{ $status }}
                    </div>
                    <div class="status-strip-value">
                        {{ number_format($count, 0, ',', '.') }}
                        <small>{{ round(($count / max($totalReports, 1)) * 100) }}%</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari tiket, pelapor, ringkasan, atau anggota..." aria-label="Cari laporan">
            </div>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status laporan">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="kecamatan" class="filter-select" data-auto-submit aria-label="Filter kecamatan laporan">
                <option value="">Semua kecamatan</option>
                @foreach ($kecamatans as $kecamatan)
                    <option value="{{ $kecamatan }}" @selected($filters['kecamatan'] === $kecamatan)>{{ $kecamatan }}</option>
                @endforeach
            </select>

            <select name="kategori" class="filter-select" data-auto-submit aria-label="Filter kategori laporan">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($filters['kategori'] === $category)>{{ $category }}</option>
                @endforeach
            </select>

            <div class="filter-date-group" role="group" aria-label="Rentang tanggal laporan">
                <input
                    type="date"
                    name="tanggal_dari"
                    value="{{ $filters['tanggal_dari'] }}"
                    class="filter-date"
                    aria-label="Tanggal laporan mulai"
                    title="Tanggal laporan mulai"
                >
                <span class="filter-date-sep" aria-hidden="true">s.d.</span>
                <input
                    type="date"
                    name="tanggal_sampai"
                    value="{{ $filters['tanggal_sampai'] }}"
                    class="filter-date"
                    aria-label="Tanggal laporan sampai"
                    title="Tanggal laporan sampai"
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('laporan.index') }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Laporan</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} laporan
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($totalReports, 0, ',', '.') }} total laporan</span>
        </div>

        @if ($reports->isEmpty())
            @if ($totalReports === 0)
                <x-admin.empty-state
                    icon="ri-chat-3-line"
                    title="Belum ada laporan"
                    description="Belum ada laporan masyarakat yang masuk ke sistem."
                />
            @else
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Laporan tidak ditemukan"
                    description="Tidak ada laporan yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('laporan.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col">Laporan</th>
                            <th scope="col" class="d-none d-md-table-cell">Kecamatan</th>
                            <th scope="col" class="d-none d-lg-table-cell">Komisi</th>
                            <th scope="col" class="d-none d-sm-table-cell">Tanggal</th>
                            <th scope="col" class="d-none d-xl-table-cell">Penanggung Jawab</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            @php
                                $detailUrl = route('laporan.show', [
                                    'laporan' => $report['id'],
                                    'return_to' => $returnUrl,
                                ]);
                            @endphp
                            <tr>
                                <td>
                                    <span class="cell-title" title="{{ $report['ringkasan'] }}">{{ $report['ringkasan'] }}</span>
                                    <span class="cell-sub">{{ $report['tiket'] }} &middot; {{ $report['pelapor'] }}</span>
                                </td>
                                <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $report['kecamatan'] }}</span></td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="cell-muted">{{ $report['komisi_efektif'] ?: 'Belum terklasifikasi' }}</span>
                                    @if ($report['klasifikasi_komisi'] && ! $report['komisi_final'])
                                        <span class="cell-sub">Rekomendasi · {{ $report['klasifikasi_status_label'] }}</span>
                                    @elseif ($report['komisi_final'])
                                        <span class="cell-sub">Komisi final</span>
                                    @endif
                                </td>
                                <td class="d-none d-sm-table-cell"><span class="cell-muted">{{ \App\Support\PortalData::date($report['tanggal']) }}</span></td>
                                <td class="d-none d-xl-table-cell">
                                    <div class="table-person">
                                        <x-admin.avatar :name="$report['anggota']" tone="slate" size="sm" />
                                        <div class="table-person-meta">
                                            <strong>{{ $report['anggota'] }}</strong>
                                            <span>{{ $report['staf'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$report['status']" />
                                    @if ($report['klasifikasi_status'] && ! $report['komisi_final'])
                                        <span class="status-badge {{ $report['klasifikasi_status'] === \App\Models\LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN ? 'status-success' : 'status-warning' }} mt-1">
                                            {{ $report['klasifikasi_status_label'] }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ $detailUrl }}"
                                           class="action-menu-btn"
                                           aria-label="Lihat detail laporan {{ $report['tiket'] }}">
                                            <i class="ri-eye-line" aria-hidden="true"></i>
                                        </a>

                                        @if ($report['lampiran'])
                                            <a href="{{ route('laporan.lampiran', $report['id']) }}"
                                               class="action-menu-btn"
                                               aria-label="Unduh lampiran laporan {{ $report['tiket'] }}">
                                                <i class="ri-attachment-2" aria-hidden="true"></i>
                                            </a>
                                        @endif

                                        @if (auth()->user()->isAdministrator())
                                            <div class="dropdown">
                                                <button type="button" class="action-menu-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tindakan untuk {{ $report['tiket'] }}">
                                                    <i class="ri-more-2-fill" aria-hidden="true"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ $detailUrl }}">
                                                            <i class="ri-eye-line" aria-hidden="true"></i>
                                                            Lihat Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ $detailUrl }}#penugasan">
                                                            <i class="ri-user-add-line" aria-hidden="true"></i>
                                                            Atur Penugasan
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ $detailUrl }}#status">
                                                            <i class="ri-route-line" aria-hidden="true"></i>
                                                            Ubah Status
                                                        </a>
                                                    </li>
                                                    @if ($report['lampiran'])
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('laporan.lampiran', $report['id']) }}">
                                                                <i class="ri-download-2-line" aria-hidden="true"></i>
                                                                Unduh Lampiran
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> laporan
                </p>
                <x-admin.pagination :paginator="$reports" />
            </div>
        @endif
    </div>
@endsection
