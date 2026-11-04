@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => $selectedMember
        ? [
            ['label' => 'Beranda', 'url' => route('dashboard')],
            ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
            ['label' => $selectedMember->nama_lengkap],
        ]
        : [
            ['label' => 'Beranda', 'url' => route('dashboard')],
            ['label' => 'Laporan Masyarakat'],
        ],
])

@section('title', $selectedMember ? 'Laporan '.$selectedMember->nama_lengkap : 'Laporan Masyarakat')

@section('content')
    <x-admin.page-header
        eyebrow="Aspirasi masyarakat"
        :title="$selectedMember ? 'Laporan untuk '.$selectedMember->nama_lengkap : 'Laporan Masyarakat'"
        :description="$selectedMember
            ? 'Daftar aspirasi yang ditujukan kepada anggota DPRD ini.'
            : 'Pilih folder anggota DPRD untuk melihat aspirasi yang ditujukan kepadanya.'"
    >
        @if ($selectedMember)
            <a href="{{ route('laporan.index') }}" class="btn btn-outline-soft">
                <i class="ri-arrow-left-line" aria-hidden="true"></i>
                Kembali ke Folder
            </a>
        @endif
    </x-admin.page-header>

    @if (! $selectedMember)
        <div class="app-card filter-card mb-3">
            <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar" role="search">
                <div class="filter-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Cari nama anggota DPRD" aria-label="Cari anggota DPRD">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-emerald">Cari anggota</button>
                    @if ($hasFolderQuery)
                        <a href="{{ route('laporan.index') }}" class="filter-reset">Hapus pencarian</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Folder Anggota DPRD</h2>
                    <p class="app-card-subtitle">{{ $members->count() }} anggota aktif · folder dibuat dari data database</p>
                </div>
            </div>

            @if ($members->isEmpty())
                <x-admin.empty-state
                    icon="ri-team-line"
                    title="Belum ada anggota DPRD"
                    description="Folder akan tampil otomatis setelah data anggota DPRD tersedia."
                />
            @else
                <div class="app-card-body">
                    <div class="row g-3">
                        @foreach ($members as $member)
                            <div class="col-sm-6 col-xl-4">
                                <a href="{{ route('laporan.index', ['anggota' => $member->id]) }}"
                                   class="d-flex h-100 align-items-center gap-3 rounded-4 border p-3 text-decoration-none report-folder"
                                   style="border-color: #e5e9e6; background: #fbfcfa;">
                                    <span class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                          style="width: 46px; height: 46px; background: #e6f0eb; color: #155043;">
                                        <i class="ri-folder-user-line" aria-hidden="true" style="font-size: 22px;"></i>
                                    </span>
                                    <span class="min-w-0 flex-grow-1">
                                        <strong class="d-block text-truncate" style="font-size: 12.5px; color: #193b32;">
                                            {{ $member->nama_lengkap }}
                                        </strong>
                                        <span class="d-block text-muted" style="font-size: 10px;">
                                            {{ $member->laporan_count > 0
                                                ? number_format($member->laporan_count, 0, ',', '.').' laporan'
                                                : 'Belum ada laporan' }}
                                        </span>
                                    </span>
                                    <i class="ri-arrow-right-s-line flex-shrink-0" aria-hidden="true" style="color: #a17f3e;"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="app-card filter-card mb-3">
            <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar" role="search">
                <input type="hidden" name="anggota" value="{{ $selectedMember->id }}">
                <div class="filter-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Cari nama pelapor atau isi laporan" aria-label="Cari laporan">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-emerald">Cari laporan</button>
                    @if ($hasFolderQuery)
                        <a href="{{ route('laporan.index', ['anggota' => $selectedMember->id]) }}" class="filter-reset">Hapus pencarian</a>
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
                    description="Aspirasi untuk anggota DPRD ini akan tampil di sini."
                />
            @else
                <div class="table-responsive">
                    <table class="table-modern report-simple-table">
                        <thead>
                            <tr>
                                <th scope="col">Nama pelapor</th>
                                <th scope="col" class="d-none d-md-table-cell">Anggota DPRD tujuan</th>
                                <th scope="col">Isi aspirasi</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $statusTone = match ($report->status) {
                                        \App\Models\LaporanMasyarakat::STATUS_SELESAI => 'success',
                                        \App\Models\LaporanMasyarakat::STATUS_DIPROSES,
                                        \App\Models\LaporanMasyarakat::STATUS_DITUGASKAN => 'info',
                                        \App\Models\LaporanMasyarakat::STATUS_DITOLAK => 'danger',
                                        default => 'warning',
                                    };
                                    $isDuplicate = $duplicateIds->contains($report->id);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $report->pelapor_nama }}</strong>
                                        <span class="cell-sub d-block d-md-none">{{ $report->kecamatan_label }} · {{ \App\Support\PortalData::date($report->tanggal_laporan?->toDateString()) }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $report->member?->nama_lengkap ?: '—' }}</span></td>
                                    <td class="report-summary-cell">
                                        {{ $report->ringkasan }}
                                        @if ($isDuplicate)
                                            <span class="status-badge status-warning d-inline-block mt-1">Berpotensi Duplikat</span>
                                        @endif
                                    </td>
                                    <td><span class="status-badge status-{{ $statusTone }}">{{ $report->status_label }}</span></td>
                                    <td>
                                        <a href="{{ route('laporan.show', ['laporan' => $report, 'return_to' => $returnUrl]) }}"
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
    @endif
@endsection

@push('styles')
    <style>
        .report-folder { transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
        .report-folder:hover { border-color: #b9cdc3 !important; box-shadow: 0 12px 28px -20px rgba(14, 59, 49, .5); transform: translateY(-1px); }
        .report-simple-table { min-width: 760px; }
        .report-simple-table th, .report-simple-table td { padding: 15px 14px; font-size: 13px; vertical-align: middle; }
        .report-summary-cell { min-width: 220px; max-width: 420px; line-height: 1.6; }
        .report-view-button { min-height: 40px; white-space: nowrap; }
    </style>
@endpush
