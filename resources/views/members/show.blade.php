@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Anggota DPRD', 'url' => route('anggota.index')],
        ['label' => $member->nama_lengkap],
    ],
])

@section('title', 'Folder '.$member->nama_lengkap)

@section('content')
    <x-admin.page-header
        eyebrow="Folder anggota DPRD"
        title="Pokir &amp; Hasil Reses"
        :description="'Dua submenu utama untuk '.$member->nama_lengkap.'. Pilih salah satu untuk membuka data yang terhubung dengan anggota ini.'"
    >
        <a href="{{ route('anggota.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Anggota DPRD
        </a>

        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-emerald">
            <i class="ri-archive-2-line" aria-hidden="true"></i>
            Semua Kategori Arsip
        </a>
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-file-text-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($totalDocuments, 0, ',', '.') }}</strong>
                            <span>Dokumen terfolder</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
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

                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-chat-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($member->laporan_count, 0, ',', '.') }}</strong>
                            <span>Laporan masyarakat</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #f6eef6; color: #7a3f7a;">
                            <i class="ri-folder-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ number_format($member->arsips_count, 0, ',', '.') }}</strong>
                            <span>Total arsip</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Submenu Pendampingan</h2>
                <p class="app-card-subtitle">Dua folder utama yang tersedia untuk anggota ini.</p>
            </div>
        </div>

        <div class="app-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <article class="member-folder-card">
                        <div class="member-folder-icon member-folder-icon-gold" aria-hidden="true">
                            <i class="ri-lightbulb-flame-line"></i>
                        </div>
                        <div class="member-folder-copy">
                            <div class="member-folder-heading">
                                <h3>Pokir <small>(Pokok-Pokok Pikiran)</small></h3>
                                @if ($pokir)
                                    <span class="archive-count-badge">{{ number_format($pokir->documents_count, 0, ',', '.') }} dokumen</span>
                                @endif
                            </div>
                            <p>Usulan pokok-pokok pikiran {{ $member->nama_lengkap }} untuk pengembangan daerah.</p>
                            <div class="member-folder-meta">
                                @if ($pokir)
                                    <span><i class="ri-calendar-2-line" aria-hidden="true"></i>{{ number_format($pokir->years_count, 0, ',', '.') }} tahun anggaran</span>
                                    @if ($pokir->kode)
                                        <span><i class="ri-price-tag-3-line" aria-hidden="true"></i>{{ $pokir->kode }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="member-folder-actions">
                            @if ($pokir)
                                <a href="{{ route('anggota.arsip.tahun', [$member, $pokir]) }}" class="btn btn-gold">
                                    Buka Pokir
                                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="status-badge status-neutral">Kategori belum tersedia</span>
                            @endif
                        </div>
                    </article>
                </div>

                <div class="col-md-6">
                    <article class="member-folder-card">
                        <div class="member-folder-icon member-folder-icon-emerald" aria-hidden="true">
                            <i class="ri-map-2-line"></i>
                        </div>
                        <div class="member-folder-copy">
                            <div class="member-folder-heading">
                                <h3>Hasil Reses</h3>
                                @if ($reses)
                                    <span class="archive-count-badge">{{ number_format($reses->documents_count, 0, ',', '.') }} dokumen</span>
                                @endif
                            </div>
                            <p>Laporan hasil kegiatan reses {{ $member->nama_lengkap }} beserta aspirasi serta tindak lanjutnya.</p>
                            <div class="member-folder-meta">
                                @if ($reses)
                                    <span><i class="ri-calendar-2-line" aria-hidden="true"></i>{{ number_format($reses->years_count, 0, ',', '.') }} tahun anggaran</span>
                                    @if ($reses->kode)
                                        <span><i class="ri-price-tag-3-line" aria-hidden="true"></i>{{ $reses->kode }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="member-folder-actions">
                            @if ($reses)
                                <a href="{{ route('anggota.arsip.tahun', [$member, $reses]) }}" class="btn btn-emerald">
                                    Buka Hasil Reses
                                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="status-badge status-neutral">Kategori belum tersedia</span>
                            @endif
                        </div>
                    </article>
                </div>
            </div>

            @php
                $otherCategories = $categories
                    ->reject(fn ($category) => in_array(
                        $category->id,
                        array_filter([$pokir?->id, $reses?->id]),
                        true
                    ));
            @endphp

            @if ($otherCategories->isNotEmpty())
                <div class="soft-divider"></div>
                <h3 class="app-card-title mb-2">Kategori Arsip Lainnya</h3>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    @foreach ($otherCategories as $category)
                        <li class="d-flex align-items-center justify-content-between gap-3 rounded-3 border p-3">
                            <span class="min-w-0">
                                <a href="{{ route('anggota.arsip.tahun', [$member, $category]) }}"
                                   class="d-block fw-bold text-decoration-none"
                                   style="font-size: 12.5px; color: #193b32;">
                                    {{ $category->nama }}
                                </a>
                                <span class="cell-muted" style="font-size: 10px;">
                                    {{ number_format($category->documents_count, 0, ',', '.') }} dokumen &middot;
                                    {{ number_format($category->years_count, 0, ',', '.') }} tahun anggaran
                                </span>
                            </span>
                            @if (! $category->is_active)
                                <span class="status-badge status-warning">Nonaktif</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .member-folder-card { display: flex; flex-direction: column; gap: 14px; height: 100%; padding: 20px; border: 1px solid #e5e9e6; border-radius: 16px; background: #fbfcfa; transition: border-color .18s ease, box-shadow .18s ease; }
        .member-folder-card:hover { border-color: #b9cdc3; box-shadow: 0 12px 28px -20px rgba(14, 59, 49, .5); }
        .member-folder-icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; font-size: 22px; }
        .member-folder-icon-gold { background: #fbf3e2; color: #a9853f; }
        .member-folder-icon-emerald { background: #e6f0eb; color: #155043; }
        .member-folder-copy { display: flex; flex-direction: column; gap: 6px; }
        .member-folder-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .member-folder-heading h3 { margin: 0; font-size: 14px; font-weight: 700; color: #193b32; }
        .member-folder-heading h3 small { font-weight: 500; font-size: 10.5px; color: #6d7a73; }
        .member-folder-copy p { margin: 0; font-size: 11.5px; line-height: 1.7; color: #4c5a53; }
        .member-folder-meta { display: flex; gap: 14px; flex-wrap: wrap; font-size: 10px; color: #6d7a73; }
        .member-folder-actions { margin-top: auto; }
    </style>
@endpush
