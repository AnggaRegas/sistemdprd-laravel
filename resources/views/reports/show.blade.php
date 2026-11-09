@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat', 'url' => $returnUrl],
        ['label' => $report->nomor_tiket],
    ],
])

@section('title', 'Detail Laporan '.$report->nomor_tiket)

@php
    $closedStatuses = [
        \App\Models\LaporanMasyarakat::STATUS_SELESAI,
        \App\Models\LaporanMasyarakat::STATUS_DITOLAK,
    ];
    $isClosed = in_array($report->status, $closedStatuses, true);
    $needApproval = $isAdministrator && ! $report->disetujui_at && ! $isClosed;
@endphp

@section('content')
    <x-admin.page-header
        eyebrow="Detail laporan masyarakat"
        :title="$report->ringkasan"
        :description="'Tiket '.$report->nomor_tiket.' · '.$report->kecamatan_label"
    >
        <a href="{{ $returnUrl }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Daftar Laporan
        </a>

        @if ($needApproval)
            <a href="#persetujuan" class="btn btn-gold">
                <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                Verifikasi &amp; Disposisi
            </a>
        @endif
    </x-admin.page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Laporan</h2>
                        <p class="app-card-subtitle">Data pelapor dan status penanganan.</p>
                    </div>
                    <x-admin.status-badge :status="$report->status_label" />
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Nomor tiket</dt>
                                    <dd>{{ $report->nomor_tiket }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal laporan</dt>
                                    <dd>{{ \App\Support\PortalData::date($report->tanggal_laporan?->toDateString(), true) }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal masuk</dt>
                                    <dd>{{ \App\Support\PortalData::dateTime($report->created_at) }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Kecamatan</dt>
                                    <dd>{{ $report->kecamatan_label }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Status verifikasi</dt>
                                    <dd>
                                        @if ($report->disetujui_at)
                                            Disetujui{{ $report->approvedBy ? ' oleh '.$report->approvedBy->name : '' }}
                                        @elseif ($isClosed)
                                            Diverifikasi ({{ $report->status_label }})
                                        @else
                                            Menunggu verifikasi administrator
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Pelapor</dt>
                                    <dd>{{ $report->pelapor_nama }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Kontak</dt>
                                    <dd>{{ $report->pelapor_kontak ?: '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Alamat</dt>
                                    <dd class="text-break">{{ $report->pelapor_alamat ?: '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Anggota DPRD tujuan</dt>
                                    <dd>{{ $report->member?->nama_lengkap ?: 'Belum ditentukan' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Staf pendamping</dt>
                                    <dd>
                                        @if ($report->assignee)
                                            {{ $report->assignee->name }} <span class="cell-muted">(disposisi otomatis)</span>
                                        @elseif ($isAdministrator && $autoStaff)
                                            {{ $autoStaff->name }} <span class="cell-muted">(siap didisposisikan)</span>
                                        @elseif ($isAdministrator)
                                            Belum ada staf aktif untuk anggota ini
                                        @else
                                            Belum ditugaskan
                                        @endif
                                    </dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Ditutup pada</dt>
                                    <dd>{{ $report->ditutup_at ? \App\Support\PortalData::dateTime($report->ditutup_at) : '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Isi Aspirasi</h2>
                        <p class="app-card-subtitle">Uraian yang dikirimkan oleh masyarakat.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="report-quote">{!! nl2br(e($report->isi ?: $report->ringkasan)) !!}</div>

                    @if ($report->catatan_penyelesaian)
                        <div class="soft-divider"></div>
                        <h3 class="app-card-title mb-2">Catatan Tindak Lanjut</h3>
                        <p class="mb-0 text-muted" style="font-size: 11.5px; line-height: 1.7;">
                            {!! nl2br(e($report->catatan_penyelesaian)) !!}
                        </p>
                    @endif
                </div>
            </div>

            @include('reports.partials.kamus-panel')

            @php
                $activeAssignment = $report->penugasans
                    ->first(static fn ($assignment) => $assignment->released_at === null);
            @endphp

            @if ($activeAssignment)
                <div class="app-card mb-3">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Penugasan Tindak Lanjut</h2>
                            <p class="app-card-subtitle">Status penyelesaian tugas staf pendamping untuk aspirasi ini.</p>
                        </div>
                        <x-admin.status-badge :status="$activeAssignment->status_label" />
                    </div>

                    <div class="app-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Staff pendamping</dt>
                                        <dd>{{ $activeAssignment->user?->name ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Tanggal penugasan</dt>
                                        <dd>{{ \App\Support\PortalData::dateTime($activeAssignment->assigned_at) }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Tanggal selesai</dt>
                                        <dd>
                                            @if ($activeAssignment->completed_at)
                                                {{ \App\Support\PortalData::dateTime($activeAssignment->completed_at) }}
                                                <span class="cell-sub d-block">{{ $activeAssignment->completedBy?->name ?? $activeAssignment->user?->name }}</span>
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        @if ($activeAssignment->isSelesai())
                            <div class="soft-divider"></div>
                            <h3 class="app-card-title mb-2">Hasil Tindak Lanjut</h3>
                            <p style="font-size: 11.5px; line-height: 1.8;">{!! nl2br(e($activeAssignment->hasil_tindak_lanjut ?: '—')) !!}</p>

                            @if ($activeAssignment->keterangan_tambahan)
                                <h3 class="app-card-title mb-2">Keterangan Tambahan</h3>
                                <p style="font-size: 11.5px; line-height: 1.8;">{!! nl2br(e($activeAssignment->keterangan_tambahan)) !!}</p>
                            @endif

                            @if ($activeAssignment->hasBukti())
                                <a href="{{ route('penugasan.bukti', $activeAssignment) }}" class="app-card-link">
                                    <i class="ri-download-2-line" aria-hidden="true"></i>
                                    {{ $activeAssignment->bukti_nama ?: 'Unduh bukti tindak lanjut' }}
                                    <span class="cell-sub ms-1">{{ $activeAssignment->bukti_size_label }}</span>
                                </a>
                            @else
                                <p class="cell-muted mb-0">Tidak ada dokumen pendukung yang dilampirkan.</p>
                            @endif
                        @endif

                        <div class="soft-divider"></div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('penugasan.show', $activeAssignment) }}" class="btn btn-outline-soft btn-sm">
                                <i class="ri-eye-line" aria-hidden="true"></i>
                                Detail Penugasan
                            </a>

                            @if ($isStaff && (int) $activeAssignment->user_id === (int) auth()->id() && ! $activeAssignment->isSelesai())
                                @if ($activeAssignment->status === \App\Models\LaporanPenugasan::STATUS_DITUGASKAN)
                                    <form method="POST" action="{{ route('penugasan.mulai', $activeAssignment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-soft btn-sm">
                                            <i class="ri-play-line" aria-hidden="true"></i>
                                            Mulai Tindak Lanjut
                                        </button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-emerald btn-sm"
                                        data-complete-assignment
                                        data-complete-action="{{ route('penugasan.selesai', $activeAssignment) }}"
                                        data-complete-id="{{ $activeAssignment->id }}"
                                        data-complete-title="{{ $report->ringkasan }}">
                                    <i class="ri-check-double-line" aria-hidden="true"></i>
                                    Tandai Selesai
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Lampiran</h2>
                        <p class="app-card-subtitle">Berkas pendukung disimpan secara privat, hanya dapat dibuka dari halaman ini.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    @if ($report->hasLampiran())
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-flex align-items-center justify-content-center rounded-3" style="width: 42px; height: 42px; background: #e6f0eb; color: #155043;">
                                    <i class="ri-attachment-2" aria-hidden="true" style="font-size: 20px;"></i>
                                </span>
                                <div>
                                    <strong class="d-block" style="font-size: 11.5px;">{{ $report->lampiran_nama ?: 'Lampiran' }}</strong>
                                    <span class="text-muted" style="font-size: 9.5px;">
                                        {{ $report->lampiran_size_label }}
                                        @if ($report->lampiran_mime)
                                            &middot; {{ $report->lampiran_mime }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('laporan.lampiran.lihat', $report) }}" target="_blank" rel="noopener" class="btn btn-outline-soft btn-sm">
                                    <i class="ri-eye-line" aria-hidden="true"></i>
                                    Lihat Lampiran
                                </a>
                                <a href="{{ route('laporan.lampiran', $report) }}" class="btn btn-emerald btn-sm">
                                    <i class="ri-download-2-line" aria-hidden="true"></i>
                                    Unduh Lampiran
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="mb-0 text-muted" style="font-size: 11px;">Tidak ada lampiran pada laporan ini.</p>
                    @endif
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Riwayat Disposisi</h2>
                        <p class="app-card-subtitle">Catatan penugasan &amp; perpindahan staf pendamping untuk laporan ini.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    @if ($report->penugasans->isEmpty())
                        <x-admin.empty-state
                            icon="ri-user-follow-line"
                            title="Belum ada disposisi"
                            description="Riwayat akan muncul setelah laporan disetujui dan didisposisikan kepada staf pendamping."
                        />
                    @else
                        <ul class="timeline">
                            @foreach ($report->penugasans as $assignment)
                                <li class="timeline-item {{ $assignment->released_at ? 'is-done' : 'is-active' }}">
                                    <strong>{{ $assignment->user?->name ?: 'Staf tidak ditemukan' }}</strong>
                                    <p>
                                        Ditugaskan {{ \App\Support\PortalData::dateTime($assignment->assigned_at) }}
                                        @if ($assignment->assignedBy)
                                            oleh {{ $assignment->assignedBy->name }}
                                        @endif
                                    </p>
                                    <span>
                                        @if ($assignment->released_at)
                                            Dilepas {{ \App\Support\PortalData::dateTime($assignment->released_at) }}
                                        @else
                                            Penugasan aktif
                                        @endif
                                    </span>
                                    @if ($assignment->catatan)
                                        <p class="mt-1">{{ $assignment->catatan }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            @if ($isAdministrator)
                <div class="app-card mb-3" id="persetujuan">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Verifikasi &amp; Disposisi</h2>
                            <p class="app-card-subtitle">Persetujuan otomatis meneruskan laporan ke staf pendamping anggota yang dituju.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($errors->approval->any())
                            <div class="alert alert-danger app-alert mb-3" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Laporan belum dapat disetujui.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->approval->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if ($report->disetujui_at)
                            <div class="alert alert-success app-alert mb-3" role="status">
                                <i class="ri-check-double-line" aria-hidden="true"></i>
                                <div>
                                    Disetujui{{ $report->approvedBy ? ' oleh '.$report->approvedBy->name : '' }}
                                    pada {{ \App\Support\PortalData::dateTime($report->disetujui_at) }}.
                                </div>
                            </div>
                        @endif

                        @if ($report->dprd_member_id && $autoStaff)
                            <p class="mb-3" style="font-size: 11.5px; line-height: 1.7;">
                                Disposisi otomatis akan menugaskan
                                <strong>{{ $autoStaff->name }}</strong> (staf pendamping aktif anggota
                                <strong>{{ $report->member?->nama_lengkap }}</strong>).
                            </p>
                        @elseif ($report->dprd_member_id)
                            <div class="alert alert-warning app-alert mb-3" role="alert">
                                <i class="ri-alert-line" aria-hidden="true"></i>
                                <div>
                                    Anggota DPRD tujuan belum memiliki staf pendamping aktif. Laporan tetap dapat diverifikasi nanti setelah akun staf tersedia.
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning app-alert mb-3" role="alert">
                                <i class="ri-alert-line" aria-hidden="true"></i>
                                <div>Aspirasi ini belum menentukan anggota DPRD tujuan sehingga tidak dapat didisposisikan.</div>
                            </div>
                        @endif

                        @if (! $isClosed)
                            <form method="POST" action="{{ route('laporan.persetujuan', $report) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                                <div class="mb-3">
                                    <label class="form-label" for="approvalNote">Catatan disposisi</label>
                                    <textarea id="approvalNote" name="catatan" rows="3" maxlength="1000" class="form-control" placeholder="Arahan singkat untuk staf pendamping (opsional).">{{ old('catatan') }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-gold w-100">
                                    <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                                    {{ $report->disetujui_at ? 'Disposisi Ulang' : 'Setujui &amp; Disposisikan' }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info app-alert mb-0" role="status">
                                <i class="ri-lock-2-line" aria-hidden="true"></i>
                                <div>Laporan sudah ditutup ({{ $report->status_label }}) sehingga tidak dapat disetujui lagi.</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="app-card" id="status">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Status &amp; Tindak Lanjut</h2>
                            <p class="app-card-subtitle">Hanya transisi status yang sah yang ditampilkan.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($errors->status->any())
                            <div class="alert alert-danger app-alert mb-3" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Status belum dapat disimpan.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->status->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('laporan.status.update', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                            <div class="mb-3">
                                <label class="form-label" for="reportStatus">Status Laporan</label>
                                <select id="reportStatus" name="status" class="form-select">
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="reportStatusNote">Catatan Tindak Lanjut</label>
                                <textarea id="reportStatusNote" name="catatan" rows="4" maxlength="2000" class="form-control" placeholder="Tuliskan hasil koordinasi atau alasan perubahan status.">{{ old('catatan', $report->catatan_penyelesaian) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-emerald w-100">
                                <i class="ri-check-double-line" aria-hidden="true"></i>
                                Simpan Status
                            </button>
                        </form>
                    </div>
                </div>

                <div class="app-card mt-3" id="analisis">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Analisis Kemiripan</h2>
                            <p class="app-card-subtitle">Riwayat deteksi duplikasi aspirasi ini.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        <p class="cell-muted" style="font-size: 11px; line-height: 1.8;">
                            Rincian perhitungan TF-IDF dan cosine similarity disimpan saat aspirasi masuk dan tidak dihitung ulang.
                        </p>

                        <a href="{{ route('laporan.analisis', $report) }}" class="btn btn-outline-soft w-100">
                            <i class="ri-pulse-line" aria-hidden="true"></i>
                            Lihat Rincian Analisis
                        </a>
                    </div>
                </div>
            @elseif ($isStaff)
                <div class="app-card" id="status">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Tindak Lanjut Staf</h2>
                            <p class="app-card-subtitle">Status hanya dapat bergerak dari Ditugaskan ke Diproses lalu Selesai.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($errors->status->any())
                            <div class="alert alert-danger app-alert mb-3" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Status belum dapat disimpan.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->status->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if ($canStaffUpdateStatus)
                            <form method="POST" action="{{ route('laporan.tindak-lanjut.update', $report) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                                <div class="mb-3">
                                    <label class="form-label" for="reportStatus">Status Tindak Lanjut</label>
                                    <select id="reportStatus" name="status" class="form-select">
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="reportStatusNote">Catatan Tindak Lanjut</label>
                                    <textarea id="reportStatusNote" name="catatan" rows="4" maxlength="2000" class="form-control" placeholder="Tuliskan perkembangan penanganan aspirasi.">{{ old('catatan', $report->catatan_penyelesaian) }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-emerald w-100">
                                    <i class="ri-check-double-line" aria-hidden="true"></i>
                                    Simpan Tindak Lanjut
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info app-alert mb-0" role="status">
                                <i class="ri-information-line" aria-hidden="true"></i>
                                <div>
                                    Laporan ini belum siap untuk diperbarui. Anda dapat memperbarui status hanya setelah laporan didisposisikan kepada Anda oleh administrator, atau laporan sudah berstatus Selesai/Ditolak.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($isStaff && $activeAssignment && (int) $activeAssignment->user_id === (int) auth()->id() && ! $activeAssignment->isSelesai())
        @include('penugasan._complete-modal')
    @endif
@endsection

@push('styles')
    <style>
        .report-quote { font-size: 12.5px !important; }
    </style>
@endpush
