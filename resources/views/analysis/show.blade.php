@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
        ['label' => $laporan->ringkasan, 'url' => route('laporan.show', $laporan)],
        ['label' => 'Rincian Analisis'],
    ],
])

@section('title', 'Rincian Analisis '.$laporan->ringkasan)

@php
    $percent = static function ($value): string {
        if ($value === null) {
            return '—';
        }

        return number_format(((float) $value) * 100, 1, ',', '.').' %';
    };

    $highest = $comparisons->max('score');
    if ($highest === null && $analysisRun?->highest_similarity !== null) {
        $highest = (float) $analysisRun->highest_similarity;
    }

    $currentStatus = $isCurrentMain
        ? 'Aspirasi Utama'
        : ($group !== null ? 'Serupa' : 'Belum Ada Kelompok');
@endphp

@section('content')
    <x-admin.page-header
        eyebrow="Rincian analisis kemiripan"
        :title="$laporan->ringkasan"
        :description="'Pelapor: '.($laporan->pelapor_nama ?: 'Tidak tercatat').($laporan->member?->nama_lengkap ? ' · '.$laporan->member->nama_lengkap : '')"
    >
        <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Detail Laporan
        </a>
    </x-admin.page-header>

    @if ($analysisRun === null && $group === null)
        <div class="app-card mb-3">
            <x-admin.empty-state
                icon="ri-pulse-line"
                title="Laporan ini belum memiliki riwayat analisis"
                description="Analisis kemiripan dijalankan otomatis saat aspirasi masuk. Laporan lama atau laporan tanpa anggota DPRD tujuan tidak memiliki hasil analisis."
            />
        </div>
    @else
        <div class="app-card mb-3">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Ringkasan Kemiripan</h2>
                    <p class="app-card-subtitle">Hasil perbandingan isi aspirasi ini dengan aspirasi lain.</p>
                </div>
                <span class="status-badge {{ $isCurrentMain ? 'status-success' : 'status-neutral' }}">
                    {{ $currentStatus }}
                </span>
            </div>

            <div class="app-card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                                <i class="ri-percent-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ $percent($highest) }}</strong>
                                <span>Kemiripan tertinggi</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                                <i class="ri-file-list-3-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ number_format($comparisons->count(), 0, ',', '.') }}</strong>
                                <span>Aspirasi pembanding</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #f3effa; color: #6a4bb5;">
                                <i class="ri-user-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong style="font-size: 12px;">{{ $laporan->pelapor_nama ?: 'Tidak tercatat' }}</strong>
                                <span>Pelapor aspirasi ini</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Aspirasi Ini</h2>
                            <p class="app-card-subtitle">Isi aspirasi yang sedang dibandingkan.</p>
                        </div>
                        <span class="status-badge {{ $isCurrentMain ? 'status-success' : 'status-info' }}">
                            {{ $isCurrentMain ? 'Aspirasi Utama' : 'Serupa' }}
                        </span>
                    </div>

                    <div class="app-card-body">
                        <dl class="detail-list mb-3">
                            <div class="detail-row">
                                <dt>Pelapor</dt>
                                <dd>{{ $laporan->pelapor_nama ?: 'Tidak tercatat' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Anggota DPRD</dt>
                                <dd>{{ $laporan->member?->nama_lengkap ?: '—' }}</dd>
                            </div>
                        </dl>

                        <div class="report-quote">{!! nl2br(e($laporan->isi ?: $laporan->ringkasan)) !!}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="app-card">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Aspirasi Pembanding</h2>
                            <p class="app-card-subtitle">Diurutkan dari kemiripan tertinggi.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($comparisons->isEmpty())
                            <p class="cell-muted mb-0">Belum ada aspirasi lain yang terdeteksi mirip dengan aspirasi ini.</p>
                        @else
                            <ul class="similarity-mini-list">
                                @foreach ($comparisons as $match)
                                    <li>
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <div>
                                                <strong style="font-size: 12.5px;">{{ $match['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</strong>
                                                <span class="cell-sub d-block">{{ \Illuminate\Support\Str::limit($match['ringkasan'], 90) }}</span>
                                            </div>
                                            <span class="status-badge {{ $match['is_main'] ? 'status-success' : 'status-info' }}">
                                                {{ $match['is_main'] ? 'Aspirasi Utama' : 'Serupa' }}
                                            </span>
                                        </div>

                                        <p class="mt-2 mb-2" style="font-size: 11.5px; line-height: 1.7; color: #52605b;">
                                            {{ \Illuminate\Support\Str::limit($match['isi'] ?: $match['ringkasan'], 260) }}
                                        </p>

                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span class="cell-sub">
                                                Kemiripan:
                                                <strong>{{ $percent($match['score']) }}</strong>
                                            </span>
                                            <a href="{{ route('laporan.show', $match['id']) }}" class="btn btn-outline-soft btn-sm">
                                                <i class="ri-eye-line" aria-hidden="true"></i>
                                                Lihat Detail
                                            </a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (isset($reviewComparisons) && $reviewComparisons->isNotEmpty())
            <div class="app-card mt-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Perlu Peninjauan Manual</h2>
                        <p class="app-card-subtitle">
                            Isi aspirasi ini mirip dengan aspirasi berikut, tetapi lokasinya belum dapat dikonfirmasi.
                            Periksa kembali sebelum memutuskan duplikat atau bukan.
                        </p>
                    </div>
                    <span class="status-badge status-warning">{{ $reviewComparisons->count() }} pasangan</span>
                </div>

                <div class="app-card-body">
                    <ul class="similarity-mini-list">
                        @foreach ($reviewComparisons as $match)
                            <li>
                                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                    <div>
                                        <strong style="font-size: 12.5px;">{{ $match['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</strong>
                                        <span class="cell-sub d-block">{{ \Illuminate\Support\Str::limit($match['ringkasan'], 90) }}</span>
                                        <span class="cell-sub d-block">
                                            {{ $match['nomor_tiket'] ?: '—' }}
                                            · {{ $match['kecamatan'] ?: 'Kecamatan tidak tercatat' }}
                                        </span>
                                    </div>
                                    <span class="status-badge status-warning">Perlu Peninjauan</span>
                                </div>

                                <p class="mt-2 mb-2" style="font-size: 11.5px; line-height: 1.7; color: #52605b;">
                                    {{ \Illuminate\Support\Str::limit($match['isi'] ?: $match['ringkasan'], 260) }}
                                </p>

                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <span class="cell-sub">
                                        Skor masalah:
                                        <strong>{{ $percent($match['problem_score']) }}</strong>
                                        · Skor lokasi:
                                        <strong>{{ $percent($match['location_score']) }}</strong>
                                    </span>
                                    <a href="{{ route('laporan.show', $match['id']) }}" class="btn btn-outline-soft btn-sm">
                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                        Lihat Detail
                                    </a>
                                </div>

                                @if (! empty($match['reasons']))
                                    <ul class="mt-2 mb-0" style="padding-left: 18px; font-size: 11.5px; color: #52605b;">
                                        @foreach ($match['reasons'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('styles')
    <style>
        .report-quote { font-size: 12.5px !important; }
        .similarity-mini-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .similarity-mini-list li { border: 1px solid #e5e9e6; border-radius: 12px; padding: 12px 14px; background: #fbfcfa; }
        .similarity-mini-list a { color: #155043; text-decoration: none; }
    </style>
@endpush
