@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama, 'url' => route('anggota.arsip.tahun', [$member, $category])],
        ['label' => (string) $archiveYear->tahun, 'url' => route('anggota.arsip.folder', [$member, $category, $archiveYear])],
        ['label' => $folder->nama],
    ],
])

@section('title', 'Berkas '.$folder->nama)

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Berkas Arsip"
        :description="'Dokumen pada folder '.$folder->nama.', kategori '.$category->nama.' tahun '.$archiveYear->tahun.'.'"
    >
        <a href="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Folder
        </a>

        @if ($category->is_active)
            @can('create', App\Models\Arsip::class)
                @can('manage-member-archives', $member)
                    <a href="{{ route('anggota.arsip.dokumen.create', [$member, $category, $archiveYear, $folder]) }}"
                       class="btn btn-gold">
                        <i class="ri-upload-2-line" aria-hidden="true"></i>
                        Unggah Berkas
                    </a>
                @endcan
            @endcan
        @endif
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori', 'value' => $category->nama],
        ['icon' => 'ri-calendar-2-line', 'label' => 'Tahun anggaran', 'value' => $archiveYear->tahun],
        ['icon' => 'ri-folder-3-line', 'label' => 'Folder', 'value' => $folder->nama],
        ['icon' => 'ri-file-text-line', 'label' => 'Dokumen', 'value' => number_format($totalDocuments, 0, ',', '.').' berkas'],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    @if (! $category->is_active)
        <div class="archive-note mb-3">
            <i class="ri-information-line" aria-hidden="true"></i>
            <p>Kategori ini sedang nonaktif. Dokumen lama tetap dapat dibuka dan diunduh, tetapi unggahan baru dinonaktifkan.</p>
        </div>
    @endif

    <div class="app-card filter-card">
        <form method="GET"
              action="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
              class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari judul, nomor, nama berkas, atau keterangan..."
                       aria-label="Cari dokumen" maxlength="100">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    Cari
                </button>
                <a href="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
                   class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Berkas Arsip</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $documents->firstItem() ?? 0 }}–{{ $documents->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} dokumen
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($totalDocuments, 0, ',', '.') }} total dokumen</span>
        </div>

        @if ($documents->isEmpty())
            @if ($totalDocuments === 0)
                <x-admin.empty-state
                    icon="ri-file-upload-line"
                    title="Belum ada berkas arsip"
                    description="Unggah dokumen pertama untuk folder ini. Berkas disimpan pada penyimpanan privat dan hanya dapat diunduh oleh pengguna berwenang."
                >
                    @if ($category->is_active)
                        @can('create', App\Models\Arsip::class)
                            @can('manage-member-archives', $member)
                                <a href="{{ route('anggota.arsip.dokumen.create', [$member, $category, $archiveYear, $folder]) }}"
                                   class="btn btn-emerald btn-sm">
                                    <i class="ri-upload-2-line" aria-hidden="true"></i>
                                    Unggah Berkas
                                </a>
                            @endcan
                        @endcan
                    @endif
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Dokumen tidak ditemukan"
                    description="Tidak ada dokumen yang cocok dengan pencarian Anda. Coba kata kunci lain atau reset pencarian."
                >
                    <a href="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
                       class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Reset pencarian
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern archive-document-table">
                    <thead>
                        <tr>
                            <th scope="col" class="archive-col-no">No.</th>
                            <th scope="col">Nama Dokumen</th>
                            <th scope="col">Kategori / Jenis Dokumen</th>
                            <th scope="col">Tanggal Dokumen</th>
                            <th scope="col">Nama Berkas</th>
                            <th scope="col">Tanggal Diunggah</th>
                            <th scope="col">Pengunggah</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            @php
                                $rowNumber = ($documents->currentPage() - 1) * $documents->perPage() + $loop->iteration;
                            @endphp
                            <tr>
                                <td class="archive-col-no">{{ $rowNumber }}</td>
                                <td>
                                    <span class="cell-title" title="{{ $document->nama }}">{{ $document->nama }}</span>
                                    <span class="cell-sub">{{ $document->nomor ?: 'Tanpa nomor' }}</span>
                                </td>
                                <td>
                                    <span class="cell-muted">{{ $category->nama }}</span>
                                    <span class="cell-sub">{{ $document->jenis_label }}</span>
                                </td>
                                <td>
                                    <span class="cell-muted">
                                        {{ $document->tanggal?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="cell-title archive-file-name" title="{{ $document->file_name }}">
                                        {{ $document->file_name ?: 'Tanpa berkas' }}
                                    </span>
                                    <span class="cell-sub">{{ $document->file_size_label }}</span>
                                </td>
                                <td>
                                    <span class="cell-muted">
                                        {{ $document->created_at?->locale('id')->translatedFormat('d M Y H:i') ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="cell-muted">{{ $document->uploader?->name ?? 'Sistem' }}</span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <div class="dropdown action-dropdown">
                                            <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                                    data-bs-boundary="viewport"
                                                    data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false"
                                                    aria-label="Tindakan untuk dokumen {{ $document->nama }}">
                                                <i class="ri-more-2-fill" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal" data-bs-target="#archiveDetailModal"
                                                            data-nomor="{{ $document->nomor ?: '—' }}"
                                                            data-nama="{{ $document->nama }}"
                                                            data-kategori="{{ $category->nama }}"
                                                            data-jenis="{{ $document->jenis_label }}"
                                                            data-tanggal="{{ $document->tanggal?->locale('id')->translatedFormat('d F Y') ?? '—' }}"
                                                            data-file-name="{{ $document->file_name ?: '—' }}"
                                                            data-file-size="{{ $document->file_size_label }}"
                                                            data-uploader="{{ $document->uploader?->name ?? 'Sistem' }}"
                                                            data-uploaded-at="{{ $document->created_at?->locale('id')->translatedFormat('d F Y H:i') ?? '—' }}"
                                                            data-folder="{{ $folder->nama }}"
                                                            data-anggota="{{ $member->nama_lengkap }}"
                                                            data-keterangan="{{ $document->deskripsi ?: 'Tidak ada keterangan.' }}"
                                                            data-status="{{ $document->status_label }}">
                                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                                        Lihat Detail
                                                    </button>
                                                </li>

                                                @if ($document->file_path)
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('anggota.arsip.dokumen.download', [$member, $category, $archiveYear, $folder, $document]) }}">
                                                            <i class="ri-download-2-line" aria-hidden="true"></i>
                                                            Unduh Dokumen
                                                        </a>
                                                    </li>
                                                @endif

                                                @can('update', $document)
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('anggota.arsip.dokumen.edit', [$member, $category, $archiveYear, $folder, $document]) }}">
                                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                                            Edit Metadata
                                                        </a>
                                                    </li>
                                                @endcan

                                                @can('delete', $document)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST"
                                                              action="{{ route('anggota.arsip.dokumen.destroy', [$member, $category, $archiveYear, $folder, $document]) }}"
                                                              id="deleteDocument{{ $document->id }}" class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        <button type="button" class="dropdown-item text-danger"
                                                                data-confirm-action
                                                                data-confirm-title="Hapus dokumen ini?"
                                                                data-confirm-message="Dokumen {{ $document->nama }} akan dihapus (soft delete). Berkas fisik dan riwayat tetap tersimpan untuk audit."
                                                                data-confirm-label="Hapus dokumen"
                                                                data-confirm-form="#deleteDocument{{ $document->id }}">
                                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                            Hapus Dokumen
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
                    Menampilkan <strong>{{ $documents->firstItem() }}–{{ $documents->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> dokumen
                </p>
                <x-admin.pagination :paginator="$documents" />
            </div>
        @endif
    </div>

    {{-- Detail dokumen --}}
    <div class="modal fade" id="archiveDetailModal" tabindex="-1" aria-labelledby="archiveDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="archiveDetailTitle">Detail Dokumen</h5>
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
                            <dt>Nomor dokumen</dt>
                            <dd data-detail-nomor>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jenis dokumen</dt>
                            <dd data-detail-jenis>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tanggal dokumen</dt>
                            <dd data-detail-tanggal>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Nama berkas</dt>
                            <dd data-detail-file-name>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Ukuran berkas</dt>
                            <dd data-detail-file-size>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Folder</dt>
                            <dd data-detail-folder>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Diunggah oleh</dt>
                            <dd data-detail-uploader>—</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tanggal diunggah</dt>
                            <dd data-detail-uploaded-at>—</dd>
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
