@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => $commission->name],
    ],
])

@section('title', 'Folder RDP '.$commission->name)

@section('content')
    <x-admin.page-header
        eyebrow="Folder RDP"
        :title="'Folder RDP '.$commission->name"
        :description="$commission->field
            ? 'Bidang '.$commission->field.'. Seluruh RDP pada halaman ini hanya milik '.$commission->name.'.'
            : 'Seluruh RDP pada halaman ini hanya milik '.$commission->name.'.'"
    >
        <a href="{{ route('komisi.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Daftar Komisi
        </a>

        @if ($commission->is_active)
            <a href="{{ route('komisi.rdp.create', $commission) }}" class="btn btn-emerald">
                <i class="ri-add-line" aria-hidden="true"></i>
                Tambah RDP
            </a>
        @else
            <button type="button" class="btn btn-outline-soft" disabled title="Komisi nonaktif tidak dapat menerima RDP baru">
                <i class="ri-lock-2-line" aria-hidden="true"></i>
                Komisi Nonaktif
            </button>
        @endif
    </x-admin.page-header>

    <div class="app-card commission-summary mb-3">
        <div class="app-card-body">
            <div class="commission-summary-main">
                <span class="commission-icon" aria-hidden="true">
                    <i class="{{ $commission->icon }}"></i>
                </span>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="app-card-title mb-0">{{ $commission->name }}</h2>
                        <span class="status-badge {{ $commission->is_active ? 'status-success' : 'status-danger' }}">
                            {{ $commission->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="commission-summary-field">{{ $commission->field ?: 'Bidang komisi belum diisi' }}</p>
                    <p class="commission-summary-description">
                        {{ $commission->description ?: 'Belum ada deskripsi untuk komisi ini.' }}
                    </p>
                </div>
            </div>

            <div class="commission-summary-stats">
                <div class="commission-summary-stat">
                    <strong>{{ $totalRdp }}</strong>
                    <span>RDP</span>
                </div>
                <div class="commission-summary-stat">
                    <strong>{{ $totalDocuments }}</strong>
                    <span>Dokumen</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('komisi.rdp.index', $commission) }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari judul, kode, tempat, atau ringkasan RDP..."
                       aria-label="Cari RDP">
            </div>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status RDP">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <input type="date" name="tanggal" class="filter-date" value="{{ $filters['tanggal'] }}"
                   aria-label="Filter tanggal RDP" title="Filter tanggal RDP">

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('komisi.rdp.index', $commission) }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar RDP</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $meetings->firstItem() ?? 0 }}–{{ $meetings->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} RDP
                </p>
            </div>
            <span class="status-badge status-gold">{{ number_format($filteredTotal, 0, ',', '.') }} hasil</span>
        </div>

        @if ($meetings->isEmpty())
            @if ($totalRdp === 0)
                <x-admin.empty-state
                    icon="ri-calendar-event-line"
                    title="Belum ada RDP pada komisi ini"
                    description="Folder ini belum memiliki arsip Rapat Dengar Pendapat. Tambahkan RDP untuk mulai menyimpan agenda dan dokumennya."
                >
                    @if ($commission->is_active)
                        <a href="{{ route('komisi.rdp.create', $commission) }}" class="btn btn-emerald btn-sm">
                            <i class="ri-add-line" aria-hidden="true"></i>
                            Tambah RDP
                        </a>
                    @endif
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="RDP tidak ditemukan"
                    description="Tidak ada RDP yang cocok dengan pencarian atau filter Anda pada komisi ini."
                >
                    <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-outline-soft btn-sm">
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
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">RDP</th>
                            <th scope="col">Jadwal</th>
                            <th scope="col" class="d-none d-lg-table-cell">Tempat</th>
                            <th scope="col">Status</th>
                            <th scope="col">Dokumen</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meetings as $meeting)
                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($meetings->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('komisi.rdp.show', [$commission, $meeting]) }}" class="cell-title">
                                        {{ $meeting->title }}
                                    </a>
                                    <span class="cell-sub">{{ $meeting->code ?: 'Tanpa kode RDP' }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $meeting->meeting_date?->locale('id')->translatedFormat('d M Y') ?? '—' }}</span>
                                    <span class="cell-sub">{{ $meeting->start_time_label }}{{ $meeting->start_time ? ' WITA' : '' }}</span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="cell-muted">{{ $meeting->location ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $meeting->status_tone }}">{{ $meeting->status_label }}</span>
                                </td>
                                <td>
                                    @if ($meeting->documents_count > 0)
                                        <span class="status-badge status-success">{{ $meeting->documents_count }} dokumen</span>
                                    @else
                                        <span class="status-badge status-neutral">Belum ada</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <div class="dropdown action-dropdown">
                                            <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                                    data-bs-boundary="viewport"
                                                    data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false" aria-label="Tindakan untuk RDP {{ $meeting->title }}">
                                                <i class="ri-more-2-fill" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('komisi.rdp.show', [$commission, $meeting]) }}">
                                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                                        Lihat Detail
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('komisi.rdp.edit', [$commission, $meeting]) }}">
                                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                                        Edit RDP
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger"
                                                            data-confirm-action
                                                            data-confirm-title="Hapus RDP ini?"
                                                            data-confirm-message="RDP {{ $meeting->title }} akan dihapus (soft delete). Dokumen terkait tetap tersimpan untuk keperluan audit."
                                                            data-confirm-label="Hapus RDP"
                                                            data-confirm-form="#deleteMeeting{{ $meeting->id }}">
                                                        <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                        Hapus RDP
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <form id="deleteMeeting{{ $meeting->id }}" method="POST"
                                          action="{{ route('komisi.rdp.destroy', [$commission, $meeting]) }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $meetings->firstItem() }}–{{ $meetings->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> RDP
                </p>
                <x-admin.pagination :paginator="$meetings" />
            </div>
        @endif
    </div>
@endsection
