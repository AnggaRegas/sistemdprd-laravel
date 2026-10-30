@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama],
    ],
])

@section('title', 'Tahun Anggaran '.$category->nama)

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Pilih Tahun Anggaran"
        :description="'Tahun anggaran untuk kategori '.$category->nama.' milik '.$member->nama_lengkap.'.'"
    >
        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Kategori
        </a>

        @can('manage-member-archives', $member)
            @if ($category->is_active)
                <a href="{{ route('anggota.arsip.tahun.create', [$member, $category]) }}" class="btn btn-gold">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    Tambah Tahun
                </a>
            @endif
        @endcan
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori arsip', 'value' => $category->nama],
        ['icon' => 'ri-file-text-line', 'label' => 'Deskripsi', 'value' => $category->deskripsi ?: 'Belum ada deskripsi'],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Tahun Anggaran</h2>
                <p class="app-card-subtitle">
                    {{ number_format($years->count(), 0, ',', '.') }} tahun tersedia untuk kategori ini.
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($years->count(), 0, ',', '.') }} tahun</span>
        </div>

        @if ($years->isEmpty())
            <x-admin.empty-state
                icon="ri-calendar-event-line"
                title="Belum ada tahun anggaran"
                description="Tambahkan tahun anggaran terlebih dahulu sebelum membuat folder dan mengunggah dokumen."
            >
                @can('manage-member-archives', $member)
                    @if ($category->is_active)
                        <a href="{{ route('anggota.arsip.tahun.create', [$member, $category]) }}" class="btn btn-emerald btn-sm">
                            <i class="ri-add-line" aria-hidden="true"></i>
                            Tambah Tahun Anggaran
                        </a>
                    @endif
                @endcan
            </x-admin.empty-state>
        @else
            <div class="app-card-body">
                <div class="archive-grid archive-grid-compact">
                    @foreach ($years as $year)
                        <article class="archive-card archive-year-card">
                            <div class="archive-card-top">
                                <span class="archive-card-icon archive-card-icon-gold" aria-hidden="true">
                                    <i class="ri-calendar-2-line"></i>
                                </span>

                                <div class="archive-card-copy">
                                    <div class="archive-card-heading">
                                        <h3 class="archive-year-number">{{ $year->tahun }}</h3>
                                        <span class="archive-count-badge">
                                            {{ number_format($year->documents_count, 0, ',', '.') }} dokumen
                                        </span>
                                    </div>
                                    <p>Tahun anggaran {{ $year->tahun }}</p>
                                </div>
                            </div>

                            <div class="archive-card-meta">
                                <span>
                                    <i class="ri-folder-3-line" aria-hidden="true"></i>
                                    {{ number_format($year->folders_count, 0, ',', '.') }} folder
                                </span>
                                <span>
                                    <i class="ri-file-text-line" aria-hidden="true"></i>
                                    {{ number_format($year->documents_count, 0, ',', '.') }} dokumen
                                </span>
                            </div>

                            <div class="archive-card-actions">
                                <a href="{{ route('anggota.arsip.folder', [$member, $category, $year]) }}" class="btn btn-emerald btn-sm">
                                    Pilih Folder
                                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                </a>

                                @can('manage-member-archives', $member)
                                    <a href="{{ route('anggota.arsip.tahun.edit', [$member, $category, $year]) }}"
                                       class="btn btn-outline-soft btn-sm">
                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('anggota.arsip.tahun.destroy', [$member, $category, $year]) }}"
                                          id="deleteYear{{ $year->id }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                    <button type="button" class="btn btn-soft-danger btn-sm"
                                            data-confirm-action
                                            data-confirm-title="Hapus tahun anggaran?"
                                            data-confirm-message="Tahun {{ $year->tahun }} hanya dapat dihapus jika belum memiliki folder."
                                            data-confirm-label="Hapus tahun"
                                            data-confirm-form="#deleteYear{{ $year->id }}">
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
