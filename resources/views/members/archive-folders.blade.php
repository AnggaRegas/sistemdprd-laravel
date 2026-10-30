@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama, 'url' => route('anggota.arsip.tahun', [$member, $category])],
        ['label' => (string) $archiveYear->tahun],
    ],
])

@section('title', 'Folder Dokumen '.$archiveYear->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Folder Dokumen"
        :description="'Folder untuk kategori '.$category->nama.' tahun anggaran '.$archiveYear->tahun.' milik '.$member->nama_lengkap.'.'"
    >
        <a href="{{ route('anggota.arsip.tahun', [$member, $category]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Tahun
        </a>

        @if ($category->is_active)
            @can('manage-member-archives', $member)
                <a href="{{ route('anggota.arsip.folder.create', [$member, $category, $archiveYear]) }}" class="btn btn-gold">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    Tambah Folder
                </a>
            @endcan
        @endif
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori', 'value' => $category->nama],
        ['icon' => 'ri-calendar-2-line', 'label' => 'Tahun anggaran', 'value' => $archiveYear->tahun],
        ['icon' => 'ri-folder-3-line', 'label' => 'Total folder', 'value' => number_format($totalFolders, 0, ',', '.').' folder'],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    @if (! $category->is_active)
        <div class="archive-note mb-3">
            <i class="ri-information-line" aria-hidden="true"></i>
            <p>Kategori ini sedang nonaktif. Folder dan dokumen lama tetap dapat dibuka, tetapi penambahan folder baru dinonaktifkan.</p>
        </div>
    @endif

    <div class="app-card filter-card">
        <form method="GET"
              action="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}"
              class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari nama atau keterangan folder..."
                       aria-label="Cari folder" maxlength="100">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    Cari
                </button>
                <a href="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Daftar Folder</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $folders->firstItem() ?? 0 }}–{{ $folders->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} folder
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($totalFolders, 0, ',', '.') }} total folder</span>
        </div>

        @if ($folders->isEmpty())
            @if ($totalFolders === 0)
                <x-admin.empty-state
                    icon="ri-folder-open-line"
                    title="Belum ada folder dokumen"
                    description="Buat folder untuk mengelompokkan dokumen berdasarkan kegiatan atau keperluan pengarsipan."
                >
                    @if ($category->is_active)
                        @can('manage-member-archives', $member)
                            <a href="{{ route('anggota.arsip.folder.create', [$member, $category, $archiveYear]) }}" class="btn btn-emerald btn-sm">
                                <i class="ri-add-line" aria-hidden="true"></i>
                                Tambah Folder
                            </a>
                        @endcan
                    @endif
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Folder tidak ditemukan"
                    description="Tidak ada folder yang cocok dengan pencarian Anda. Coba kata kunci lain atau reset pencarian."
                >
                    <a href="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Reset pencarian
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="app-card-body">
                <div class="archive-grid">
                    @foreach ($folders as $folder)
                        <article class="archive-card archive-folder-card">
                            <div class="archive-card-top">
                                <span class="archive-card-icon archive-card-icon-blue" aria-hidden="true">
                                    <i class="ri-folder-3-line"></i>
                                </span>

                                <div class="archive-card-copy">
                                    <div class="archive-card-heading">
                                        <h3>{{ $folder->nama }}</h3>
                                        <span class="archive-count-badge">
                                            {{ number_format($folder->documents_count, 0, ',', '.') }} berkas
                                        </span>
                                    </div>
                                    <p>{{ $folder->deskripsi ?: 'Belum ada keterangan folder.' }}</p>
                                </div>
                            </div>

                            <div class="archive-card-meta">
                                <span>
                                    <i class="ri-file-text-line" aria-hidden="true"></i>
                                    {{ number_format($folder->documents_count, 0, ',', '.') }} dokumen
                                </span>
                                <span>
                                    <i class="ri-time-line" aria-hidden="true"></i>
                                    Diperbarui {{ $folder->updated_at?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                                </span>
                            </div>

                            <div class="archive-card-actions">
                                <a href="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
                                   class="btn btn-emerald btn-sm">
                                    Buka Folder
                                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                </a>

                                @can('manage-member-archives', $member)
                                    <a href="{{ route('anggota.arsip.folder.edit', [$member, $category, $archiveYear, $folder]) }}"
                                       class="btn btn-outline-soft btn-sm">
                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('anggota.arsip.folder.destroy', [$member, $category, $archiveYear, $folder]) }}"
                                          id="deleteFolder{{ $folder->id }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                    <button type="button" class="btn btn-soft-danger btn-sm"
                                            data-confirm-action
                                            data-confirm-title="Hapus folder ini?"
                                            data-confirm-message="Folder {{ $folder->nama }} hanya dapat dihapus jika sudah tidak berisi dokumen."
                                            data-confirm-label="Hapus folder"
                                            data-confirm-form="#deleteFolder{{ $folder->id }}">
                                        <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $folders->firstItem() }}–{{ $folders->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> folder
                </p>
                <x-admin.pagination :paginator="$folders" />
            </div>
        @endif
    </div>
@endsection
