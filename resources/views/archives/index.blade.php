@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen'],
    ],
])

@section('title', 'Arsip Dokumen')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen arsip"
        title="Arsip Dokumen"
        description="Telusuri, kelola, dan verifikasi dokumen DPRD Kota Parepare dalam satu daftar yang terorganisir."
    >
        @can('create', App\Models\Arsip::class)
            <a href="{{ route('arsip.create') }}" class="btn btn-gold">
                <i class="ri-add-line" aria-hidden="true"></i>
                Tambah Arsip
            </a>
        @endcan
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-archive-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($totalArchives, 0, ',', '.') }}</strong>
                            <span>Total arsip</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-filter-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong>
                            <span>Hasil filter</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-price-tag-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($categories->count(), 0, ',', '.') }}</strong>
                            <span>Kategori tersedia</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('arsip.index') }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari nomor, nama dokumen, atau anggota..."
                       aria-label="Cari arsip" maxlength="100">
            </div>

            <select name="kategori" class="filter-select" data-auto-submit aria-label="Filter kategori arsip">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['kategori'] === (string) $category->id)>
                        {{ $category->nama }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status arsip">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="member" class="filter-select" data-auto-submit aria-label="Filter anggota terkait">
                <option value="">Semua anggota</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((string) $filters['member'] === (string) $member->id)>
                        {{ $member->nama_lengkap }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="tanggal" class="filter-date" value="{{ $filters['tanggal'] }}"
                   aria-label="Filter tanggal arsip" title="Filter tanggal arsip">

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('arsip.index') }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Arsip</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $archives->firstItem() ?? 0 }}–{{ $archives->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} dokumen
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($totalArchives, 0, ',', '.') }} total arsip</span>
        </div>

        @if ($archives->isEmpty())
            @if ($totalArchives === 0)
                <x-admin.empty-state
                    icon="ri-archive-2-line"
                    title="Belum ada arsip"
                    description="Belum ada dokumen arsip yang tercatat. Tambahkan arsip pertama untuk mulai membangun arsip digital DPRD."
                >
                    @can('create', App\Models\Arsip::class)
                        <a href="{{ route('arsip.create') }}" class="btn btn-emerald btn-sm">
                            <i class="ri-add-line" aria-hidden="true"></i>
                            Tambah Arsip
                        </a>
                    @endcan
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Arsip tidak ditemukan"
                    description="Tidak ada dokumen yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('arsip.index') }}" class="btn btn-outline-soft btn-sm">
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
                            <th scope="col">Dokumen</th>
                            <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                            <th scope="col" class="d-none d-sm-table-cell">Tanggal</th>
                            <th scope="col" class="d-none d-lg-table-cell">Anggota Terkait</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($archives as $archive)
                            <tr>
                                <td>
                                    <span class="cell-title" title="{{ $archive->nama }}">{{ $archive->nama ?: 'Arsip tanpa judul' }}</span>
                                    <span class="cell-sub">{{ $archive->nomor ?: 'Tanpa nomor' }}</span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="cell-muted">{{ $archive->kategoriArsip?->nama ?? 'Tanpa kategori' }}</span>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="cell-muted">{{ $archive->tanggal?->locale('id')->translatedFormat('d M Y') ?? '—' }}</span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if ($archive->member)
                                        <div class="table-person">
                                            <x-admin.avatar :name="$archive->member->nama_lengkap" tone="slate" size="sm" />
                                            <div class="table-person-meta">
                                                <strong>{{ $archive->member->nama_lengkap }}</strong>
                                                <span>{{ $archive->member->kode_label }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="cell-italic">Sekretariat DPRD</span>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$archive->status_label" />
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <div class="dropdown action-dropdown">
                                            <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                                    data-bs-boundary="viewport"
                                                    data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false"
                                                    aria-label="Tindakan untuk arsip {{ $archive->nomor }}">
                                                <i class="ri-more-2-fill" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal" data-bs-target="#archiveDetailModal"
                                                            data-nomor="{{ $archive->nomor ?: '—' }}"
                                                            data-nama="{{ $archive->nama ?: 'Arsip tanpa judul' }}"
                                                            data-kategori="{{ $archive->kategoriArsip?->nama ?? 'Tanpa kategori' }}"
                                                            data-tanggal="{{ $archive->tanggal?->locale('id')->translatedFormat('d F Y') ?? '—' }}"
                                                            data-jenis="{{ $archive->jenis_label }}"
                                                            data-anggota="{{ $archive->member?->nama_lengkap ?? 'Sekretariat DPRD' }}"
                                                            data-keterangan="{{ $archive->deskripsi ?: 'Tidak ada keterangan.' }}"
                                                            data-status="{{ $archive->status_label }}">
                                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                                        Lihat Detail
                                                    </button>
                                                </li>
                                                @if ($archive->file_path)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('arsip.download', $archive) }}">
                                                            <i class="ri-download-2-line" aria-hidden="true"></i>
                                                            Unduh Berkas
                                                        </a>
                                                    </li>
                                                @endif
                                                @can('update', $archive)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('arsip.edit', $archive) }}">
                                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                                            Edit Arsip
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('delete', $archive)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('arsip.destroy', $archive) }}"
                                                              id="deleteArchive{{ $archive->id }}" class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        <button type="button" class="dropdown-item text-danger"
                                                                data-confirm-action
                                                                data-confirm-title="Hapus arsip ini?"
                                                                data-confirm-message="Arsip {{ $archive->nomor ?: $archive->nama }} akan dihapus (soft delete). Berkas fisik dan riwayat tetap tersimpan untuk audit."
                                                                data-confirm-label="Hapus arsip"
                                                                data-confirm-form="#deleteArchive{{ $archive->id }}">
                                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                            Hapus Arsip
                                                        </button>
                                                    </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $archives->firstItem() }}–{{ $archives->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> dokumen
                </p>
                <x-admin.pagination :paginator="$archives" />
            </div>
        @endif
    </div>

    {{-- Detail arsip --}}
    <div class="modal fade" id="archiveDetailModal" tabindex="-1" aria-labelledby="archiveDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="archiveDetailTitle">Detail Arsip</h5>
                        <p class="app-card-subtitle mb-0" data-detail-nomor>—</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
                        <div>
                            <div class="page-eyebrow mb-1">Dokumen</div>
                            <h4 class="fs-12 fw-800 mb-1 text-ink" data-detail-nama>—</h4>
                            <span class="text-muted-soft fs-10" data-detail-kategori>—</span>
                        </div>
                        <span class="status-badge status-neutral" data-detail-status>—</span>
                    </div>

                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Nomor arsip</dt>
                            <dd data-detail-nomor>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tanggal</dt>
                            <dd data-detail-tanggal>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jenis</dt>
                            <dd data-detail-jenis>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Anggota terkait</dt>
                            <dd data-detail-anggota>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Keterangan</dt>
                            <dd data-detail-keterangan>—</dd>
                        </div>
                    </dl>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
