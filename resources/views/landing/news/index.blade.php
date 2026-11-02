@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Berita & Kegiatan'],
    ],
])

@section('title', 'Berita & Kegiatan')

@section('content')
    @php
        $hasFilters = ($filters['q'] ?? '') !== ''
            || $filters['category'] !== null
            || $filters['status'] !== null;

        $statusClasses = [
            \App\Models\LandingNews::STATUS_PUBLISHED => 'status-success',
            \App\Models\LandingNews::STATUS_DRAFT => 'status-warning',
            \App\Models\LandingNews::STATUS_ARCHIVED => 'status-neutral',
        ];
    @endphp

    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Berita & Kegiatan"
        description="Kelola berita, pengumuman, dan publikasi yang tampil pada halaman informasi publik DPRD Kota Parepare."
    >
        <a href="{{ route('landing.news.create') }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Berita
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-newspaper-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['total'] }}</strong>
                            <span>Total berita</span>
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
                        <span class="mini-stat-icon" style="background: #eef2f0; color: #566860;">
                            <i class="ri-archive-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $stats['archived'] }}</strong>
                            <span>Archived</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="app-card-body">
            <form method="GET" action="{{ route('landing.news.index') }}" class="row g-3" role="search">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="filter-q">Cari judul</label>
                    <input type="search" id="filter-q" name="q" value="{{ $filters['q'] }}"
                           class="form-control" placeholder="Cari judul berita...">
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="filter-category">Kategori</label>
                    <select id="filter-category" name="category" class="form-select" data-auto-submit>
                        <option value="">Semua kategori</option>
                        @foreach (\App\Models\LandingNews::CATEGORIES as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['category'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="filter-status">Status</label>
                    <select id="filter-status" name="status" class="form-select" data-auto-submit>
                        <option value="">Semua status</option>
                        @foreach (\App\Models\LandingNews::STATUSES as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['status'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-emerald btn-sm">
                        <i class="ri-filter-3-line" aria-hidden="true"></i>
                        Terapkan
                    </button>
                    @if ($hasFilters)
                        <a href="{{ route('landing.news.index') }}" class="btn btn-outline-soft btn-sm">
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
                <h2 class="app-card-title">Daftar Berita</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $news->firstItem() ?? 0 }}–{{ $news->lastItem() ?? 0 }}
                    dari {{ number_format($news->total(), 0, ',', '.') }} berita
                </p>
            </div>
        </div>

        @if ($news->isEmpty())
            @if ($hasFilters)
                <x-admin.empty-state
                    icon="ri-search-eye-line"
                    title="Berita tidak ditemukan"
                    description="Tidak ada berita yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('landing.news.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-newspaper-line"
                    title="Belum ada berita"
                    description="Belum ada berita yang tercatat. Tambahkan berita untuk mulai mengisi halaman informasi publik."
                >
                    <a href="{{ route('landing.news.create') }}" class="btn btn-emerald btn-sm">
                        <i class="ri-add-line" aria-hidden="true"></i>
                        Tambah Berita
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">Thumbnail</th>
                            <th scope="col">Judul</th>
                            <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                            <th scope="col" class="d-none d-sm-table-cell">Tanggal Publikasi</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($news as $item)
                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($news->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    @if ($item->thumbnail)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->thumbnail) }}"
                                             alt="Gambar utama {{ $item->title }}"
                                             class="rounded"
                                             style="width: 56px; height: 56px; object-fit: cover;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded"
                                              style="width: 56px; height: 56px; background: #eef2f0; color: #6b7c74;"
                                              aria-hidden="true">
                                            <i class="ri-image-line"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cell-title">{{ $item->title }}</span>
                                    <span class="cell-sub">
                                        {{ $item->excerpt ? \Illuminate\Support\Str::limit($item->excerpt, 60) : 'Tanpa ringkasan' }}
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="cell-muted">
                                        {{ \App\Models\LandingNews::CATEGORIES[$item->category] ?? ucfirst((string) $item->category) }}
                                    </span>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="cell-muted">{{ $item->published_at?->format('d M Y') ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $statusClasses[$item->status] ?? 'status-neutral' }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('landing.news.edit', $item) }}" class="btn btn-outline-soft btn-sm">
                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                            Edit
                                        </a>

                                        <form id="delete-news-{{ $item->id }}" method="POST"
                                              action="{{ route('landing.news.destroy', $item) }}" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <button type="button" class="btn btn-soft-danger btn-sm"
                                                data-confirm-action
                                                data-confirm-form="#delete-news-{{ $item->id }}"
                                                data-confirm-title="Hapus berita ini?"
                                                data-confirm-message="Berita {{ $item->title }} akan dihapus permanen beserta gambar utamanya."
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
                    Menampilkan <strong>{{ $news->firstItem() }}–{{ $news->lastItem() }}</strong>
                    dari <strong>{{ number_format($news->total(), 0, ',', '.') }}</strong> berita
                </p>
                <x-admin.pagination :paginator="$news" />
            </div>
        @endif
    </div>
@endsection
