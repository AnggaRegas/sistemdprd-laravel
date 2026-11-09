@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index')],
        ['label' => 'Riwayat Perubahan'],
    ],
])

@section('title', 'Riwayat Perubahan Kamus Acuan')

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan"
        title="Riwayat Perubahan"
        description="Audit pembuatan versi, perubahan entri, impor, dan aktivasi kamus. Riwayat tidak pernah dihapus."
    >
        <a href="{{ route('kamus.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Kamus
        </a>
    </x-admin.page-header>

    <div class="app-card filter-card mb-3">
        <form method="GET" action="{{ route('kamus.riwayat') }}" class="filter-bar" role="search">
            <select name="versi" class="filter-select" data-auto-submit aria-label="Filter versi kamus">
                <option value="">Semua versi</option>
                @foreach ($versions as $version)
                    <option value="{{ $version->id }}" @selected($filters['versi'] === (string) $version->id)>{{ $version->tahun }} — {{ $version->nama }}</option>
                @endforeach
            </select>

            <select name="aksi" class="filter-select" data-auto-submit aria-label="Filter jenis perubahan">
                <option value="">Semua tindakan</option>
                @foreach ($aksiLabels as $aksi => $label)
                    <option value="{{ $aksi }}" @selected($filters['aksi'] === $aksi)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">Terapkan</button>
                <a href="{{ route('kamus.riwayat') }}" class="filter-reset">Reset</a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Catatan Audit</h2>
                <p class="app-card-subtitle">{{ number_format($riwayat->total(), 0, ',', '.') }} catatan perubahan.</p>
            </div>
        </div>

        @if ($riwayat->isEmpty())
            <x-admin.empty-state
                icon="ri-history-line"
                title="Belum ada riwayat"
                description="Riwayat akan muncul setelah versi atau entri kamus dibuat, diubah, atau diimpor."
            />
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
                            <th scope="col">Versi</th>
                            <th scope="col">Entri</th>
                            <th scope="col">Tindakan</th>
                            <th scope="col">Petugas</th>
                            <th scope="col">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $item)
                            <tr>
                                <td><span class="cell-muted">{{ \App\Support\PortalData::dateTime($item->created_at) }}</span></td>
                                <td>{{ $item->versi ? $item->versi->tahun.' — '.$item->versi->nama : '—' }}</td>
                                <td>
                                    @if ($item->entri)
                                        <strong>{{ $item->entri->kode }}</strong>
                                        <span class="cell-sub d-block">{{ $item->entri->klasifikasi }}</span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="status-badge status-info">{{ $item->aksi_label }}</span></td>
                                <td>{{ $item->user?->name ?: 'Sistem' }}</td>
                                <td>
                                    <span class="cell-muted">{{ $item->catatan ?: '—' }}</span>

                                    @if ($item->sebelum || $item->sesudah)
                                        <details class="mt-2">
                                            <summary class="cell-sub" style="cursor: pointer;">Lihat snapshot</summary>
                                            <div class="mt-2" style="font-size: 10px; line-height: 1.6;">
                                                @if ($item->sebelum)
                                                    <strong>Sebelum</strong>
                                                    <pre class="mb-2 text-wrap">{{ json_encode($item->sebelum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                @endif
                                                @if ($item->sesudah)
                                                    <strong>Sesudah</strong>
                                                    <pre class="mb-0 text-wrap">{{ json_encode($item->sesudah, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                @endif
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">Menampilkan <strong>{{ $riwayat->firstItem() }}–{{ $riwayat->lastItem() }}</strong> dari <strong>{{ number_format($riwayat->total(), 0, ',', '.') }}</strong> catatan</p>
                <x-admin.pagination :paginator="$riwayat" />
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .table-modern pre { background: #f6f8f6; border-radius: 10px; padding: 10px; margin: 0; font-size: 10px; }
    </style>
@endpush
