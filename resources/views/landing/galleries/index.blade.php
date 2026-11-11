@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Landing Page'],
        ['label' => 'Dokumentasi Kegiatan'],
    ],
])

@section('title', 'Dokumentasi Kegiatan')

@section('content')
    @php
        $hasFilters = $filters['q'] !== ''
            || $filters['status'] !== null
            || $filters['date'] !== null;

        $statusClasses = [
            \App\Models\LandingGallery::STATUS_DRAFT => 'status-neutral',
            \App\Models\LandingGallery::STATUS_PUBLISHED => 'status-success',
            \App\Models\LandingGallery::STATUS_ARCHIVED => 'status-warning',
        ];
    @endphp

    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Dokumentasi Kegiatan"
        description="Kelola album foto dokumentasi kegiatan DPRD Kota Parepare yang tampil pada situs publik. Setiap album dapat memuat banyak foto dengan sampul dan urutan tersendiri."
    >
        <a href="{{ route('landing.galleries.create') }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Buat Album
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-gallery-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($stats['total'], 0, ',', '.') }}</strong>
                            <span>Total album</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-image-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($stats['photos'], 0, ',', '.') }}</strong>
                            <span>Total foto</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($stats['published'], 0, ',', '.') }}</strong>
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
                            <strong>{{ number_format($stats['draft'], 0, ',', '.') }}</strong>
                            <span>Draft</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('landing.galleries.index') }}" class="filter-bar" role="search" data-live-search>
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari judul album dokumentasi..."
                       aria-label="Cari album dokumentasi">
            </div>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status publikasi">
                <option value="">Semua status</option>
                @foreach (\App\Models\LandingGallery::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <input type="date" name="date" value="{{ $filters['date'] }}"
                   class="filter-date" aria-label="Tanggal kegiatan">

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('landing.galleries.index') }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Album Dokumentasi</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $galleries->firstItem() ?? 0 }}–{{ $galleries->lastItem() ?? 0 }}
                    dari {{ number_format($galleries->total(), 0, ',', '.') }} album
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($galleries->total(), 0, ',', '.') }} album</span>
        </div>

        @if ($galleries->isEmpty())
            @if ($hasFilters)
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Album tidak ditemukan"
                    description="Tidak ada album yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('landing.galleries.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-gallery-line"
                    title="Belum ada album dokumentasi"
                    description="Belum ada album foto kegiatan yang tercatat. Buat album pertama untuk mulai menampilkan dokumentasi kegiatan pada situs publik."
                >
                    <a href="{{ route('landing.galleries.create') }}" class="btn btn-emerald btn-sm">
                        <i class="ri-add-line" aria-hidden="true"></i>
                        Buat Album
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">Sampul</th>
                            <th scope="col">Judul Album</th>
                            <th scope="col">Tanggal Kegiatan</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Jumlah Foto</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($galleries as $gallery)
                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($galleries->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    @if ($gallery->cover_image)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($gallery->cover_image) }}"
                                             alt="Sampul album {{ $gallery->title }}"
                                             class="rounded"
                                             style="width: 64px; height: 48px; object-fit: cover; object-position: center;"
                                             loading="lazy">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted"
                                              style="width: 64px; height: 48px;" aria-label="Belum ada foto sampul">
                                            <i class="ri-image-line" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cell-title">{{ $gallery->title }}</span>
                                    @if ($gallery->description)
                                        <span class="cell-sub">
                                            {{ \Illuminate\Support\Str::limit($gallery->description, 60) }}
                                        </span>
                                    @else
                                        <span class="cell-sub">Belum ada deskripsi album</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cell-muted">
                                        {{ $gallery->activity_date?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($gallery->location)
                                        <span class="cell-muted">{{ $gallery->location }}</span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-info">{{ $gallery->photos_count }} foto</span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $statusClasses[$gallery->status] ?? 'status-neutral' }}">
                                        {{ $gallery->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('landing.galleries.edit', $gallery) }}"
                                           class="btn btn-outline-soft btn-sm">
                                            <i class="ri-gallery-line" aria-hidden="true"></i>
                                            Kelola Foto
                                        </a>
                                        <a href="{{ route('landing.galleries.edit', $gallery) }}"
                                           class="btn btn-outline-soft btn-sm">
                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                            Edit
                                        </a>
                                        <button type="button" class="btn btn-soft-danger btn-sm"
                                                data-confirm-action
                                                data-confirm-title="Hapus album dokumentasi?"
                                                data-confirm-message="Album {{ $gallery->title }} beserta seluruh {{ $gallery->photos_count }} fotonya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan."
                                                data-confirm-label="Hapus album"
                                                data-confirm-form="#delete-gallery-{{ $gallery->id }}">
                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                            Hapus
                                        </button>
                                    </div>

                                    <form id="delete-gallery-{{ $gallery->id }}" method="POST"
                                          action="{{ route('landing.galleries.destroy', $gallery) }}" class="d-none">
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
                    Menampilkan <strong>{{ $galleries->firstItem() }}–{{ $galleries->lastItem() }}</strong>
                    dari <strong>{{ number_format($galleries->total(), 0, ',', '.') }}</strong> album
                </p>
                <x-admin.pagination :paginator="$galleries" />
            </div>
        @endif
    </div>
@endsection
