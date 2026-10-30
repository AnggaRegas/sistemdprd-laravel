@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap],
    ],
])

@section('title', 'Arsip '.$member->nama_lengkap)

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Kategori Arsip Dokumen"
        :description="'Pilih kategori arsip untuk '.$member->nama_lengkap.', lalu telusuri tahun anggaran, folder, dan berkas dokumennya.'"
    >
        <a href="{{ route('anggota.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Anggota
        </a>

        @can('create', App\Models\KategoriArsip::class)
            <a href="{{ route('anggota.arsip.kategori.create', $member) }}" class="btn btn-gold">
                <i class="ri-add-line" aria-hidden="true"></i>
                Tambah Kategori
            </a>
        @endcan
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-file-text-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($totalDocuments, 0, ',', '.') }}</strong>
                            <span>Total dokumen</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-calendar-2-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($totalYears, 0, ',', '.') }}</strong>
                            <span>Tahun anggaran</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-folder-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($totalFolders, 0, ',', '.') }}</strong>
                            <span>Folder dokumen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="archive-note mb-3">
        <i class="ri-information-line" aria-hidden="true"></i>
        <p>
            Arsip umum yang belum dimasukkan ke folder tahunan tetap dapat dikelola melalui
            <a href="{{ route('arsip.index') }}">Arsip Dokumen</a>.
        </p>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Kategori Arsip Dokumen</h2>
                <p class="app-card-subtitle">
                    {{ number_format($categories->count(), 0, ',', '.') }} kategori tersedia untuk anggota ini.
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($totalDocuments, 0, ',', '.') }} dokumen</span>
        </div>

        @if ($categories->isEmpty())
            <x-admin.empty-state
                icon="ri-folder-open-line"
                title="Belum ada kategori arsip"
                description="Kategori arsip belum tersedia. Administrator dapat menambahkan kategori baru untuk mulai menata dokumen anggota."
            >
                @can('create', App\Models\KategoriArsip::class)
                    <a href="{{ route('anggota.arsip.kategori.create', $member) }}" class="btn btn-emerald btn-sm">
                        <i class="ri-add-line" aria-hidden="true"></i>
                        Tambah Kategori
                    </a>
                @endcan
            </x-admin.empty-state>
        @else
            <div class="app-card-body">
                <div class="archive-grid">
                    @foreach ($categories as $category)
                        <article class="archive-card">
                            <div class="archive-card-top">
                                <span class="archive-card-icon" aria-hidden="true">
                                    <i class="ri-folder-2-line"></i>
                                </span>

                                <div class="archive-card-copy">
                                    <div class="archive-card-heading">
                                        <h3>{{ $category->nama }}</h3>
                                        <span class="archive-count-badge">
                                            {{ number_format($category->documents_count, 0, ',', '.') }} dokumen
                                        </span>
                                    </div>
                                    <p>{{ $category->deskripsi ?: 'Belum ada deskripsi kategori.' }}</p>
                                </div>
                            </div>

                            <div class="archive-card-meta">
                                <span>
                                    <i class="ri-calendar-2-line" aria-hidden="true"></i>
                                    {{ number_format($category->years_count, 0, ',', '.') }} tahun anggaran
                                </span>

                                @if ($category->kode)
                                    <span>
                                        <i class="ri-price-tag-3-line" aria-hidden="true"></i>
                                        {{ $category->kode }}
                                    </span>
                                @endif

                                @if (! $category->is_active)
                                    <span class="status-badge status-warning">Nonaktif</span>
                                @endif
                            </div>

                            <div class="archive-card-actions">
                                <a href="{{ route('anggota.arsip.tahun', [$member, $category]) }}" class="btn btn-emerald btn-sm">
                                    Pilih Tahun
                                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                </a>

                                @can('update', $category)
                                    <a href="{{ route('anggota.arsip.kategori.edit', [$member, $category]) }}"
                                       class="btn btn-outline-soft btn-sm">
                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                        Edit
                                    </a>
                                @endcan

                                @can('delete', $category)
                                    <form method="POST"
                                          action="{{ route('anggota.arsip.kategori.destroy', [$member, $category]) }}"
                                          id="deleteCategory{{ $category->id }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                    <button type="button" class="btn btn-soft-danger btn-sm"
                                            data-confirm-action
                                            data-confirm-title="Hapus kategori ini?"
                                            data-confirm-message="Kategori {{ $category->nama }} hanya dapat dihapus jika belum memiliki tahun anggaran atau dokumen."
                                            data-confirm-label="Hapus kategori"
                                            data-confirm-form="#deleteCategory{{ $category->id }}">
                                        <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
