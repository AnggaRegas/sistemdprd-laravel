@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Anggota DPRD'],
    ],
])

@section('title', 'Daftar Anggota DPRD Kota Parepare')

@section('content')
    <x-admin.page-header
        eyebrow="Data kelembagaan"
        title="Daftar Anggota DPRD Kota Parepare"
        description="Data resmi 25 anggota dewan periode 2024–2029, jabatan, pendamping, dan berkas arsip terkait."
    >
        @can('create', App\Models\DprdMember::class)
            <a href="{{ route('anggota.create') }}" class="btn btn-gold">
                <i class="ri-add-line" aria-hidden="true"></i>
                Tambah Anggota Dewan
            </a>
        @endcan
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-team-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>
                            <span>Total anggota</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf6ee; color: #1f7a4c;">
                            <i class="ri-user-follow-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['active'], 0, ',', '.') }}</strong>
                            <span>Anggota aktif</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-user-star-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['staff'], 0, ',', '.') }}</strong>
                            <span>Staf pendamping</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-archive-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($summary['archives'], 0, ',', '.') }}</strong>
                            <span>Arsip terkait</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header member-card-header">
            <div>
                <h2 class="app-card-title">Data Anggota Dewan</h2>
                <p class="app-card-subtitle">Menampilkan seluruh legislator resmi DPRD Kota Parepare.</p>
            </div>

            <form method="GET" action="{{ route('anggota.index') }}" class="member-search" role="search">
                @foreach (['status', 'partai', 'kecamatan', 'staff'] as $filterKey)
                    @if ($filters[$filterKey] !== '')
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                    @endif
                @endforeach

                <div class="filter-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $filters['q'] }}"
                           placeholder="Cari nama, kode, jabatan, partai, staf..."
                           aria-label="Cari anggota DPRD" maxlength="100">
                </div>
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    Cari
                </button>
            </form>
        </div>

        <div class="member-filter-row">
            <form method="GET" action="{{ route('anggota.index') }}" class="filter-bar" role="search">
                <input type="hidden" name="q" value="{{ $filters['q'] }}">

                <select name="status" class="filter-select" data-auto-submit aria-label="Filter status anggota">
                    <option value="">Semua status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                </select>

                <select name="partai" class="filter-select" data-auto-submit aria-label="Filter partai politik">
                    <option value="">Semua partai</option>
                    @foreach ($parties as $party)
                        <option value="{{ $party }}" @selected($filters['partai'] === $party)>{{ $party }}</option>
                    @endforeach
                </select>

                <select name="kecamatan" class="filter-select" data-auto-submit aria-label="Filter kecamatan asal">
                    <option value="">Semua kecamatan</option>
                    @foreach ($kecamatanOptions as $kecamatan)
                        <option value="{{ $kecamatan }}" @selected($filters['kecamatan'] === $kecamatan)>{{ $kecamatan }}</option>
                    @endforeach
                </select>

                <select name="staff" class="filter-select" data-auto-submit aria-label="Filter penugasan staf">
                    <option value="">Semua penugasan</option>
                    <option value="assigned" @selected($filters['staff'] === 'assigned')>Sudah ada staf</option>
                    <option value="unassigned" @selected($filters['staff'] === 'unassigned')>Belum ditugaskan</option>
                </select>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-emerald btn-sm">
                        <i class="ri-filter-3-line" aria-hidden="true"></i>
                        Terapkan
                    </button>
                    <a href="{{ route('anggota.index') }}" class="filter-reset">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        @if ($members->isEmpty())
            @if ($summary['total'] === 0)
                <x-admin.empty-state
                    icon="ri-team-line"
                    title="Belum ada anggota"
                    description="Belum ada data anggota DPRD yang tercatat di sistem. Tambahkan anggota pertama untuk mulai mengelola profil dan arsipnya."
                >
                    @can('create', App\Models\DprdMember::class)
                        <a href="{{ route('anggota.create') }}" class="btn btn-emerald btn-sm">
                            <i class="ri-add-line" aria-hidden="true"></i>
                            Tambah Anggota Dewan
                        </a>
                    @endcan
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-user-search-line"
                    title="Anggota tidak ditemukan"
                    description="Tidak ada anggota yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('anggota.index') }}" class="btn btn-outline-soft btn-sm">
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
                            <th scope="col">Nama Lengkap &amp; Gelar</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col" class="d-none d-lg-table-cell">Partai Politik / Fraksi</th>
                            <th scope="col" class="d-none d-xl-table-cell">Kecamatan Asal</th>
                            <th scope="col" class="d-none d-xl-table-cell">Staf Pendamping</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi &amp; Arsip</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($members->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    <div class="table-person">
                                        <x-admin.avatar :name="$member->nama_lengkap" :tone="$member->avatar_tone" size="sm" />
                                        <div class="table-person-meta">
                                            <strong>{{ $member->nama_lengkap }}</strong>
                                            <span class="member-code">{{ $member->kode_label }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="cell-muted">{{ $member->jabatan ?: 'Anggota DPRD' }}</span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if ($member->partai)
                                        <span class="party-badge">{{ $member->partai }}</span>
                                    @else
                                        <span class="cell-italic">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    @if ($member->kecamatan)
                                        <span class="cell-muted">{{ $member->kecamatan }}</span>
                                    @else
                                        <span class="cell-italic">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    @if ($member->staff)
                                        <span class="staff-cell">
                                            <i class="ri-user-star-line" aria-hidden="true"></i>
                                            {{ $member->staff->name }}
                                        </span>
                                    @else
                                        <span class="cell-italic">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$member->is_active ? 'Aktif' : 'Nonaktif'" />
                                </td>
                                <td>
                                    <div class="table-actions member-actions">
                                        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-outline-soft btn-sm">
                                            <i class="ri-archive-2-line" aria-hidden="true"></i>
                                            Buka Arsip
                                        </a>

                                        @can('update', $member)
                                            <div class="dropdown action-dropdown">
                                                <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                                        data-bs-boundary="viewport"
                                                        data-bs-popper-config='{"strategy":"fixed"}'
                                                        aria-expanded="false"
                                                        aria-label="Tindakan untuk {{ $member->nama_lengkap }}">
                                                    <i class="ri-more-2-fill" aria-hidden="true"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('anggota.edit', $member) }}">
                                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                                            Edit Anggota
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('anggota.toggle-status', $member) }}"
                                                              id="toggleMember{{ $member->id }}" class="d-none">
                                                            @csrf
                                                            @method('PATCH')
                                                        </form>
                                                        <button type="button" class="dropdown-item"
                                                                data-confirm-action
                                                                data-confirm-title="{{ $member->is_active ? 'Nonaktifkan anggota ini?' : 'Aktifkan anggota ini?' }}"
                                                                data-confirm-message="{{ $member->is_active
                                                                    ? 'Anggota '.$member->nama_lengkap.' akan dinonaktifkan. Riwayat arsip, laporan, dan akun staf tetap tersimpan.'
                                                                    : 'Anggota '.$member->nama_lengkap.' akan diaktifkan kembali dan dapat menerima penugasan staf.' }}"
                                                                data-confirm-label="{{ $member->is_active ? 'Nonaktifkan anggota' : 'Aktifkan anggota' }}"
                                                                data-confirm-form="#toggleMember{{ $member->id }}">
                                                            <i class="{{ $member->is_active ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}" aria-hidden="true"></i>
                                                            {{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('anggota.destroy', $member) }}"
                                                              id="deleteMember{{ $member->id }}" class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        <button type="button" class="dropdown-item text-danger"
                                                                data-confirm-action
                                                                data-confirm-title="Hapus anggota ini?"
                                                                data-confirm-message="Anggota {{ $member->nama_lengkap }} hanya dapat dihapus jika tidak memiliki akun staf, arsip, atau laporan. Gunakan Nonaktifkan untuk mempertahankan seluruh riwayat."
                                                                data-confirm-label="Hapus anggota"
                                                                data-confirm-form="#deleteMember{{ $member->id }}">
                                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                            Hapus Anggota
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $members->firstItem() }}–{{ $members->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> anggota
                    @if ($hasFilters)
                        <span class="text-muted-soft">(difilter dari {{ number_format($summary['total'], 0, ',', '.') }} anggota)</span>
                    @endif
                </p>
                <x-admin.pagination :paginator="$members" />
            </div>
        @endif
    </div>
@endsection
