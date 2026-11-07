@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat', 'url' => route('laporan.index')],
        ['label' => $laporan->nomor_tiket, 'url' => route('laporan.show', $laporan)],
        ['label' => 'Rincian Analisis'],
    ],
])

@section('title', 'Rincian Analisis '.$laporan->nomor_tiket)

@php
    $percent = static function ($value): string {
        if ($value === null) {
            return '—';
        }

        return number_format(((float) $value) * 100, 1, ',', '.').' %';
    };

    $decimal = static function ($value, int $digits = 4): string {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, $digits, ',', '.');
    };
@endphp

@section('content')
    <x-admin.page-header
        eyebrow="Rincian analisis kemiripan"
        :title="$laporan->ringkasan"
        :description="'Tiket '.$laporan->nomor_tiket.' · '.$laporan->kecamatan_label"
    >
        <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Detail Laporan
        </a>
    </x-admin.page-header>

    @if ($analysisRun === null)
        <div class="app-card mb-3">
            <x-admin.empty-state
                icon="ri-pulse-line"
                title="Laporan ini belum memiliki riwayat analisis"
                description="Analisis kemiripan dijalankan otomatis saat aspirasi masuk. Laporan lama atau laporan tanpa anggota DPRD tujuan tidak memiliki snapshot perhitungan."
            />
        </div>
    @else
        @php
            $preprocessing = $snapshot['preprocessing'] ?? null;
            $filter = $snapshot['filter'] ?? null;
            $tfidf = $snapshot['tfidf'] ?? null;
            $cosine = $snapshot['cosine'] ?? null;
            $result = $snapshot['result'] ?? null;
            $matches = collect($result['matches'] ?? []);
            $removedWords = collect($preprocessing['removed'] ?? []);
            $stemmingChanges = collect($preprocessing['stemming_changes'] ?? []);
            $finalTokens = collect($preprocessing['final_tokens'] ?? []);
            $tfidfTerms = collect($tfidf['new_document']['terms'] ?? []);
            $cosineCandidates = collect($cosine['candidates'] ?? []);
        @endphp

        <div class="app-card mb-3 filter-card">
            <div class="app-card-body-tight">
                <div class="row g-3">
                    <div class="col-6 col-xl-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: {{ $analysisRun->is_duplicate ? '#fbf3e2' : '#eef6f1' }}; color: {{ $analysisRun->is_duplicate ? '#a9853f' : '#155043' }};">
                                <i class="{{ $analysisRun->is_duplicate ? 'ri-error-warning-line' : 'ri-shield-check-line' }}" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ $percent($analysisRun->highest_similarity) }}</strong>
                                <span>Kemiripan tertinggi</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                                <i class="ri-filter-3-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ $percent($analysisRun->threshold) }}</strong>
                                <span>Ambang kemiripan</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #f3effa; color: #6a4bb5;">
                                <i class="ri-stack-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong>{{ number_format($analysisRun->candidate_count, 0, ',', '.') }}</strong>
                                <span>Kandidat dibandingkan</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="mini-stat">
                            <span class="mini-stat-icon" style="background: #e7f5ee; color: #16794f;">
                                <i class="ri-time-line" aria-hidden="true"></i>
                            </span>
                            <div>
                                <strong style="font-size: 12px;">{{ \App\Support\PortalData::dateTime($analysisRun->analyzed_at) }}</strong>
                                <span>Waktu analisis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="app-card mb-3">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Ringkasan Analisis</h2>
                            <p class="app-card-subtitle">Hasil akhir seperti tercatat saat perhitungan berlangsung.</p>
                        </div>
                        <x-admin.status-badge :status="$analysisRun->hasil_label" />
                    </div>

                    <div class="app-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Indikasi duplikat</dt>
                                        <dd>{{ $analysisRun->is_duplicate ? 'Ya' : 'Tidak' }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Ambang kemiripan</dt>
                                        <dd>{{ $percent($analysisRun->threshold) }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Batas hasil terdepan</dt>
                                        <dd>{{ $analysisRun->max_results !== null ? number_format($analysisRun->max_results, 0, ',', '.') : '—' }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Versi algoritma</dt>
                                        <dd><code>{{ $analysisRun->algorithm_version }}</code></dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="col-md-6">
                                <dl class="detail-list">
                                    <div class="detail-row">
                                        <dt>Anggota DPRD</dt>
                                        <dd>{{ $laporan->member?->nama_lengkap ?: '—' }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Kandidat dibandingkan</dt>
                                        <dd>{{ number_format($analysisRun->candidate_count, 0, ',', '.') }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Kemiripan tertinggi</dt>
                                        <dd>{{ $percent($analysisRun->highest_similarity) }}</dd>
                                    </div>
                                    <div class="detail-row">
                                        <dt>Waktu analisis</dt>
                                        <dd>{{ \App\Support\PortalData::dateTime($analysisRun->analyzed_at) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div class="soft-divider"></div>
                        <h3 class="app-card-title mb-2">Aspirasi Terdahulu yang Memenuhi Ambang</h3>

                        @if ($matches->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table-modern">
                                    <thead>
                                        <tr>
                                            <th scope="col">Aspirasi</th>
                                            <th scope="col" class="text-end">Kemiripan</th>
                                            <th scope="col" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($matches as $match)
                                            <tr>
                                                <td>
                                                    <span class="cell-title">{{ $match['ringkasan'] ?? 'Aspirasi terdahulu' }}</span>
                                                    <span class="cell-sub">#{{ $match['id'] }}</span>
                                                </td>
                                                <td class="text-end">{{ $percent($match['similarity'] ?? null) }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('laporan.show', $match['id']) }}" class="btn btn-outline-soft btn-sm">
                                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                                        Buka
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="cell-muted mb-0">Tidak ada aspirasi terdahulu yang mencapai ambang kemiripan.</p>
                        @endif
                    </div>
                </div>

                <div class="app-card mb-3">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Detail Perhitungan</h2>
                            <p class="app-card-subtitle">Preprocessing, TF-IDF, dan cosine similarity yang benar-benar dijalankan.</p>
                        </div>
                        <span class="status-badge {{ $analysisRun->has_detail ? 'status-success' : 'status-neutral' }}">
                            {{ $analysisRun->has_detail ? 'Snapshot tersimpan' : 'Tanpa detail' }}
                        </span>
                    </div>

                    <div class="app-card-body">
                        @if (! $analysisRun->has_detail || $snapshot === null)
                            <div class="alert alert-info app-alert mb-0" role="status">
                                <i class="ri-information-line" aria-hidden="true"></i>
                                <div>
                                    Riwayat ini berasal dari hasil deteksi sebelum snapshot tersedia. Perhitungan tidak diulang agar rincian asli tidak dikarang; analisis berikutnya pada laporan ini akan menyimpan detail lengkap.
                                </div>
                            </div>
                        @else
                            <h3 class="app-card-title mb-2">1. Pra-pemrosesan Teks</h3>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <span class="cell-sub d-block mb-1">Teks mentah</span>
                                    <p class="cell-muted mb-0" style="font-size: 11px; max-height: 96px; overflow-y: auto; white-space: pre-wrap;">{{ mb_substr((string) ($preprocessing['raw'] ?? ''), 0, 4000) }}</p>
                                </div>
                                <div class="col-md-4">
                                    <span class="cell-sub d-block mb-1">Case folding</span>
                                    <p class="cell-muted mb-0" style="font-size: 11px; max-height: 96px; overflow-y: auto; white-space: pre-wrap;">{{ mb_substr((string) ($preprocessing['case_folding'] ?? ''), 0, 4000) }}</p>
                                </div>
                                <div class="col-md-4">
                                    <span class="cell-sub d-block mb-1">Pembersihan karakter</span>
                                    <p class="cell-muted mb-0" style="font-size: 11px; max-height: 96px; overflow-y: auto; white-space: pre-wrap;">{{ mb_substr((string) ($preprocessing['cleaning'] ?? ''), 0, 4000) }}</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="cell-sub d-block mb-1">Token akhir ({{ number_format($finalTokens->count(), 0, ',', '.') }})</span>
                                    <p class="mb-0" style="font-size: 11.5px; line-height: 1.8;">{{ $finalTokens->implode(' ') ?: '—' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <span class="cell-sub d-block mb-1">Kata dihapus ({{ number_format($removedWords->count(), 0, ',', '.') }})</span>
                                    <p class="mb-0" style="font-size: 11.5px; line-height: 1.8;">
                                        {{ $removedWords->take(20)->map(fn (array $row) => $row['word'].' ('.$row['reason'].')')->implode(', ') ?: '—' }}
                                    </p>
                                    @if ($stemmingChanges->isNotEmpty())
                                        <span class="cell-sub d-block mb-1 mt-2">Perubahan stemming ({{ number_format($stemmingChanges->count(), 0, ',', '.') }})</span>
                                        <p class="mb-0" style="font-size: 11.5px; line-height: 1.8;">
                                            {{ $stemmingChanges->take(20)->map(fn (array $row) => $row['from'].' → '.$row['to'])->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if ($filter !== null)
                                <div class="soft-divider"></div>
                                <h3 class="app-card-title mb-2">2. Filter Kandidat</h3>
                                <p class="mb-2" style="font-size: 11.5px; line-height: 1.8;">
                                    {{ $filter['criteria'] ?? '—' }}
                                </p>
                                @php
                                    $candidates = collect($filter['candidates'] ?? []);
                                @endphp
                                @if ($candidates->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table-modern">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Kandidat</th>
                                                    <th scope="col">Dibuat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($candidates as $candidate)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('laporan.show', $candidate['id']) }}" class="cell-title">{{ $candidate['ringkasan'] }}</a>
                                                            <span class="cell-sub">#{{ $candidate['id'] }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="cell-muted">
                                                                {{ $candidate['dibuat_at'] ? \App\Support\PortalData::dateTime(\Illuminate\Support\Carbon::parse($candidate['dibuat_at'])) : '—' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="cell-muted mb-0">Tidak ada kandidat.</p>
                                @endif
                            @endif

                            <div class="soft-divider"></div>
                            <h3 class="app-card-title mb-2">3. TF-IDF Dokumen Baru</h3>
                            @if ($tfidf === null)
                                <p class="cell-muted mb-0">Tidak ada dokumen pembanding sehingga TF-IDF tidak dihitung.</p>
                            @else
                                <p class="mb-2" style="font-size: 11.5px; line-height: 1.8;">
                                    Total dokumen korpus: <strong>{{ number_format((int) ($tfidf['total_documents'] ?? 0), 0, ',', '.') }}</strong>.
                                    Token dokumen baru: <strong>{{ number_format((int) ($tfidf['new_document']['token_count'] ?? 0), 0, ',', '.') }}</strong>.
                                </p>
                                <div class="table-responsive">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th scope="col">Kata</th>
                                                <th scope="col" class="text-end">TF</th>
                                                <th scope="col" class="text-end">TF normal</th>
                                                <th scope="col" class="text-end">DF</th>
                                                <th scope="col" class="text-end">IDF</th>
                                                <th scope="col" class="text-end">TF-IDF</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($tfidfTerms as $term)
                                                <tr>
                                                    <td><code style="font-size: 10.5px;">{{ $term['term'] }}</code></td>
                                                    <td class="text-end">{{ number_format((int) ($term['tf'] ?? 0), 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ $decimal($term['tf_normalized'] ?? null) }}</td>
                                                    <td class="text-end">{{ number_format((int) ($term['df'] ?? 0), 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ $decimal($term['idf'] ?? null) }}</td>
                                                    <td class="text-end">{{ $decimal($term['tfidf'] ?? null) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="cell-muted">Tidak ada kata valid setelah pra-pemrosesan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="soft-divider"></div>
                            <h3 class="app-card-title mb-2">4. Cosine Similarity</h3>
                            @if ($cosine === null)
                                <p class="cell-muted mb-0">Cosine similarity tidak dihitung karena tidak ada kandidat.</p>
                            @else
                                <p class="mb-2" style="font-size: 11.5px; line-height: 1.8;">
                                    Magnitudo dokumen baru: <strong>{{ $decimal($cosine['magnitude_new'] ?? null) }}</strong>.
                                </p>
                                <div class="table-responsive">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th scope="col">Kandidat</th>
                                                <th scope="col" class="text-end">Dot product</th>
                                                <th scope="col" class="text-end">Magnitudo</th>
                                                <th scope="col" class="text-end">Cosine</th>
                                                <th scope="col">Kata kunci</th>
                                                <th scope="col">Ambang</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($cosineCandidates as $candidate)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('laporan.show', $candidate['id']) }}" class="cell-title">{{ $candidate['ringkasan'] }}</a>
                                                        <span class="cell-sub">#{{ $candidate['id'] }}</span>
                                                    </td>
                                                    <td class="text-end">{{ $decimal($candidate['dot_product'] ?? null) }}</td>
                                                    <td class="text-end">
                                                        <span class="cell-sub d-block">
                                                            {{ $decimal($candidate['magnitude_new'] ?? null) }} × {{ $decimal($candidate['magnitude_candidate'] ?? null) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end"><strong>{{ $percent($candidate['similarity'] ?? null) }}</strong></td>
                                                    <td>
                                                        <span class="cell-sub">
                                                            {{ collect($candidate['vector'] ?? [])->take(5)->map(fn (array $entry) => (string) $entry['term'])->implode(', ') ?: '—' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge {{ ($candidate['memenuhi_threshold'] ?? false) ? 'status-success' : 'status-neutral' }}">
                                                            {{ ($candidate['memenuhi_threshold'] ?? false) ? 'Lolos' : 'Di bawah' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="cell-muted">Tidak ada kandidat laporan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="app-card">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Informasi Analisis</h2>
                            <p class="app-card-subtitle">Keterangan teknis pemantauan duplikasi.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        <dl class="detail-list">
                            <div class="detail-row">
                                <dt>Nomor tiket</dt>
                                <dd>{{ $laporan->nomor_tiket }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Anggota DPRD</dt>
                                <dd>{{ $laporan->member?->nama_lengkap ?: '—' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Waktu analisis</dt>
                                <dd>{{ \App\Support\PortalData::dateTime($analysisRun->analyzed_at) }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Versi algoritma</dt>
                                <dd><code>{{ $analysisRun->algorithm_version }}</code></dd>
                            </div>
                            <div class="detail-row">
                                <dt>Kelengkapan</dt>
                                <dd>{{ $analysisRun->has_detail ? 'Snapshot lengkap' : 'Ringkasan legacy' }}</dd>
                            </div>
                        </dl>

                        <div class="soft-divider"></div>

                        <p class="cell-muted" style="font-size: 11px; line-height: 1.8;">
                            Kemiripan dihitung dari isi aspirasi dengan anggota DPRD tujuan yang sama.
                            Identitas pelapor, alamat, kontak, dan lampiran tidak pernah diproses.
                        </p>

                        <a href="{{ route('laporan.show', $laporan) }}" class="btn btn-outline-soft w-100">
                            <i class="ri-file-text-line" aria-hidden="true"></i>
                            Buka Detail Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
