@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => $isAdmin ? 'Monitoring Penugasan' : 'Penugasan Saya', 'url' => route('penugasan.index')],
        ['label' => 'Detail Penugasan'],
    ],
])

@section('title', 'Detail Penugasan '.($laporan?->nomor_tiket ?? '#'.$penugasan->id))

@section('content')
    <x-admin.page-header
        eyebrow="Detail penugasan staf pendamping"
        :title="$laporan?->ringkasan ?? 'Penugasan'"
        :description="'Tiket '.($laporan?->nomor_tiket ?? '—').' · '.($laporan?->kecamatan_label ?? 'Tanpa kecamatan')"
    >
        <a href="{{ route('penugasan.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>

        @if ($laporan)
            <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-emerald">
                <i class="ri-file-text-line" aria-hidden="true"></i>
                Buka Detail Laporan
            </a>
        @endif
    </x-admin.page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Penugasan</h2>
                        <p class="app-card-subtitle">Data aspirasi, staf pendamping, dan waktu penugasan.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <x-admin.status-badge :status="$penugasan->status_label" />
                        @if ($penugasan->isVerified())
                            <span class="status-badge status-success">Terverifikasi</span>
                        @endif
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Judul aspirasi</dt>
                                    <dd>{{ $laporan?->ringkasan ?? '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Nomor tiket</dt>
                                    <dd>{{ $laporan?->nomor_tiket ?? '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Anggota DPRD</dt>
                                    <dd>{{ $laporan?->member?->nama_lengkap ?? '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Status aspirasi</dt>
                                    <dd>{{ $laporan?->status_label ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Staff pendamping</dt>
                                    <dd>{{ $penugasan->user?->name ?? '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal penugasan</dt>
                                    <dd>{{ \App\Support\PortalData::dateTime($penugasan->assigned_at) }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Ditugaskan oleh</dt>
                                    <dd>{{ $penugasan->assignedBy?->name ?? 'Superadmin' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal selesai</dt>
                                    <dd>{{ $penugasan->completed_at ? \App\Support\PortalData::dateTime($penugasan->completed_at) : '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="soft-divider"></div>

                    <div class="row g-3">
                        @foreach ($tahapan as $tahap)
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border h-100" style="background: #fbfcfa;">
                                    <span class="cell-sub d-block">{{ $tahap['label'] }}</span>
                                    <strong class="d-block mt-1" style="font-size: 12px;">
                                        {{ $tahap['waktu'] ? \App\Support\PortalData::dateTime($tahap['waktu']) : 'Belum terjadi' }}
                                    </strong>
                                    @if ($tahap['waktu'] && $tahap['oleh'])
                                        <span class="cell-muted">{{ $tahap['oleh'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Hasil Tindak Lanjut</h2>
                        <p class="app-card-subtitle">Pekerjaan yang sudah dilakukan oleh staf pendamping.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    @if (! $penugasan->isSelesai())
                        <div class="alert alert-info app-alert mb-0" role="status">
                            <i class="ri-time-line" aria-hidden="true"></i>
                            <div>
                                Penugasan ini masih {{ strtolower($penugasan->status_label) }}. Hasil tindak lanjut
                                akan tampil di sini setelah staf menandai tugas selesai.
                            </div>
                        </div>
                    @else
                        <h3 class="app-card-title mb-2">Hasil</h3>
                        <p class="mb-3" style="font-size: 12px; line-height: 1.8;">
                            {!! nl2br(e($penugasan->hasil_tindak_lanjut ?: '—')) !!}
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Diselesaikan oleh</dt>
                                        <dd>{{ $penugasan->completedBy?->name ?? $penugasan->user?->name ?? '—' }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Waktu penyelesaian</dt>
                                        <dd>{{ \App\Support\PortalData::dateTime($penugasan->completed_at) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="col-md-6">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Keterangan tambahan</dt>
                                        <dd>{!! nl2br(e($penugasan->keterangan_tambahan ?: '—')) !!}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Bukti tindak lanjut</dt>
                                        <dd>
                                            @if ($penugasan->hasBukti())
                                                <a href="{{ route('penugasan.bukti', $penugasan) }}" class="app-card-link">
                                                    <i class="ri-download-2-line" aria-hidden="true"></i>
                                                    {{ $penugasan->bukti_nama ?: 'Unduh bukti' }}
                                                </a>
                                                <span class="cell-sub d-block">{{ $penugasan->bukti_size_label }}</span>
                                            @else
                                                <span class="cell-muted">Tidak ada dokumen pendukung yang dilampirkan.</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($isAdmin && $penugasan->isSelesai())
                <div class="app-card">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Verifikasi Hasil</h2>
                            <p class="app-card-subtitle">
                                Konfirmasi hasil pekerjaan staf. Status aspirasi tetap dikelola melalui status laporan.
                            </p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($penugasan->isVerified())
                            <div class="alert alert-success app-alert mb-0" role="status">
                                <i class="ri-verified-badge-line" aria-hidden="true"></i>
                                <div>
                                    Hasil tindak lanjut sudah diverifikasi oleh
                                    <strong>{{ $penugasan->verifiedBy?->name ?? 'Superadmin' }}</strong>
                                    pada {{ \App\Support\PortalData::dateTime($penugasan->verified_at) }}.
                                    @if ($penugasan->catatan_verifikasi)
                                        <br>{{ $penugasan->catatan_verifikasi }}
                                    @endif
                                </div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('penugasan.verifikasi', $penugasan) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="catatanVerifikasi">Catatan Verifikasi</label>
                                    <textarea id="catatanVerifikasi" name="catatan_verifikasi" rows="3" maxlength="2000"
                                              class="form-control"
                                              placeholder="Catatan konfirmasi hasil tindak lanjut (opsional).">{{ old('catatan_verifikasi') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-emerald">
                                    <i class="ri-check-double-line" aria-hidden="true"></i>
                                    Konfirmasi Hasil Tindak Lanjut
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Aksi</h2>
                        <p class="app-card-subtitle">Tindakan yang tersedia untuk penugasan ini.</p>
                    </div>
                </div>

                <div class="app-card-body d-grid gap-2">
                    @if (! $isAdmin && $penugasan->user_id === auth()->id() && ! $penugasan->isSelesai())
                        @if ($penugasan->status === \App\Models\LaporanPenugasan::STATUS_DITUGASKAN)
                            <form method="POST" action="{{ route('penugasan.mulai', $penugasan) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-soft w-100">
                                    <i class="ri-play-line" aria-hidden="true"></i>
                                    Mulai Tindak Lanjut
                                </button>
                            </form>
                        @endif

                        <button type="button" class="btn btn-emerald w-100"
                                data-complete-assignment
                                data-complete-action="{{ route('penugasan.selesai', $penugasan) }}"
                                data-complete-id="{{ $penugasan->id }}"
                                data-complete-title="{{ $laporan?->ringkasan ?? 'Penugasan' }}">
                            <i class="ri-check-double-line" aria-hidden="true"></i>
                            Tandai Selesai
                        </button>
                    @endif

                    @if ($laporan)
                        <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-outline-soft w-100">
                            <i class="ri-file-text-line" aria-hidden="true"></i>
                            Lihat Aspirasi
                        </a>
                    @endif

                    @if ($penugasan->hasBukti())
                        <a href="{{ route('penugasan.bukti', $penugasan) }}" class="btn btn-outline-soft w-100">
                            <i class="ri-download-2-line" aria-hidden="true"></i>
                            Unduh Bukti
                        </a>
                    @endif
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Aspirasi</h2>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Pelapor</dt>
                            <dd>{{ $laporan?->pelapor_nama ?? '—' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kecamatan</dt>
                            <dd>{{ $laporan?->kecamatan_label ?? '—' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tanggal laporan</dt>
                            <dd>{{ $laporan?->tanggal_laporan ? \App\Support\PortalData::date($laporan->tanggal_laporan->toDateString(), true) : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if (! $isAdmin && $penugasan->user_id === auth()->id() && ! $penugasan->isSelesai())
        @include('penugasan._complete-modal')
    @endif
@endsection
