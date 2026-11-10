@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Pratinjau Unggahan'],
    ],
])

@section('title', 'Pratinjau Kamus Acuan '.$version->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan {{ $version->tahun }}"
        title="Pratinjau Unggahan"
        description="Periksa hasil pembacaan berkas. Data baru hanya disimpan setelah Anda menekan Terapkan."
    >
        <a href="{{ route('kamus.versi.unggah', $version) }}" class="btn btn-outline-soft">
            <i class="ri-upload-2-line" aria-hidden="true"></i>
            Unggah Berkas Lain
        </a>
        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @if ($preview['errors'] !== [])
        <div class="alert alert-danger app-alert mb-3" role="alert">
            <i class="ri-error-warning-line" aria-hidden="true"></i>
            <div>
                <strong>Pratinjau belum valid. Tidak ada data yang diubah.</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($preview['errors'] as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                <p class="mb-0 mt-2" style="font-size: 11.5px;">
                    Perbaiki berkas lalu unggah ulang. Data Kamus Acuan lama tetap utuh.
                </p>
            </div>
        </div>
    @endif

    @if ($preview['warnings'] !== [])
        <div class="alert alert-warning app-alert mb-3" role="alert">
            <i class="ri-alert-line" aria-hidden="true"></i>
            <div>
                <strong>Catatan pembacaan berkas</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($preview['warnings'] as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #f3f6f4; color: #155043;">
                            <i class="ri-file-text-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $preview['total'] }}</strong>
                            <span>Baris terbaca</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef7f1; color: #14663f;">
                            <i class="ri-add-circle-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $preview['baru'] }}</strong>
                            <span>Entri baru</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fdf6e9; color: #8a6514;">
                            <i class="ri-refresh-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $preview['diperbarui'] }}</strong>
                            <span>Entri diperbarui</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #f3f0f8; color: #5b4a86;">
                            <i class="ri-file-list-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $preview['format_label'] }}</strong>
                            <span>{{ $preview['file_name'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Pemetaan Kolom</h2>
                <p class="app-card-subtitle">
                    Sumber: {{ $preview['file_name'] }}
                    @if ($preview['sheet'])
                        · lembar {{ $preview['sheet'] }}
                    @endif
                </p>
            </div>
            <span class="status-badge {{ $preview['valid'] ? 'status-success' : 'status-danger' }}">
                {{ $preview['valid'] ? 'Siap diterapkan' : 'Perlu perbaikan' }}
            </span>
        </div>
        <div class="app-card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach ($preview['columns'] as $key => $header)
                    <span class="status-badge status-neutral">
                        {{ $header }} → {{ $key }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Contoh Baris Terbaca</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ min(200, $preview['total']) }} dari {{ number_format($preview['total'], 0, ',', '.') }} baris.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-modern upload-preview-table">
                <thead>
                    <tr>
                        <th scope="col">Baris</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Klasifikasi</th>
                        <th scope="col">Kata kunci</th>
                        <th scope="col">Prioritas</th>
                        <th scope="col">Komisi</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($preview['rows'], 0, 200) as $row)
                        <tr>
                            <td><span class="cell-muted">{{ $row['baris'] }}</span></td>
                            <td><strong>{{ $row['kode'] }}</strong></td>
                            <td>
                                {{ $row['klasifikasi'] }}
                                @if ($row['uraian'])
                                    <span class="cell-sub d-block">{{ \Illuminate\Support\Str::limit($row['uraian'], 100) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="cell-sub d-block">
                                    {{ collect($row['kata_kunci'])->take(5)->pluck('term')->implode(', ') ?: '—' }}
                                </span>
                                <span class="cell-muted" style="font-size: 10px;">
                                    {{ count($row['kata_kunci']) }} kata kunci · {{ count($row['sinonim']) }} sinonim
                                </span>
                            </td>
                            <td>{{ $row['prioritas'] ?: '—' }}</td>
                            <td><span class="cell-muted">{{ $row['komisi'] ?: '—' }}</span></td>
                            <td>
                                <span class="status-badge {{ $row['status'] === 'baru' ? 'status-success' : 'status-warning' }}">
                                    {{ $row['status'] === 'baru' ? 'Baru' : 'Diperbarui' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Terapkan Perubahan</h2>
                <p class="app-card-subtitle">
                    Pilih cara penerapan. Seluruh baris disimpan dalam satu transaksi; jika gagal, tidak ada perubahan sebagian.
                </p>
            </div>
        </div>

        <div class="app-card-body">
            <form method="POST" action="{{ route('kamus.versi.unggah.apply', $version) }}" data-upload-apply>
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="uploadMode">Mode penerapan</label>
                        <select id="uploadMode" name="mode" class="form-select">
                            <option value="gabung" @selected(old('mode', 'gabung') === 'gabung')>
                                Gabungkan — perbarui kode yang sama, pertahankan entri lain
                            </option>
                            <option value="ganti" @selected(old('mode') === 'ganti')>
                                Ganti — hapus lunak entri yang tidak ada pada berkas baru
                            </option>
                        </select>
                        <div class="form-text">
                            Mode "Ganti" hanya berjalan setelah seluruh baris valid. Entri yang dihapus tetap tersimpan sebagai riwayat.
                        </div>
                    </div>

                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-emerald w-100" @disabled(! $preview['valid'])>
                            <i class="ri-check-double-line" aria-hidden="true"></i>
                            Terapkan ke Kamus
                        </button>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('kamus.versi.unggah.cancel', $version) }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-outline-soft btn-sm">
                    Batalkan Pratinjau
                </button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .upload-preview-table { min-width: 920px; }
        .upload-preview-table th, .upload-preview-table td { padding: 12px; font-size: 12px; vertical-align: middle; }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('[data-upload-apply]');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function (event) {
                var mode = form.querySelector('#uploadMode');
                var message = mode && mode.value === 'ganti'
                    ? 'Terapkan mode Ganti? Entri yang tidak ada pada berkas baru akan dihapus lunak dari versi ini.'
                    : 'Terapkan hasil pratinjau ke Kamus Acuan sekarang?';

                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
