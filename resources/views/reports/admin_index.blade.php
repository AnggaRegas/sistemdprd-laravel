@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => $selectedMember
        ? [
            ['label' => 'Beranda', 'url' => route('dashboard')],
            ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
            ['label' => $selectedMember->nama_lengkap],
        ]
        : (
            $reports !== null
                ? [
                    ['label' => 'Beranda', 'url' => route('dashboard')],
                    ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
                    ['label' => 'Semua Aspirasi'],
                ]
                : [
                    ['label' => 'Beranda', 'url' => route('dashboard')],
                    ['label' => 'Laporan Masyarakat'],
                ]
        ),
])

@section('title', $selectedMember ? 'Laporan '.$selectedMember->nama_lengkap : ($reports !== null ? 'Semua Aspirasi Masyarakat' : 'Laporan Masyarakat'))

@section('content')
    @php
        $similarityErrorReportId = $errors->similarity->any()
            ? (int) old('decided_report_id')
            : null;
    @endphp

    <x-admin.page-header
        eyebrow="Aspirasi masyarakat"
        :title="$selectedMember ? 'Laporan untuk '.$selectedMember->nama_lengkap : ($reports !== null ? 'Semua Aspirasi Masyarakat' : 'Laporan Masyarakat')"
        :description="$selectedMember
            ? 'Daftar aspirasi yang ditujukan kepada anggota DPRD ini.'
            : 'Kelola, pantau, dan kelompokkan aspirasi masyarakat yang masuk untuk ditindaklanjuti.'"
    >
        <a href="{{ route('laporan.index', ['anggota' => 'semua']) }}" class="btn btn-emerald">
            <i class="ri-list-check-2" aria-hidden="true"></i>
            Lihat Semua Aspirasi Masyarakat
        </a>
        @if ($reports !== null)
            <a href="{{ route('laporan.index') }}" class="btn btn-outline-soft">
                <i class="ri-folder-user-line" aria-hidden="true"></i>
                Folder Anggota
            </a>
        @endif
    </x-admin.page-header>

    @if ($reports === null)
        <div class="app-card filter-card mb-3">
            <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar" role="search">
                <div class="filter-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Cari nama anggota DPRD" aria-label="Cari anggota DPRD">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-emerald">Cari anggota</button>
                    @if ($hasFolderQuery)
                        <a href="{{ route('laporan.index') }}" class="filter-reset">Hapus pencarian</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Folder Anggota DPRD</h2>
                    <p class="app-card-subtitle">{{ $members->count() }} anggota aktif · folder dibuat dari data database</p>
                </div>
            </div>

            @if ($members->isEmpty())
                <x-admin.empty-state
                    icon="ri-team-line"
                    title="Belum ada anggota DPRD"
                    description="Folder akan tampil otomatis setelah data anggota DPRD tersedia."
                />
            @else
                <div class="app-card-body">
                    <div class="row g-3">
                        @foreach ($members as $member)
                            @php
                                $unreadCount = (int) ($memberUnreadCounts->get($member->id) ?? 0);
                            @endphp
                            <div class="col-sm-6 col-xl-4">
                                <a href="{{ route('laporan.index', ['anggota' => $member->id]) }}"
                                   class="d-flex h-100 align-items-center gap-3 rounded-4 border p-3 text-decoration-none report-folder"
                                   data-member-id="{{ $member->id }}"
                                   style="border-color: #e5e9e6; background: #fbfcfa;">
                                    <span class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                          style="width: 46px; height: 46px; background: #e6f0eb; color: #155043;">
                                        <i class="ri-folder-user-line" aria-hidden="true" style="font-size: 22px;"></i>
                                    </span>
                                    <span class="min-w-0 flex-grow-1">
                                        <strong class="d-block text-truncate" style="font-size: 12.5px; color: #193b32;">
                                            {{ $member->nama_lengkap }}
                                        </strong>
                                        <span class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                            <span class="text-muted" style="font-size: 10px;">
                                                {{ $member->laporan_count > 0
                                                    ? number_format($member->laporan_count, 0, ',', '.').' laporan'
                                                    : 'Belum ada laporan' }}
                                            </span>
                                            <span class="member-unread-slot" data-member-unread-slot="{{ $member->id }}">
                                                @if ($unreadCount > 0)
                                                    <span class="member-unread-badge" aria-label="{{ $unreadCount }} aspirasi baru belum dibaca">
                                                        <span class="member-unread-dot" aria-hidden="true"></span>
                                                        {{ $unreadCount }} BARU
                                                    </span>
                                                @endif
                                            </span>
                                        </span>
                                    </span>
                                    <i class="ri-arrow-right-s-line flex-shrink-0" aria-hidden="true" style="color: #a17f3e;"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="app-card filter-card mb-3">
            <form method="GET" action="{{ route('laporan.index') }}" class="p-3" role="search">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="filterAnggota" class="form-label mb-1" style="font-size: 11.5px; font-weight: 700; color: #405149;">
                            Anggota DPRD
                        </label>
                        <select id="filterAnggota" name="anggota" class="form-select form-select-sm filter-select" data-auto-submit aria-label="Filter Anggota DPRD">
                            <option value="semua" @selected($selectedMemberParam === 'semua' || (!$selectedMember && $reports !== null))>Semua Anggota DPRD</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected((string) $selectedMemberParam === (string) $member->id)>
                                    {{ $member->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="filterKemiripan" class="form-label mb-1" style="font-size: 11.5px; font-weight: 700; color: #405149;">
                            Kemiripan Aspirasi
                        </label>
                        <select id="filterKemiripan" name="kemiripan" class="form-select form-select-sm filter-select" data-auto-submit aria-label="Filter Kemiripan Aspirasi">
                            <option value="" @selected($filters['kemiripan'] === '')>Semua Aspirasi</option>
                            <option value="memiliki_kemiripan" @selected($filters['kemiripan'] === 'memiliki_kemiripan')>Aspirasi Memiliki Kemiripan</option>
                            <option value="tidak_memiliki_kemiripan" @selected($filters['kemiripan'] === 'tidak_memiliki_kemiripan')>Aspirasi Tidak Memiliki Kemiripan</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="filterStatusKamus" class="form-label mb-1" style="font-size: 11.5px; font-weight: 700; color: #405149;">
                            Status Kamus
                        </label>
                        <select id="filterStatusKamus" name="status_kamus" class="form-select form-select-sm filter-select" data-auto-submit aria-label="Filter Status Kamus">
                            <option value="" @selected($filters['status_kamus'] === '')>Semua Status Kamus</option>
                            <option value="sesuai" @selected($filters['status_kamus'] === 'sesuai')>Sesuai Kamus</option>
                            <option value="tidak_ditemukan" @selected($filters['status_kamus'] === 'tidak_ditemukan')>Tidak Ditemukan di Kamus</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="ri-search-line" aria-hidden="true"></i>
                            </span>
                            <input type="search" name="q" value="{{ $searchQuery }}" class="form-control border-start-0" placeholder="Cari nama pelapor atau isi laporan" aria-label="Cari laporan">
                        </div>
                    </div>

                    <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-emerald btn-sm flex-grow-1">
                            <i class="ri-filter-3-line" aria-hidden="true"></i> Cari laporan
                        </button>
                        @if ($hasFolderQuery)
                            <a href="{{ route('laporan.index', ['anggota' => 'semua']) }}" class="btn btn-outline-soft btn-sm text-nowrap">
                                Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Daftar Laporan</h2>
                    <p class="app-card-subtitle">{{ number_format($filteredTotal, 0, ',', '.') }} laporan ditemukan</p>
                </div>
            </div>

            @if ($reports->isEmpty())
                <x-admin.empty-state
                    icon="ri-chat-3-line"
                    title="Belum ada laporan"
                    :description="$selectedMember ? 'Aspirasi untuk anggota DPRD ini akan tampil di sini.' : 'Tidak ada aspirasi masyarakat yang memenuhi kriteria filter.'"
                />
            @else
                <div class="table-responsive">
                    <table class="table-modern report-simple-table">
                        <thead>
                            <tr>
                                <th scope="col">Nama pelapor</th>
                                <th scope="col" class="d-none d-md-table-cell">Anggota DPRD tujuan</th>
                                <th scope="col">Isi aspirasi</th>
                                <th scope="col" class="text-center">Kemiripan</th>
                                <th scope="col">Status</th>
                                <th scope="col">Status Kamus</th>
                                <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $matches = $similarityMatches->get($report->id, []);
                                    $matchCount = count($matches);
                                    $panel = $similarityPanels->get($report->id);
                                    $group = $report->similarityGroup;
                                    $isMain = ($group && (int) $group->main_laporan_id === (int) $report->id)
                                        || $report->similarity_status === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_UTAMA;
                                    $isMerged = $report->is_duplicate_merged;
                                    $isSeparated = $report->similarity_status === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH;
                                    $isUnread = isset($unreadReportLookup[$report->id]);
                                    $groupMembersCount = $group?->members->count() ?? 0;
                                    $hasSimilarity = ! $isSeparated
                                        && ($isMerged || $matchCount > 0 || $groupMembersCount >= 2);
                                    $canCompare = $panel && $matchCount > 0;
                                    $isDitugaskan = in_array($report->status, [
                                        \App\Models\LaporanMasyarakat::STATUS_DITUGASKAN,
                                        \App\Models\LaporanMasyarakat::STATUS_DIPROSES,
                                        \App\Models\LaporanMasyarakat::STATUS_SELESAI,
                                    ], true) || $report->assigned_at !== null || $report->penugasans()->exists();
                                @endphp
                                <tr class="{{ $isUnread ? 'is-unread-report' : '' }}">
                                    <td>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <strong>{{ $report->pelapor_nama }}</strong>
                                            @if ($isUnread)
                                                <span class="report-row-unread-badge" title="Aspirasi baru belum dibaca">BARU</span>
                                            @endif
                                        </div>
                                        <span class="cell-sub d-block d-md-none">{{ $report->kecamatan_label }} · {{ \App\Support\PortalData::date($report->tanggal_laporan?->toDateString()) }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $report->member?->nama_lengkap ?: '—' }}</span></td>
                                    <td class="report-summary-cell">
                                        {{ $report->ringkasan }}
                                    </td>
                                    <td class="report-similarity-cell">
                                        @if ($isMain)
                                            <span class="status-badge status-success">Aspirasi Utama</span>
                                            @if ($hasSimilarity)
                                                <div class="mt-2">
                                                    <span class="similarity-badge">
                                                        <i class="ri-team-line" aria-hidden="true"></i>
                                                        Ada Kemiripan
                                                    </span>
                                                </div>
                                            @endif
                                            @if ($canCompare)
                                                <button type="button"
                                                        class="btn btn-outline-soft btn-sm mt-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#similarityModal{{ $report->id }}">
                                                    <i class="ri-arrow-left-right-line" aria-hidden="true"></i>
                                                    Bandingkan
                                                </button>
                                            @endif
                                        @elseif ($hasSimilarity)
                                            <span class="similarity-badge">
                                                <i class="ri-team-line" aria-hidden="true"></i>
                                                Ada Kemiripan
                                            </span>
                                        @else
                                            <span class="cell-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $isDitugaskan ? 'info' : 'warning' }}">
                                            {{ $isDitugaskan ? 'Ditugaskan' : 'Menunggu Penugasan' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($report->kamus_status === \App\Models\LaporanKamusHasil::STATUS_SESUAI)
                                            <span class="status-badge status-success">Sesuai Kamus</span>
                                        @else
                                            <span class="status-badge status-neutral">Tidak Ditemukan di Kamus</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <a href="{{ route('laporan.show', ['laporan' => $report, 'return_to' => $returnUrl]) }}"
                                               class="btn btn-outline-soft report-view-button">
                                                Lihat Detail
                                            </a>
                                            <button type="button"
                                                    class="btn btn-soft-danger report-delete-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteReportModal{{ $report->id }}"
                                                    aria-label="Hapus aspirasi {{ $report->nomor_tiket }}"
                                                    title="Hapus aspirasi">
                                                <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <p class="table-footer-note">Menampilkan <strong>{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</strong> dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> laporan</p>
                    <x-admin.pagination :paginator="$reports" />
                </div>
            @endif
        </div>

        @foreach ($reports as $report)
            @php
                $panel = $similarityPanels->get($report->id);
                $modalMatches = $similarityMatches->get($report->id, []);
                $showSimilarityError = $errors->similarity->any()
                    && (! $similarityErrorReportId || $similarityErrorReportId === (int) $report->id);
            @endphp

            @if ($panel && $panel['total'] > 0 && $modalMatches !== [])
                @php
                    $currentCard = $panel['current'];
                    $currentLabel = $panel['current_is_newest'] ? 'Aspirasi Baru' : 'Aspirasi yang Dibandingkan';
                    $highestPercent = (int) round($panel['highest_score'] * 100);
                @endphp
                <div class="modal fade similarity-modal" id="similarityModal{{ $report->id }}" tabindex="-1"
                     aria-labelledby="similarityModalLabel{{ $report->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable similarity-modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header similarity-modal-header">
                                <div class="similarity-modal-heading">
                                    <span class="similarity-modal-icon" aria-hidden="true">
                                        <i class="ri-arrow-left-right-line"></i>
                                    </span>
                                    <div>
                                        <h5 class="modal-title" id="similarityModalLabel{{ $report->id }}">
                                            Perbandingan Aspirasi
                                        </h5>
                                        <p class="cell-sub mb-0 mt-1">
                                            {{ $report->nomor_tiket }} · {{ $report->member?->nama_lengkap ?: 'Anggota DPRD' }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>

                            <form method="POST"
                                  action="{{ route('laporan.kemiripan.decide', $report) }}"
                                  class="similarity-decision-form"
                                  data-current-id="{{ $currentCard['id'] }}"
                                  data-current-ticket="{{ $currentCard['nomor_tiket'] }}">
                                @csrf
                                <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                                <input type="hidden" name="decided_report_id" value="{{ $report->id }}">

                                <div class="modal-body similarity-modal-body">
                                    @if ($showSimilarityError)
                                        <div class="alert alert-danger app-alert mb-3" role="alert">
                                            <i class="ri-error-warning-line" aria-hidden="true"></i>
                                            <div>
                                                <strong>Keputusan belum dapat disimpan.</strong>
                                                <ul class="mb-0 mt-1 ps-3">
                                                    @foreach ($errors->similarity->all() as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="similarity-alert" role="note">
                                        <span class="similarity-alert-icon" aria-hidden="true">
                                            <i class="ri-alert-line"></i>
                                        </span>
                                        <div class="similarity-alert-body">
                                            <strong>Ditemukan {{ number_format($panel['total'] + 1, 0, ',', '.') }} aspirasi serupa</strong>
                                            <span class="similarity-alert-score">
                                                Tingkat kemiripan tertinggi:
                                                <b>{{ number_format($highestPercent, 0, ',', '.') }}%</b>
                                            </span>
                                            <p>
                                                Pilih satu aspirasi sebagai aspirasi utama, simpan aspirasi baru
                                                sebagai arsip terkait, atau hapus aspirasi yang memang tidak diperlukan.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="similarity-compare-grid">
                                        <section class="similarity-pane">
                                            <div class="similarity-pane-head">
                                                <span class="similarity-pane-title">{{ $currentLabel }}</span>
                                                <span class="similarity-pane-count">1</span>
                                            </div>

                                            <label class="similarity-card similarity-card-current">
                                                <input class="similarity-radio"
                                                       type="radio"
                                                       name="main_laporan_id"
                                                       value="{{ $currentCard['id'] }}"
                                                       id="similarityMain{{ $report->id }}Current"
                                                       @checked((int) old('main_laporan_id') === (int) $currentCard['id'])>

                                                <span class="similarity-card-body">
                                                    <span class="similarity-card-top">
                                                        <span class="similarity-tag similarity-tag-new">{{ $currentLabel }}</span>
                                                        @if ($currentCard['is_main'])
                                                            <span class="similarity-tag similarity-tag-main">Aspirasi Utama Saat Ini</span>
                                                        @elseif ($currentCard['similarity_status'] === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN)
                                                            <span class="similarity-tag similarity-tag-related">Arsip Terkait</span>
                                                        @elseif ($currentCard['similarity_status'] === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH)
                                                            <span class="similarity-tag similarity-tag-neutral">Diproses Terpisah</span>
                                                        @endif
                                                    </span>

                                                    <strong class="similarity-card-title">{{ $currentCard['ringkasan'] }}</strong>

                                                    <span class="similarity-card-meta">
                                                        <span><i class="ri-user-line" aria-hidden="true"></i> {{ $currentCard['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</span>
                                                        <span><i class="ri-calendar-2-line" aria-hidden="true"></i> {{ \App\Support\PortalData::date($currentCard['tanggal']) }}</span>
                                                        <span><i class="ri-price-tag-3-line" aria-hidden="true"></i> {{ $currentCard['kategori_label'] ?: 'Kategori belum diisi' }}</span>
                                                        <span><i class="ri-flag-line" aria-hidden="true"></i> {{ $currentCard['status_label'] ?: 'Status tidak diketahui' }}</span>
                                                    </span>

                                                    <span class="similarity-card-content">{{ \Illuminate\Support\Str::limit($currentCard['isi'], 1200) }}</span>

                                                    <span class="similarity-card-location">
                                                        <i class="ri-map-pin-line" aria-hidden="true"></i>
                                                        {{ $currentCard['pelapor_alamat'] ?: 'Alamat belum diisi' }}
                                                        @if ($currentCard['kecamatan'])
                                                            · {{ $currentCard['kecamatan'] }}
                                                        @endif
                                                    </span>

                                                    <span class="similarity-selected-note">
                                                        <i class="ri-checkbox-circle-fill" aria-hidden="true"></i>
                                                        Akan Dijadikan Aspirasi Utama
                                                    </span>
                                                </span>
                                            </label>
                                        </section>

                                        <section class="similarity-pane similarity-pane-matches">
                                            <div class="similarity-pane-head">
                                                <span class="similarity-pane-title">Aspirasi yang Mirip</span>
                                                <span class="similarity-pane-count">{{ number_format($panel['total'], 0, ',', '.') }}</span>
                                            </div>

                                            <div class="similarity-match-list">
                                                @foreach ($panel['matches'] as $match)
                                                    @php
                                                        $matchPercent = (float) ($match['score'] !== null ? $match['score'] * 100 : 0);
                                                        $scoreTone = $matchPercent >= 90 ? 'is-very-high' : 'is-high';
                                                    @endphp

                                                    <label class="similarity-card similarity-card-match">
                                                        <input class="similarity-radio"
                                                               type="radio"
                                                               name="main_laporan_id"
                                                               value="{{ $match['id'] }}"
                                                               id="similarityMain{{ $report->id }}Match{{ $match['id'] }}"
                                                               @checked((int) old('main_laporan_id') === (int) $match['id'])>

                                                        <span class="similarity-card-body">
                                                            <span class="similarity-card-top">
                                                                <span class="similarity-score-pill {{ $scoreTone }}">
                                                                    {{ number_format($matchPercent, 1, ',', '.') }}%
                                                                </span>
                                                                @if ($match['is_main'])
                                                                    <span class="similarity-tag similarity-tag-main">Aspirasi Utama</span>
                                                                @elseif ($match['similarity_status'] === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN)
                                                                    <span class="similarity-tag similarity-tag-related">Arsip Terkait</span>
                                                                @elseif ($match['similarity_status'] === \App\Models\LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH)
                                                                    <span class="similarity-tag similarity-tag-neutral">Diproses Terpisah</span>
                                                                @endif
                                                            </span>

                                                            <strong class="similarity-card-title">{{ $match['ringkasan'] }}</strong>

                                                            <span class="similarity-card-meta">
                                                                <span><i class="ri-user-line" aria-hidden="true"></i> {{ $match['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</span>
                                                                <span><i class="ri-calendar-2-line" aria-hidden="true"></i> {{ \App\Support\PortalData::date($match['tanggal']) }}</span>
                                                                <span><i class="ri-price-tag-3-line" aria-hidden="true"></i> {{ $match['kategori_label'] ?: 'Kategori belum diisi' }}</span>
                                                                <span><i class="ri-flag-line" aria-hidden="true"></i> {{ $match['status_label'] ?: 'Status tidak diketahui' }}</span>
                                                            </span>

                                                            <span class="similarity-card-content">{{ \Illuminate\Support\Str::limit($match['isi'], 360) }}</span>

                                                            <span class="similarity-card-location">
                                                                <i class="ri-map-pin-line" aria-hidden="true"></i>
                                                                {{ $match['pelapor_alamat'] ?: 'Alamat belum diisi' }}
                                                                @if ($match['kecamatan'])
                                                                    · {{ $match['kecamatan'] }}
                                                                @endif
                                                            </span>

                                                            <span class="similarity-selected-note">
                                                                <i class="ri-checkbox-circle-fill" aria-hidden="true"></i>
                                                                Akan Dijadikan Aspirasi Utama
                                                            </span>

                                                            <button type="submit"
                                                                    form="deleteMatch{{ $report->id }}-{{ $match['id'] }}"
                                                                    class="btn btn-soft-danger btn-sm similarity-delete-button"
                                                                    onclick="return confirm('Hapus aspirasi {{ $match['nomor_tiket'] }}? Relasi kelompok akan dibersihkan dan jika aspirasi ini utama, sistem memilih pengganti otomatis.');">
                                                                <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                                Hapus Aspirasi Ini
                                                            </button>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </section>
                                    </div>

                                    <p class="similarity-selection-hint" data-similarity-hint>
                                        Pilih satu aspirasi terlebih dahulu untuk mengaktifkan tombol tindakan.
                                    </p>
                                </div>

                                <div class="modal-footer similarity-modal-footer">
                                    <p class="similarity-safety-note">
                                        <i class="ri-shield-check-line" aria-hidden="true"></i>
                                        Aspirasi lainnya tetap tersimpan sebagai arsip terkait dan dapat ditinjau kembali.
                                        Penghapusan hanya terjadi bila Anda menekan tombol hapus dan mengonfirmasinya.
                                    </p>

                                    <div class="similarity-modal-actions">
                                        <button type="submit"
                                                name="action"
                                                value="arsip_terkait"
                                                class="btn btn-outline-soft similarity-action-secondary"
                                                data-sim-action="arsip"
                                                disabled>
                                            <i class="ri-archive-line" aria-hidden="true"></i>
                                            Simpan sebagai Arsip Terkait
                                        </button>
                                        <button type="submit"
                                                name="action"
                                                value="pilih_utama"
                                                class="btn btn-gold similarity-action-primary"
                                                data-sim-action="utama"
                                                disabled>
                                            <i class="ri-check-double-line" aria-hidden="true"></i>
                                            Pilih sebagai Aspirasi Utama
                                        </button>
                                    </div>
                                </div>
                            </form>

                            @foreach ($panel['matches'] as $match)
                                <form id="deleteMatch{{ $report->id }}-{{ $match['id'] }}"
                                      method="POST"
                                      action="{{ route('laporan.destroy', $match['id']) }}"
                                      class="d-none">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="modal fade" id="deleteReportModal{{ $report->id }}" tabindex="-1"
                 aria-labelledby="deleteReportModalLabel{{ $report->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content delete-report-modal-content">
                        <form method="POST" action="{{ route('laporan.destroy', $report) }}" class="delete-report-form">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                            <div class="modal-header border-0 pb-0">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="delete-report-icon" aria-hidden="true">
                                        <i class="ri-delete-bin-6-line"></i>
                                    </span>
                                    <div>
                                        <h5 class="modal-title mb-0" id="deleteReportModalLabel{{ $report->id }}">
                                            Hapus aspirasi?
                                        </h5>
                                        <span class="cell-sub">{{ $report->nomor_tiket }}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>

                            <div class="modal-body">
                                <div class="delete-report-summary">
                                    <div class="delete-report-row">
                                        <span class="delete-report-label">Pelapor</span>
                                        <span class="delete-report-value">{{ $report->pelapor_nama ?: 'Tidak tercatat' }}</span>
                                    </div>
                                    <div class="delete-report-row">
                                        <span class="delete-report-label">Tanggal</span>
                                        <span class="delete-report-value">{{ \App\Support\PortalData::date($report->tanggal_laporan?->toDateString()) }}</span>
                                    </div>
                                    <div class="delete-report-row">
                                        <span class="delete-report-label">Kecamatan</span>
                                        <span class="delete-report-value">{{ $report->kecamatan_label }}</span>
                                    </div>
                                    <div class="delete-report-row">
                                        <span class="delete-report-label">Status</span>
                                        <span class="delete-report-value">{{ $report->status_label }}</span>
                                    </div>
                                    <div class="delete-report-row">
                                        <span class="delete-report-label">Isi aspirasi</span>
                                        <span class="delete-report-value">{{ $report->ringkasan }}</span>
                                    </div>
                                </div>

                                @if ($report->similarityGroup && $report->similarityGroup->main_laporan_id === $report->id)
                                    <div class="alert alert-warning app-alert mt-3 mb-0" role="note">
                                        <i class="ri-alert-line" aria-hidden="true"></i>
                                        <div>
                                            Aspirasi ini adalah <strong>aspirasi utama</strong> pada kelompok
                                            {{ $report->similarityGroup->kode }}. Bila dihapus, sistem otomatis
                                            mempromosikan anggota lain sebagai aspirasi utama pengganti. Bila tidak
                                            ada anggota tersisa, kelompok dibubarkan.
                                        </div>
                                    </div>
                                @elseif ($report->similarityGroup)
                                    <div class="alert alert-info app-alert mt-3 mb-0" role="note">
                                        <i class="ri-information-line" aria-hidden="true"></i>
                                        <div>
                                            Aspirasi ini tergabung pada kelompok {{ $report->similarityGroup->kode }}.
                                            Keanggotaan dan hasil perhitungan kemiripan terkait akan dibersihkan.
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-3">
                                    <label for="deleteReason{{ $report->id }}" class="form-label">Alasan penghapusan <span class="text-muted">(opsional)</span></label>
                                    <textarea id="deleteReason{{ $report->id }}"
                                              name="alasan"
                                              rows="2"
                                              maxlength="500"
                                              class="form-control"
                                              placeholder="Catatan internal mengapa aspirasi ini dihapus"></textarea>
                                </div>

                                <p class="delete-report-note">
                                    <i class="ri-shield-check-line" aria-hidden="true"></i>
                                    Aspirasi tidak ditampilkan lagi pada daftar dan tindak lanjut. Data tetap
                                    tersimpan sebagai arsip terhapus (soft delete) dan tercatat pada log aktivitas.
                                </p>
                            </div>

                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-soft-danger">
                                    <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                    Hapus Aspirasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('styles')
    <style>
        .report-folder { transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
        .report-folder:hover { border-color: #b9cdc3 !important; box-shadow: 0 12px 28px -20px rgba(14, 59, 49, .5); transform: translateY(-1px); }
        .report-simple-table { min-width: 1080px; }
        .report-simple-table th, .report-simple-table td { padding: 15px 14px; font-size: 13px; vertical-align: middle; }
        .report-summary-cell { min-width: 220px; max-width: 420px; line-height: 1.6; }
        .report-similarity-cell { min-width: 230px; max-width: 300px; text-align: center; }
        .similarity-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border: 1px solid #fee2e2;
            border-radius: 999px;
            background-color: #fef2f2;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.4;
            white-space: nowrap;
            vertical-align: middle;
        }
        .similarity-badge i { font-size: 12px; line-height: 1; }
        .report-view-button { min-height: 40px; white-space: nowrap; }
        .report-delete-button { display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 40px; padding: 0; border-radius: 12px; }
        .report-delete-button i { font-size: 17px; }
        .delete-report-modal-content { border: 0; border-radius: 22px; overflow: hidden; box-shadow: 0 32px 70px -34px rgba(15, 49, 40, .5); }
        .delete-report-icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 15px; background: #fdeef0; color: #b03643; font-size: 22px; flex-shrink: 0; }
        .delete-report-summary { display: flex; flex-direction: column; gap: 8px; padding: 14px 16px; border: 1px solid #ece7dd; border-radius: 16px; background: #fbfcfa; }
        .delete-report-row { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 10px; font-size: 12.5px; line-height: 1.6; }
        .delete-report-label { font-size: 10.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #8a948f; padding-top: 1px; }
        .delete-report-value { color: #33443e; font-weight: 600; word-break: break-word; }
        .delete-report-note { display: flex; gap: 8px; align-items: flex-start; margin: 14px 0 0; font-size: 11px; line-height: 1.6; color: #7b8781; }
        .delete-report-note i { margin-top: 1px; color: #155043; font-size: 15px; }
        .similarity-modal-dialog { max-width: 1120px; }
        .similarity-modal .modal-content { border: 0; border-radius: 24px; overflow: hidden; box-shadow: 0 32px 80px -36px rgba(15, 49, 40, .55); }
        .similarity-modal-header { align-items: center; gap: 16px; padding: 20px 24px; border-bottom: 1px solid #edf1ee; }
        .similarity-modal-heading { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .similarity-modal-icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 16px; background: #e6f0eb; color: #155043; font-size: 22px; flex-shrink: 0; }
        .similarity-modal-header .modal-title { font-size: 18px; font-weight: 800; letter-spacing: -.2px; color: #193b32; }
        .similarity-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 22px 24px 18px; background: #fff; }
        .similarity-decision-form { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; }
        .similarity-alert { display: flex; gap: 14px; align-items: flex-start; padding: 16px 18px; margin-bottom: 20px; border-radius: 18px; border: 1px solid #f0d3d6; background: #fdf3f4; color: #742b33; }
        .similarity-alert-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: #fff; color: #b03643; font-size: 19px; flex-shrink: 0; box-shadow: inset 0 0 0 1px #f3dadd; }
        .similarity-alert-body { display: block; min-width: 0; }
        .similarity-alert-body strong { display: block; font-size: 13.5px; font-weight: 800; color: #71242d; }
        .similarity-alert-score { display: inline-flex; gap: 5px; margin-top: 2px; font-size: 12px; color: #8b4a51; }
        .similarity-alert-score b { color: #a22f3c; }
        .similarity-alert-body p { margin: 6px 0 0; font-size: 11.5px; line-height: 1.65; color: #8a545a; }
        .similarity-compare-grid { display: grid; grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr); gap: 18px; align-items: start; }
        .similarity-pane { min-width: 0; }
        .similarity-pane-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; padding: 0 2px; }
        .similarity-pane-title { font-size: 10.5px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #7b8781; }
        .similarity-pane-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 8px; border-radius: 999px; background: #eef3ef; color: #155043; font-size: 11px; font-weight: 800; }
        .similarity-match-list { display: flex; flex-direction: column; gap: 10px; max-height: min(52vh, 520px); padding-right: 4px; overflow-y: auto; scrollbar-width: thin; }
        .similarity-match-list::-webkit-scrollbar { width: 8px; }
        .similarity-match-list::-webkit-scrollbar-thumb { border-radius: 999px; background: #d8e3dd; }
        .similarity-card { position: relative; display: flex; gap: 12px; align-items: flex-start; margin: 0; padding: 16px; border: 1px solid #e5e9e6; border-radius: 18px; background: #fff; cursor: pointer; transition: border-color .16s ease, box-shadow .16s ease, background .16s ease; }
        .similarity-card:hover { border-color: #b9cdc3; box-shadow: 0 12px 26px -24px rgba(14, 59, 49, .6); }
        .similarity-card.is-selected,
        .similarity-card:has(.similarity-radio:checked) { border-color: #155043; background: #f3f8f5; box-shadow: 0 0 0 3px rgba(21, 80, 67, .09); }
        .similarity-card-current { min-height: 100%; }
        .similarity-card-body { display: block; min-width: 0; flex-grow: 1; }
        .similarity-radio { margin-top: 4px; width: 16px; height: 16px; flex-shrink: 0; accent-color: #155043; cursor: pointer; }
        .similarity-card-top { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 6px; }
        .similarity-tag { display: inline-flex; align-items: center; border-radius: 999px; padding: 3px 9px; font-size: 9.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .similarity-tag-new { background: #e6f0eb; color: #155043; }
        .similarity-tag-main { background: #e7f4ec; color: #14663f; }
        .similarity-tag-related { background: #f8eddd; color: #8a5b12; }
        .similarity-tag-neutral { background: #eef1f0; color: #5f6d67; }
        .similarity-card-title { display: block; font-size: 13.5px; line-height: 1.45; font-weight: 800; color: #193b32; }
        .similarity-card-meta { display: flex; flex-wrap: wrap; gap: 5px 14px; margin: 7px 0 8px; font-size: 10.5px; color: #6b7873; }
        .similarity-card-meta i { color: #90a19a; }
        .similarity-card-content { display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden; font-size: 11.5px; line-height: 1.72; color: #4a5a54; white-space: pre-line; }
        .similarity-card-current .similarity-card-content { -webkit-line-clamp: 10; }
        .similarity-card-location { display: block; margin-top: 9px; padding-top: 9px; border-top: 1px dashed #e5ebe7; font-size: 10.5px; line-height: 1.6; color: #6b7873; }
        .similarity-card-location i { color: #a17f3e; }
        .similarity-score-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 800; letter-spacing: .02em; }
        .similarity-score-pill.is-very-high { background: #e2f4e9; color: #14663f; }
        .similarity-score-pill.is-high { background: #fdf4dc; color: #8a6514; }
        .similarity-meters { display: flex; flex-direction: column; gap: 5px; margin-top: 9px; }
        .similarity-meter-row { display: grid; grid-template-columns: 78px minmax(0, 1fr) 40px; align-items: center; gap: 8px; font-size: 10px; color: #6b7873; }
        .similarity-meter-label { font-weight: 700; }
        .similarity-meter { display: block; height: 6px; border-radius: 999px; background: #edf2ef; overflow: hidden; }
        .similarity-meter-fill { display: block; height: 100%; border-radius: inherit; background: #155043; }
        .similarity-meter-fill.is-location { background: linear-gradient(90deg, #155043, #3f8b72); }
        .similarity-meter-fill.is-problem { background: linear-gradient(90deg, #b98b2f, #e2bd6a); }
        .similarity-meter-row strong { text-align: right; font-size: 10px; color: #33443e; }
        .similarity-selected-note { display: none; align-items: center; gap: 5px; margin-top: 10px; font-size: 10.5px; font-weight: 800; color: #155043; }
        .similarity-card.is-selected .similarity-selected-note,
        .similarity-card:has(.similarity-radio:checked) .similarity-selected-note { display: inline-flex; }
        .similarity-delete-button { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 4px 10px; font-size: 10.5px; font-weight: 700; }
        .similarity-selection-hint { margin: 14px 0 0; font-size: 11px; color: #7b8781; text-align: center; }
        .similarity-selection-hint.is-ready { color: #155043; font-weight: 700; }
        .similarity-modal-footer { display: block; padding: 16px 24px 20px; border-top: 1px solid #edf1ee; background: #fbfcfa; }
        .similarity-safety-note { display: flex; gap: 8px; align-items: flex-start; margin: 0 0 13px; font-size: 10.5px; line-height: 1.6; color: #6b7873; }
        .similarity-safety-note i { margin-top: 1px; color: #155043; font-size: 15px; }
        .similarity-modal-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; }
        .similarity-action-secondary,
        .similarity-action-primary { min-height: 44px; padding-inline: 20px; font-weight: 800; }
        .similarity-action-secondary:disabled,
        .similarity-action-primary:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
        .member-unread-badge { display: inline-flex; align-items: center; gap: 4px; padding: 1.5px 7px; border-radius: 999px; background: #dc2626; color: #ffffff; font-size: 9px; font-weight: 800; letter-spacing: .04em; line-height: 1.4; white-space: nowrap; box-shadow: 0 2px 6px -1px rgba(220, 38, 38, .45); }
        .member-unread-dot { width: 5px; height: 5px; border-radius: 50%; background: #ffffff; display: inline-block; flex-shrink: 0; }
        .report-row-unread-badge { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 999px; background: #dc2626; color: #ffffff; font-size: 9.5px; font-weight: 800; letter-spacing: .04em; line-height: 1.35; white-space: nowrap; }
        .is-unread-report { background: #fffdfa; }
        @media (max-width: 991.98px) {
            .similarity-compare-grid { grid-template-columns: minmax(0, 1fr); }
            .similarity-match-list { max-height: 360px; }
        }
        @media (max-width: 767.98px) {
            .similarity-modal-dialog { margin: 10px; }
            .similarity-modal-header { padding: 16px 18px; }
            .similarity-modal-icon { width: 40px; height: 40px; border-radius: 13px; font-size: 19px; }
            .similarity-modal-body { padding: 16px 18px; }
            .similarity-alert { padding: 13px 14px; }
            .similarity-match-list { max-height: 320px; }
            .similarity-modal-footer { padding: 14px 18px 18px; }
            .similarity-modal-actions { flex-direction: column-reverse; }
            .similarity-modal-actions .btn { width: 100%; justify-content: center; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var errorReportId = @json($similarityErrorReportId);

            if (errorReportId && window.bootstrap) {
                var errorModal = document.getElementById('similarityModal' + errorReportId);

                if (errorModal && errorModal.querySelector('.alert-danger')) {
                    window.bootstrap.Modal.getOrCreateInstance(errorModal).show();
                }
            }

            document.querySelectorAll('.similarity-decision-form').forEach(function (form) {
                var currentId = form.dataset.currentId;
                var currentTicket = form.dataset.currentTicket || 'aspirasi baru';
                var archiveButton = form.querySelector('[data-sim-action="arsip"]');
                var mainButton = form.querySelector('[data-sim-action="utama"]');
                var hint = form.querySelector('[data-similarity-hint]');
                var radios = Array.prototype.slice.call(
                    form.querySelectorAll('input[name="main_laporan_id"]')
                );

                function selectedRadio() {
                    return form.querySelector('input[name="main_laporan_id"]:checked');
                }

                function updateSelection() {
                    var checked = selectedRadio();
                    var hasSelection = Boolean(checked);

                    form.querySelectorAll('.similarity-card').forEach(function (card) {
                        var radio = card.querySelector('input[name="main_laporan_id"]');
                        card.classList.toggle('is-selected', Boolean(radio && radio.checked));
                    });

                    if (mainButton) {
                        mainButton.disabled = !hasSelection;
                    }

                    if (archiveButton) {
                        archiveButton.disabled = !hasSelection || checked.value === currentId;
                        archiveButton.title = hasSelection && checked.value === currentId
                            ? 'Pilih salah satu aspirasi lama sebagai aspirasi utama untuk menyimpan arsip terkait.'
                            : '';
                    }

                    if (hint) {
                        hint.textContent = !hasSelection
                            ? 'Pilih satu aspirasi terlebih dahulu untuk mengaktifkan tombol tindakan.'
                            : (checked.value === currentId
                                ? 'Aspirasi baru dipilih sebagai calon aspirasi utama.'
                                : 'Aspirasi lama dipilih sebagai calon aspirasi utama.');
                        hint.classList.toggle('is-ready', hasSelection);
                    }
                }

                radios.forEach(function (radio) {
                    radio.addEventListener('change', updateSelection);
                });

                form.addEventListener('submit', function (event) {
                    var submitter = event.submitter
                        || form.querySelector('button[type="submit"]:focus');

                    if (!submitter || !submitter.dataset.simAction) {
                        event.preventDefault();
                        return;
                    }

                    var checked = selectedRadio();

                    if (!checked) {
                        event.preventDefault();
                        return;
                    }

                    if (submitter.dataset.simAction === 'arsip') {
                        if (checked.value === currentId) {
                            event.preventDefault();
                            return;
                        }

                        if (!window.confirm(
                            'Simpan ' + currentTicket + ' sebagai arsip terkait? '
                            + 'Isi laporan, identitas pelapor, tanggal, dan lampiran tetap tersimpan, '
                            + 'dan hubungan dengan aspirasi utama akan dicatat.'
                        )) {
                            event.preventDefault();
                        }

                        return;
                    }

                    if (!window.confirm(
                        'Tetapkan aspirasi terpilih sebagai aspirasi utama? '
                        + 'Aspirasi lain dalam daftar akan ditautkan sebagai arsip terkait dan tidak dihapus. '
                        + 'Proses persetujuan dan penugasan tetap dilakukan melalui alur yang sudah ada.'
                    )) {
                        event.preventDefault();
                    }
                });

                updateSelection();
            });

            @if ($reports === null)
                (function () {
                    var pollUrl = @json(route('laporan.unread-counts'));
                    if (!pollUrl) return;

                    function pollUnread() {
                        if (document.hidden) return;

                        fetch(pollUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function (res) { return res.ok ? res.json() : null; })
                        .then(function (data) {
                            if (!data) return;

                            document.querySelectorAll('[data-member-unread-slot]').forEach(function (slot) {
                                var memberId = slot.getAttribute('data-member-unread-slot');
                                var count = (data.members && data.members[memberId]) ? Number(data.members[memberId]) : 0;
                                if (count > 0) {
                                    slot.innerHTML = '<span class="member-unread-badge" aria-label="' + count + ' aspirasi baru belum dibaca">'
                                        + '<span class="member-unread-dot" aria-hidden="true"></span>'
                                        + count + ' BARU</span>';
                                } else {
                                    slot.innerHTML = '';
                                }
                            });

                            var sidebarLink = document.querySelector('.sidebar-nav a[href*="/laporan"]');
                            if (sidebarLink) {
                                var badge = sidebarLink.querySelector('.sidebar-badge');
                                if (data.total > 0) {
                                    if (!badge) {
                                        badge = document.createElement('span');
                                        badge.className = 'sidebar-badge';
                                        sidebarLink.appendChild(badge);
                                    }
                                    badge.textContent = data.total;
                                } else if (badge) {
                                    badge.remove();
                                }
                            }
                        })
                        .catch(function () {
                            /* Ignore transient polling errors */
                        });
                    }

                    var pollInterval = setInterval(pollUnread, 12000);
                    window.addEventListener('beforeunload', function () {
                        clearInterval(pollInterval);
                    });
                })();
            @endif
        });
    </script>
@endpush
