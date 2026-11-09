@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => 'Folder Anggota DPRD'],
    ],
])

@section('title', 'Folder Anggota DPRD')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Folder Anggota DPRD"
        :description="'Pilih anggota DPRD untuk membuka folder arsipnya. Tersedia '.number_format($memberStats['total'], 0, ',', '.').' anggota aktif pada database.'"
    >
        @can('create', App\Models\Arsip::class)
            <a href="{{ route('arsip.create') }}" class="btn btn-gold">
                <i class="ri-add-line" aria-hidden="true"></i>
                Tambah Arsip
            </a>
        @endcan

        <a href="#daftar-dokumen" class="btn btn-outline-soft">
            <i class="ri-file-list-3-line" aria-hidden="true"></i>
            Lihat Semua Dokumen
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-group-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($memberStats['total'], 0, ',', '.') }}</strong>
                            <span>Anggota aktif</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-folder-check-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($memberStats['with_documents'], 0, ',', '.') }}</strong>
                            <span>Sudah memiliki dokumen</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-folder-open-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($memberStats['without_documents'], 0, ',', '.') }}</strong>
                            <span>Belum memiliki dokumen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('arsip.index') }}#folder-anggota" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="member_q" value="{{ $memberFilters['q'] }}"
                       placeholder="Cari nama anggota DPRD..."
                       aria-label="Cari anggota DPRD" maxlength="100">
            </div>

            <select name="member_docs" class="filter-select" data-auto-submit aria-label="Filter ketersediaan dokumen anggota">
                <option value="">Semua ketersediaan dokumen</option>
                <option value="has" @selected($memberFilters['docs'] === 'has')>Sudah memiliki dokumen</option>
                <option value="empty" @selected($memberFilters['docs'] === 'empty')>Belum memiliki dokumen</option>
            </select>

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

    <div class="app-card mb-3" id="folder-anggota">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Folder Anggota</h2>
                <p class="app-card-subtitle">
                    Klik kartu anggota untuk membuka kategori, tahun anggaran, dan folder dokumen miliknya.
                </p>
            </div>
            <span class="status-badge status-neutral">
                {{ number_format($memberFolders->total(), 0, ',', '.') }} anggota
            </span>
        </div>

        <div class="app-card-body">
            @if ($memberFolders->isEmpty())
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Anggota tidak ditemukan"
                    description="Tidak ada anggota aktif yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('arsip.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @else
                <div class="member-folder-grid">
                    @foreach ($memberFolders as $member)
                        <a href="{{ route('anggota.arsip', $member) }}"
                           class="member-folder-card"
                           aria-label="Buka folder arsip {{ $member->nama_lengkap }}">
                            <span class="member-folder-icon" aria-hidden="true">
                                <i class="ri-folder-3-line"></i>
                            </span>

                            <span class="member-folder-copy">
                                <span class="member-folder-name" title="{{ $member->nama_lengkap }}">
                                    {{ $member->nama_lengkap }}
                                </span>
                                <span class="member-folder-meta {{ $member->arsips_count > 0 ? '' : 'is-empty' }}">
                                    @if ($member->arsips_count > 0)
                                        {{ number_format($member->arsips_count, 0, ',', '.') }} dokumen
                                    @else
                                        Belum ada dokumen
                                    @endif
                                </span>
                            </span>

                            <span class="member-folder-chevron" aria-hidden="true">
                                <i class="ri-arrow-right-s-line"></i>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="table-footer">
                    <p class="table-footer-note">
                        Menampilkan <strong>{{ $memberFolders->firstItem() }}–{{ $memberFolders->lastItem() }}</strong>
                        dari <strong>{{ number_format($memberFolders->total(), 0, ',', '.') }}</strong> anggota aktif
                    </p>
                    <x-admin.pagination :paginator="$memberFolders" />
                </div>
            @endif
        </div>
    </div>

    <div class="app-card" id="daftar-dokumen">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Semua Dokumen Arsip</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $archives->firstItem() ?? 0 }}–{{ $archives->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} dokumen lintas anggota.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="status-badge status-neutral">{{ number_format($totalArchives, 0, ',', '.') }} total arsip</span>
                <button type="button" class="btn btn-outline-soft btn-sm"
                        data-bs-toggle="collapse" data-bs-target="#archiveDocumentList"
                        aria-expanded="{{ $hasFilters ? 'true' : 'false' }}"
                        aria-controls="archiveDocumentList">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Filter dokumen
                </button>
            </div>
        </div>

        <div class="collapse {{ $hasFilters ? 'show' : '' }}" id="archiveDocumentList">
            <div class="app-card-body-tight">
                <form method="GET" action="{{ route('arsip.index') }}#daftar-dokumen" class="filter-bar" role="search">
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
