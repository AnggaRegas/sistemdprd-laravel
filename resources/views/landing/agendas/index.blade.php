@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Agenda Kegiatan'],
    ],
])

@section('title', 'Agenda Kegiatan')

@section('content')
    @php
        $hasFilters = ($filters['q'] ?? '') !== ''
            || $filters['status'] !== null
            || $filters['event_status'] !== null
            || $filters['date_from'] !== null
            || $filters['date_to'] !== null;

        $eventStatusClasses = [
            \App\Models\LandingAgenda::EVENT_UPCOMING => 'status-info',
            \App\Models\LandingAgenda::EVENT_ONGOING => 'status-gold',
            \App\Models\LandingAgenda::EVENT_COMPLETED => 'status-success',
            \App\Models\LandingAgenda::EVENT_CANCELLED => 'status-danger',
        ];

        $statusClasses = [
            \App\Models\LandingAgenda::STATUS_PUBLISHED => 'status-success',
            \App\Models\LandingAgenda::STATUS_DRAFT => 'status-warning',
            \App\Models\LandingAgenda::STATUS_ARCHIVED => 'status-neutral',
        ];
    @endphp

    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Agenda Kegiatan"
        description="Kelola jadwal kegiatan DPRD Kota Parepare yang tampil pada halaman informasi publik."
    >
        <a href="{{ route('landing.agendas.create') }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Agenda
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-calendar-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['total'] }}</strong>
                            <span>Total agenda</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['published'] }}</strong>
                            <span>Published</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-draft-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['draft'] }}</strong>
                            <span>Draft</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-calendar-event-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['upcoming'] }}</strong>
                            <span>Agenda mendatang</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="app-card-body">
            <form method="GET" action="{{ route('landing.agendas.index') }}" class="row g-3" role="search">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="filter-q">Cari nama kegiatan</label>
                    <input type="search" id="filter-q" name="q" value="{{ $filters['q'] }}"
                           class="form-control" placeholder="Cari nama kegiatan...">
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="filter-status">Status Publikasi</label>
                    <select id="filter-status" name="status" class="form-select" data-auto-submit>
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['status'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="filter-event-status">Status Kegiatan</label>
                    <select id="filter-event-status" name="event_status" class="form-select" data-auto-submit>
                        <option value="">Semua kegiatan</option>
                        @foreach ($eventStatusOptions as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['event_status'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="filter-date-from">Dari Tanggal</label>
                    <input type="date" id="filter-date-from" name="date_from" value="{{ $filters['date_from'] }}"
                           class="form-control">
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="filter-date-to">Sampai Tanggal</label>
                    <input type="date" id="filter-date-to" name="date_to" value="{{ $filters['date_to'] }}"
                           class="form-control">
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-emerald btn-sm">
                        <i class="ri-filter-3-line" aria-hidden="true"></i>
                        Terapkan
                    </button>
                    @if ($hasFilters)
                        <a href="{{ route('landing.agendas.index') }}" class="btn btn-outline-soft btn-sm">
                            <i class="ri-refresh-line" aria-hidden="true"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Agenda</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $agendas->firstItem() ?? 0 }}–{{ $agendas->lastItem() ?? 0 }}
                    dari {{ number_format($agendas->total(), 0, ',', '.') }} agenda
                </p>
            </div>
        </div>

        @if ($agendas->isEmpty())
            @if ($hasFilters)
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Agenda tidak ditemukan"
                    description="Tidak ada agenda yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('landing.agendas.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-calendar-2-line"
                    title="Belum ada agenda"
                    description="Belum ada agenda kegiatan yang tercatat. Tambahkan agenda untuk mulai mengisi jadwal kegiatan publik."
                >
                    <a href="{{ route('landing.agendas.create') }}" class="btn btn-emerald btn-sm">
                        <i class="ri-add-line" aria-hidden="true"></i>
                        Tambah Agenda
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">Nama Kegiatan</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col" class="d-none d-sm-table-cell">Waktu</th>
                            <th scope="col" class="d-none d-lg-table-cell">Lokasi</th>
                            <th scope="col">Status Kegiatan</th>
                            <th scope="col" class="d-none d-md-table-cell">Status Publikasi</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agendas as $item)
                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($agendas->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $item->title }}</span>
                                    <span class="cell-sub">{{ $item->location ?: 'Lokasi belum diisi' }}</span>
                                </td>
                                <td>
                                    <span class="cell-muted">{{ $item->event_date?->format('d M Y') ?? '—' }}</span>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="cell-muted">
                                        @if ($item->start_time && $item->end_time)
                                            {{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="cell-muted">{{ $item->location ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $eventStatusClasses[$item->event_status] ?? 'status-neutral' }}">
                                        {{ $item->event_status_label }}
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="status-badge {{ $statusClasses[$item->status] ?? 'status-neutral' }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('landing.agendas.edit', $item) }}" class="btn btn-outline-soft btn-sm">
                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                            Edit
                                        </a>

                                        <form id="delete-agenda-{{ $item->id }}" method="POST"
                                              action="{{ route('landing.agendas.destroy', $item) }}" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <button type="button" class="btn btn-soft-danger btn-sm"
                                                data-confirm-action
                                                data-confirm-form="#delete-agenda-{{ $item->id }}"
                                                data-confirm-title="Hapus agenda ini?"
                                                data-confirm-message="Agenda {{ $item->title }} akan dihapus permanen beserta gambarnya."
                                                data-confirm-label="Ya, hapus">
                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $agendas->firstItem() }}–{{ $agendas->lastItem() }}</strong>
                    dari <strong>{{ number_format($agendas->total(), 0, ',', '.') }}</strong> agenda
                </p>
                <x-admin.pagination :paginator="$agendas" />
            </div>
        @endif
    </div>
@endsection
