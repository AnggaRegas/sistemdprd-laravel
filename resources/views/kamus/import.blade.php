@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Impor CSV'],
    ],
])

@section('title', 'Impor CSV Kamus '.$version->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan {{ $version->tahun }}"
        title="Impor Entri CSV"
        description="Impor massal menggunakan berkas CSV. Baris dengan kode yang sama pada versi ini akan diperbarui."
    >
        <a href="{{ route('kamus.templat') }}" class="btn btn-outline-soft">
            <i class="ri-file-download-line" aria-hidden="true"></i>
            Unduh Templat
        </a>
        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-danger app-alert mb-3" role="alert">
            <i class="ri-error-warning-line" aria-hidden="true"></i>
            <div>
                <strong>Impor belum dapat diproses.</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Unggah Berkas</h2>
                        <p class="app-card-subtitle">Versi tujuan: {{ $version->nama }} ({{ $version->tahun }}).</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <form method="POST" action="{{ route('kamus.versi.impor.store', $version) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="file">Berkas CSV <span class="req">*</span></label>
                            <input type="file" id="file" name="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".csv,text/csv,text/plain" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maksimal 5 MB. Gunakan pemisah koma atau titik koma.</div>
                        </div>

                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-upload-2-line" aria-hidden="true"></i>
                            Impor Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Format Kolom</h2>
                        <p class="app-card-subtitle">Baris pertama harus berupa header.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th scope="col">Kolom</th>
                                    <th scope="col">Wajib</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>kode</code></td><td>Ya</td></tr>
                                <tr><td><code>klasifikasi</code></td><td>Ya</td></tr>
                                <tr><td><code>uraian</code></td><td>Tidak</td></tr>
                                <tr><td><code>kata_kunci</code></td><td>Tidak</td></tr>
                                <tr><td><code>sinonim</code></td><td>Tidak</td></tr>
                                <tr><td><code>kriteria</code></td><td>Tidak</td></tr>
                                <tr><td><code>prioritas</code></td><td>Tidak</td></tr>
                                <tr><td><code>tindak_lanjut</code></td><td>Tidak</td></tr>
                                <tr><td><code>komisi</code></td><td>Tidak</td></tr>
                                <tr><td><code>is_active</code></td><td>Tidak</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="demo-note mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Kata kunci dan sinonim ditulis dalam satu sel, dipisahkan titik koma.
                            Contoh: <code>jalan|3; jalan rusak|3; drainase|2</code>.
                            Kolom komisi dapat berisi nama komisi, slug, atau ID. Prioritas tidak dibuat otomatis.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
