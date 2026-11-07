@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => $isAdmin ? 'Monitoring Penugasan' : 'Penugasan Saya'],
    ],
])

@section('title', $isAdmin ? 'Monitoring Penugasan Staff Pendamping' : 'Penugasan Saya')

@section('content')
    <x-admin.page-header
        :eyebrow="$isAdmin ? 'Monitoring penyelesaian tugas' : 'Ruang kerja staf'"
        :title="$isAdmin ? 'Monitoring Penugasan Staff Pendamping' : 'Penugasan Saya'"
        :description="$isAdmin
            ? 'Pantau status tindak lanjut setiap penugasan, hasil pekerjaan staf, dan waktu penyelesaiannya.'
            : 'Lihat penugasan yang diberikan Superadmin, statusnya, dan riwayat penyelesaian Anda.'"
    />

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-task-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>
                            <span>Total penugasan</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-inbox-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['ditugaskan'], 0, ',', '.') }}</strong>
                            <span>Ditugaskan</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-loader-4-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['sedang'], 0, ',', '.') }}</strong>
                            <span>Sedang ditindaklanjuti</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #e7f5ee; color: #16794f;">
                            <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['selesai'], 0, ',', '.') }}</strong>
                            <span>Selesai ditindaklanjuti</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('penugasan.index') }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari judul aspirasi, tiket, atau nama staf..."
                       aria-label="Cari penugasan" maxlength="100">
            </div>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status penugasan">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            @if ($isAdmin)
                <select name="staff" class="filter-select" data-auto-submit aria-label="Filter staf pendamping">
                    <option value="">Semua staf pendamping</option>
                    @foreach ($staffs as $staff)
                        <option value="{{ $staff->id }}" @selected((string) $filters['staff'] === (string) $staff->id)>
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('penugasan.index') }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">{{ $isAdmin ? 'Daftar Penugasan' : 'Riwayat Penugasan' }}</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $penugasans->firstItem() ?? 0 }}–{{ $penugasans->lastItem() ?? 0 }}
                    dari {{ number_format($penugasans->total(), 0, ',', '.') }} penugasan
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($penugasans->total(), 0, ',', '.') }} penugasan</span>
        </div>

        @if ($penugasans->isEmpty())
            <x-admin.empty-state
                icon="ri-task-line"
                title="Belum ada penugasan"
                description="Penugasan yang diberikan Superadmin kepada staf pendamping akan muncul di sini."
            />
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col">Judul Aspirasi</th>
                            @if ($isAdmin)
                                <th scope="col" class="d-none d-md-table-cell">Staff Pendamping</th>
                            @endif
                            <th scope="col" class="d-none d-lg-table-cell">Anggota DPRD</th>
                            <th scope="col" class="d-none d-sm-table-cell">Tanggal Penugasan</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="d-none d-md-table-cell">Tanggal Selesai</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penugasans as $penugasan)
                            @php
                                $report = $penugasan->laporan;
                            @endphp
                            <tr>
                                <td>
                                    <span class="cell-title" title="{{ $report?->ringkasan }}">
                                        {{ $report?->ringkasan ?? 'Laporan tidak tersedia' }}
                                    </span>
                                    <span class="cell-sub">{{ $report?->nomor_tiket ?? '—' }}</span>
                                </td>
                                @if ($isAdmin)
                                    <td class="d-none d-md-table-cell">
                                        <div class="table-person">
                                            <x-admin.avatar :name="$penugasan->user?->name ?? 'Staf'" tone="slate" size="sm" />
                                            <div class="table-person-meta">
                                                <strong>{{ $penugasan->user?->name ?? 'Staf Pendamping' }}</strong>
                                                <span>{{ $penugasan->user?->role_label ?? 'Staf Pendamping' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="d-none d-lg-table-cell">
                                    <span class="cell-muted">{{ $report?->member?->nama_lengkap ?? '—' }}</span>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="cell-muted">{{ \App\Support\PortalData::dateTime($penugasan->assigned_at) }}</span>
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$penugasan->status_label" />
                                    @if ($penugasan->isVerified())
                                        <span class="cell-sub d-block">Terverifikasi {{ $penugasan->verifiedBy?->name }}</span>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="cell-muted">
                                        {{ $penugasan->completed_at ? \App\Support\PortalData::dateTime($penugasan->completed_at) : '—' }}
                                    </span>
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
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="app-card-footer">
                <x-admin.pagination :paginator="$penugasans" />
            </div>
        @endif
    </div>
@endsection
