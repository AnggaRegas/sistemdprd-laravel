@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
        ['label' => $laporan->ringkasan, 'url' => route('laporan.show', $laporan)],
        ['label' => 'Analisis Kemiripan'],
    ],
])

@section('title', 'Analisis Kemiripan '.$laporan->ringkasan)

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
        :description="'Tiket: '.($laporan->nomor_tiket ?: '—').' · Pelapor: '.($laporan->pelapor_nama ?: 'Tidak tercatat').' · '.\App\Support\PortalData::witaDateTime($laporan->created_at)"
    >
        <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Detail Aspirasi
        </a>
    </x-admin.page-header>

    @if ($analysisRun === null && $group === null && $comparisons->isEmpty() && $reviewComparisons->isEmpty())
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
                    <p class="app-card-subtitle">Hasil analisis kemiripan aspirasi ini dengan aspirasi lain.</p>
                </div>
                <span class="status-badge {{ $isCurrentMain ? 'status-success' : 'status-neutral' }}">
                    {{ $currentStatus }}
                </span>
            </div>

            <div class="app-card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
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
                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                                <i class="ri-file-list-3-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ number_format($comparisons->count() + $reviewComparisons->count(), 0, ',', '.') }}</strong>
                                <span>Aspirasi pembanding</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #fdf4e7; color: #b7791f;">
                                <i class="ri-shield-check-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ $reviewComparisons->isNotEmpty() && $comparisons->isEmpty() ? 'Perlu Tinjauan' : ($comparisons->isNotEmpty() ? 'Kemiripan Tinggi' : 'Tercatat') }}</strong>
                                <span>Status analisis</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
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
                            <p class="app-card-subtitle">Informasi aspirasi yang sedang diperiksa.</p>
                        </div>
                        <span class="status-badge {{ $isCurrentMain ? 'status-success' : 'status-info' }}">
                            {{ $isCurrentMain ? 'Aspirasi Utama' : 'Serupa' }}
                        </span>
                    </div>

                    <div class="app-card-body">
                        <dl class="detail-list mb-3">
                            <div class="detail-row">
                                <dt>Nomor tiket</dt>
                                <dd><strong>{{ $laporan->nomor_tiket }}</strong></dd>
                            </div>
                            <div class="detail-row">
                                <dt>Tanggal pengiriman</dt>
                                <dd>{{ \App\Support\PortalData::witaDateTime($laporan->created_at) }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Pelapor</dt>
                                <dd>{{ $laporan->pelapor_nama ?: 'Tidak tercatat' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Alamat</dt>
                                <dd>{{ $laporan->pelapor_alamat ?: '—' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Kecamatan</dt>
                                <dd>{{ $laporan->kecamatan ?: '—' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Anggota DPRD</dt>
                                <dd>{{ $laporan->member?->nama_lengkap ?: '—' }}</dd>
                            </div>
                        </dl>

                        <div class="card-subtitle-custom mb-2">Uraian Aspirasi:</div>
                        <div class="report-quote">{!! nl2br(e($laporan->isi ?: $laporan->ringkasan)) !!}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="app-card">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Aspirasi Pembanding</h2>
                            <p class="app-card-subtitle">Daftar aspirasi lain yang memiliki kemiripan (kemiripan tinggi).</p>
                        </div>
                        <span class="status-badge status-success">{{ $comparisons->count() }} aspirasi</span>
                    </div>

                    <div class="app-card-body">
                        @if ($comparisons->isEmpty())
                            <p class="cell-muted mb-0">Belum ada aspirasi lain yang terdeteksi mirip dengan aspirasi ini.</p>
                        @else
                            <ul class="similarity-card-list">
                                @foreach ($comparisons as $match)
                                    <li class="similarity-card-item">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-2">
                                            <div>
                                                <strong class="similarity-other-title">{{ $match['ringkasan'] }}</strong>
                                                <div class="cell-sub mt-1">
                                                    <span>{{ $match['nomor_tiket'] ?? 'Tiket #'.$match['id'] }}</span>
                                                    · <span>{{ $match['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</span>
                                                    @if (! empty($match['created_at']))
                                                        · <span>{{ \App\Support\PortalData::witaDateTime($match['created_at'], false) }}</span>
                                                    @endif
                                                    @if (! empty($match['kecamatan']))
                                                        · <span>{{ $match['kecamatan'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="status-badge status-{{ $match['level_tone'] ?? 'success' }}">
                                                    {{ $match['level_label'] ?? 'Kemiripan Tinggi' }}
                                                </span>
                                                @if (! empty($match['is_main']))
                                                    <span class="status-badge status-success">Aspirasi Utama</span>
                                                @endif
                                            </div>
                                        </div>

                                        <p class="similarity-other-excerpt">
                                            {{ \Illuminate\Support\Str::limit($match['isi'] ?: $match['ringkasan'], 280) }}
                                        </p>

                                        {{-- Indikator Visual Skor (Teks vs Lokasi) --}}
                                        <div class="similarity-score-grid">
                                            <div class="similarity-score-box score-text">
                                                <span class="score-icon"><i class="ri-file-text-line" aria-hidden="true"></i></span>
                                                <div class="score-content">
                                                    <span class="score-label">Cosine Similarity</span>
                                                    <strong class="score-value">{{ $percent($match['cosine_similarity'] ?? $match['score']) }}</strong>
                                                </div>
                                            </div>

                                            <div class="similarity-score-box score-problem">
                                                <span class="score-icon"><i class="ri-article-line" aria-hidden="true"></i></span>
                                                <div class="score-content">
                                                    <span class="score-label">Skor Masalah</span>
                                                    <strong class="score-value">{{ $percent($match['problem_score'] ?? $match['score']) }}</strong>
                                                </div>
                                            </div>

                                            <div class="similarity-score-box score-location">
                                                <span class="score-icon"><i class="ri-map-pin-2-line" aria-hidden="true"></i></span>
                                                <div class="score-content">
                                                    <span class="score-label">Skor Lokasi</span>
                                                    <strong class="score-value">{{ $match['location_score'] !== null ? $percent($match['location_score']) : '—' }}</strong>
                                                </div>
                                            </div>

                                            @if ($match['combined_score'] !== null)
                                                <div class="similarity-score-box score-combined">
                                                    <span class="score-icon"><i class="ri-scales-3-line" aria-hidden="true"></i></span>
                                                    <div class="score-content">
                                                        <span class="score-label">Skor Gabungan</span>
                                                        <strong class="score-value">{{ $percent($match['combined_score']) }}</strong>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Kata-kata berkontribusi (Shared Terms) --}}
                                        @if (! empty($match['shared_terms']))
                                            <div class="similarity-shared-terms mb-2">
                                                <span class="terms-title">Kata-kata Serupa:</span>
                                                <div class="terms-chips">
                                                    @foreach (array_slice($match['shared_terms'], 0, 12) as $term)
                                                        @php
                                                            $termText = is_array($term) ? ($term['term'] ?? '') : (string) $term;
                                                        @endphp
                                                        @if ($termText !== '')
                                                            <span class="term-chip">{{ $termText }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Bukti / Alasan Kemiripan --}}
                                        @if (! empty($match['reasons']))
                                            <div class="similarity-reasons mb-3">
                                                <span class="terms-title">Alasan Kemiripan:</span>
                                                <ul class="reasons-list">
                                                    @foreach ($match['reasons'] as $reason)
                                                        <li><i class="ri-checkbox-circle-line" aria-hidden="true"></i> <span>{{ $reason }}</span></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="d-flex align-items-center justify-content-end pt-2 border-top">
                                            <a href="{{ route('laporan.show', $match['id']) }}" class="btn btn-outline-soft btn-sm">
                                                <i class="ri-eye-line" aria-hidden="true"></i>
                                                Lihat Detail Aspirasi Pembanding
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
                            Isi aspirasi ini mirip dengan aspirasi berikut, tetapi lokasinya belum dapat dipastikan secara otomatis.
                            Periksa kembali sebelum memutuskan apakah ini laporan duplikat atau bukan.
                        </p>
                    </div>
                    <span class="status-badge status-warning">{{ $reviewComparisons->count() }} aspirasi</span>
                </div>

                <div class="app-card-body">
                    <ul class="similarity-card-list">
                        @foreach ($reviewComparisons as $match)
                            <li class="similarity-card-item">
                                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-2">
                                    <div>
                                        <strong class="similarity-other-title">{{ $match['ringkasan'] }}</strong>
                                        <div class="cell-sub mt-1">
                                            <span>{{ $match['nomor_tiket'] ?? 'Tiket #'.$match['id'] }}</span>
                                            · <span>{{ $match['pelapor_nama'] ?: 'Pelapor tidak tercatat' }}</span>
                                            @if (! empty($match['created_at']))
                                                · <span>{{ \App\Support\PortalData::witaDateTime($match['created_at'], false) }}</span>
                                            @endif
                                            · <span>{{ $match['kecamatan'] ?: 'Kecamatan tidak tercatat' }}</span>
                                        </div>
                                    </div>
                                    <span class="status-badge status-warning">Perlu Tinjauan Manual</span>
                                </div>

                                <p class="similarity-other-excerpt">
                                    {{ \Illuminate\Support\Str::limit($match['isi'] ?: $match['ringkasan'], 280) }}
                                </p>

                                {{-- Indikator Visual Skor (Teks vs Lokasi) --}}
                                <div class="similarity-score-grid">
                                    <div class="similarity-score-box score-text">
                                        <span class="score-icon"><i class="ri-file-text-line" aria-hidden="true"></i></span>
                                        <div class="score-content">
                                            <span class="score-label">Cosine Similarity</span>
                                            <strong class="score-value">{{ $percent($match['cosine_similarity'] ?? $match['score']) }}</strong>
                                        </div>
                                    </div>

                                    <div class="similarity-score-box score-problem">
                                        <span class="score-icon"><i class="ri-article-line" aria-hidden="true"></i></span>
                                        <div class="score-content">
                                            <span class="score-label">Skor Masalah</span>
                                            <strong class="score-value">{{ $percent($match['problem_score'] ?? $match['score']) }}</strong>
                                        </div>
                                    </div>

                                    <div class="similarity-score-box score-location">
                                        <span class="score-icon"><i class="ri-map-pin-2-line" aria-hidden="true"></i></span>
                                        <div class="score-content">
                                            <span class="score-label">Skor Lokasi</span>
                                            <strong class="score-value">{{ $match['location_score'] !== null ? $percent($match['location_score']) : '—' }}</strong>
                                        </div>
                                    </div>

                                    @if ($match['combined_score'] !== null)
                                        <div class="similarity-score-box score-combined">
                                            <span class="score-icon"><i class="ri-scales-3-line" aria-hidden="true"></i></span>
                                            <div class="score-content">
                                                <span class="score-label">Skor Gabungan</span>
                                                <strong class="score-value">{{ $percent($match['combined_score']) }}</strong>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Kata-kata berkontribusi (Shared Terms) --}}
                                @if (! empty($match['shared_terms']))
                                    <div class="similarity-shared-terms mb-2">
                                        <span class="terms-title">Kata-kata Serupa:</span>
                                        <div class="terms-chips">
                                            @foreach (array_slice($match['shared_terms'], 0, 12) as $term)
                                                @php
                                                    $termText = is_array($term) ? ($term['term'] ?? '') : (string) $term;
                                                @endphp
                                                @if ($termText !== '')
                                                    <span class="term-chip">{{ $termText }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Bukti / Alasan Kemiripan --}}
                                @if (! empty($match['reasons']))
                                    <div class="similarity-reasons mb-3">
                                        <span class="terms-title">Alasan Peninjauan:</span>
                                        <ul class="reasons-list">
                                            @foreach ($match['reasons'] as $reason)
                                                <li><i class="ri-information-line" aria-hidden="true"></i> <span>{{ $reason }}</span></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="d-flex align-items-center justify-content-end pt-2 border-top">
                                    <a href="{{ route('laporan.show', $match['id']) }}" class="btn btn-outline-soft btn-sm">
                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                        Lihat Detail Aspirasi Pembanding
                                    </a>
                                </div>
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
        .card-subtitle-custom { font-size: 11.5px; font-weight: 600; color: #52605b; }
        .similarity-card-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
        .similarity-card-item {
            border: 1px solid #e5e9e6;
            border-radius: 14px;
            padding: 14px 16px;
            background: #fbfcfa;
            transition: border-color .15s ease-in-out;
        }
        .similarity-card-item:hover { border-color: #cbd5d0; }
        .similarity-other-title { font-size: 13px; color: #15221e; }
        .similarity-other-excerpt { font-size: 12px; line-height: 1.7; color: #4b5853; margin: 8px 0 12px; }

        .similarity-score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .similarity-score-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid #e5e9e6;
            background: #ffffff;
        }
        .score-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .score-text .score-icon { background: #eef6f1; color: #155043; }
        .score-problem .score-icon { background: #eaf2fb; color: #205c90; }
        .score-location .score-icon { background: #fdf4e7; color: #b7791f; }
        .score-combined .score-icon { background: #f3effa; color: #6a4bb5; }

        .score-content { display: flex; flex-direction: column; min-width: 0; }
        .score-label { font-size: 10px; color: #72807a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .score-value { font-size: 13px; font-weight: 700; color: #15221e; line-height: 1.2; }

        .terms-title { font-size: 11px; font-weight: 600; color: #61706a; display: block; margin-bottom: 4px; }
        .terms-chips { display: flex; flex-wrap: wrap; gap: 4px; }
        .term-chip {
            display: inline-block;
            font-size: 10.5px;
            padding: 2px 7px;
            border-radius: 6px;
            background: #eef3f0;
            color: #174c3e;
            border: 1px solid #dce6e1;
        }

        .reasons-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 4px; }
        .reasons-list li {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            font-size: 11.5px;
            line-height: 1.5;
            color: #52605b;
        }
        .reasons-list li i { font-size: 14px; color: #155043; flex-shrink: 0; margin-top: 1px; }
        .score-warning-icon { color: #b7791f !important; }
    </style>
@endpush
