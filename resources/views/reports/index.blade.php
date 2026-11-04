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
        description="Laporan yang telah didisposisikan kepada Anda beserta perkembangan tindak lanjutnya."
    />

    <div class="app-card filter-card mb-3">
        <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari nama atau isi laporan" aria-label="Cari laporan">
            </div>
            <select name="status" class="filter-select" aria-label="Pilih status">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald">Cari laporan</button>
                @if ($hasFilters)
                    <a href="{{ route('laporan.index') }}" class="filter-reset">Hapus pencarian</a>
                @endif
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Laporan</h2>
                <p class="app-card-subtitle">{{ number_format($filteredTotal, 0, ',', '.') }} laporan ditemukan</p>
            </div>
        </div>

        @if ($reports->isEmpty())
            <x-admin.empty-state
                icon="ri-chat-3-line"
                title="Belum ada laporan"
                description="Laporan yang didisposisikan kepada Anda akan tampil di sini."
            />
        @else
            <div class="table-responsive">
                <table class="table-modern report-simple-table">
                    <thead>
                        <tr>
                            <th scope="col">Nama pelapor</th>
                            <th scope="col" class="d-none d-md-table-cell">Anggota DPRD</th>
                            <th scope="col">Isi aspirasi</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            @php
                                $statusTone = match ($report['status_value']) {
                                    'selesai' => 'success',
                                    'diproses', 'ditugaskan' => 'info',
                                    'ditolak' => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $report['pelapor'] }}</strong>
                                    <span class="cell-sub d-block d-md-none">{{ $report['kecamatan'] }} · {{ \App\Support\PortalData::date($report['tanggal']) }}</span>
                                    <span class="cell-sub d-block text-truncate" style="max-width: 220px;" title="{{ $report['alamat'] }}">{{ $report['alamat'] }}</span>
                                </td>
                                <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $report['anggota'] }}</span></td>
                                <td class="report-summary-cell">
                                    {{ $report['ringkasan'] }}
                                    @if ($report['berpotensi_duplikat'])
                                        <span class="status-badge status-warning d-inline-block mt-1">Berpotensi Duplikat</span>
                                    @endif
                                </td>
                                <td><span class="status-badge status-{{ $statusTone }}">{{ $report['status'] }}</span></td>
                                <td>
                                    <a href="{{ route('laporan.show', ['laporan' => $report['id'], 'return_to' => $returnUrl]) }}"
                                       class="btn btn-outline-soft report-view-button">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">Menampilkan <strong>{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</strong> dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> laporan</p>
                <x-admin.pagination :paginator="$reports" />
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .report-simple-table { min-width: 760px; }
        .report-simple-table th, .report-simple-table td { padding: 15px 14px; font-size: 13px; vertical-align: middle; }
        @media (max-width: 767.98px) { .report-simple-table { min-width: 580px; } }
        .report-summary-cell { min-width: 220px; max-width: 420px; line-height: 1.6; }
        .report-view-button { min-height: 40px; white-space: nowrap; }
    </style>
@endpush
