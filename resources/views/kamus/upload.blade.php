@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Unggah Kamus'],
    ],
])

@section('title', 'Unggah Kamus Acuan '.$version->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan {{ $version->tahun }}"
        title="Unggah Kamus Acuan"
        description="Unggah CSV, Excel, atau PDF. Berkas dibaca, dipetakan, dan ditampilkan sebagai pratinjau sebelum ada data yang diterapkan."
    >
        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-danger app-alert mb-3" role="alert">
            <i class="ri-error-warning-line" aria-hidden="true"></i>
            <div>
                <strong>Berkas belum dapat diproses.</strong>
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
                    <form method="POST" action="{{ route('kamus.versi.unggah.preview', $version) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="upload-dropzone" data-upload-dropzone>
                            <span class="upload-dropzone-icon" aria-hidden="true">
                                <i class="ri-upload-cloud-2-line"></i>
                            </span>
                            <strong>Pilih atau seret berkas ke sini</strong>
                            <span class="upload-dropzone-hint">
                                CSV · XLSX · XLS · PDF · maksimal {{ $limits['effective_max_label'] }}
                            </span>

                            <input type="file"
                                   id="file"
                                   name="file"
                                   class="form-control mt-3 @error('file') is-invalid @enderror"
                                   accept=".csv,.txt,.xlsx,.xls,.pdf,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/pdf"
                                   required
                                   data-upload-input>
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="upload-file-name" data-upload-file-name></div>
                        </div>

                        <div class="demo-note mt-3 mb-3">
                            <i class="ri-shield-check-line" aria-hidden="true"></i>
                            <div>
                                Setelah diunggah, berkas hanya dibaca. Anda akan melihat pratinjau hasil pemetaan
                                dan dapat membatalkannya tanpa mengubah data Kamus Acuan.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-search-eye-line" aria-hidden="true"></i>
                            Baca &amp; Pratinjau
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Format yang Didukung</h2>
                        <p class="app-card-subtitle">Pemetaan kolom dilakukan otomatis.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    <ul class="upload-format-list">
                        @foreach ($formats as $format => $label)
                            <li>
                                <span class="upload-format-badge">{{ strtoupper($format) }}</span>
                                <span>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="demo-note mb-0 mt-3">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Kolom yang dikenali: <code>kode</code>, <code>klasifikasi</code>, <code>uraian</code>,
                            <code>kata kunci</code>, <code>sinonim</code>, <code>kriteria</code>,
                            <code>prioritas</code>, <code>tindak lanjut</code>, <code>komisi</code>.
                            Kolom seperti <code>Isu Strategis Daerah</code> atau <code>Kamus Usulan</code> juga
                            dipetakan sebagai klasifikasi. Bila kode tidak ada, sistem membuat kode otomatis.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Batas Unggah Aktual</h2>
                        <p class="app-card-subtitle">Batas aplikasi dan server berlaku bersamaan.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    <dl class="detail-list mb-0">
                        <div class="detail-row">
                            <dt>Batas aplikasi</dt>
                            <dd>{{ $limits['app_max_label'] }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Batas efektif</dt>
                            <dd><strong>{{ $limits['effective_max_label'] }}</strong></dd>
                        </div>
                        <div class="detail-row">
                            <dt>upload_max_filesize</dt>
                            <dd>{{ $limits['php_upload_max_filesize'] }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>post_max_size</dt>
                            <dd>{{ $limits['php_post_max_size'] }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>memory_limit</dt>
                            <dd>{{ $limits['php_memory_limit'] }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>max_execution_time</dt>
                            <dd>{{ $limits['php_max_execution_time'] }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Maksimum baris</dt>
                            <dd>{{ number_format($limits['max_rows'], 0, ',', '.') }} baris</dd>
                        </div>
                    </dl>
                    <p class="cell-muted mt-2 mb-0" style="font-size: 11px;">
                        Untuk berkas lebih besar, naikkan nilai PHP/web server terlebih dahulu. Sistem tidak
                        mengklaim unggahan tanpa batas.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .upload-dropzone { display: flex; flex-direction: column; align-items: center; padding: 26px 20px; border: 2px dashed #cfe0d8; border-radius: 18px; background: #fbfcfa; text-align: center; transition: border-color .18s ease, background .18s ease; }
        .upload-dropzone.is-dragging { border-color: #155043; background: #f1f8f4; }
        .upload-dropzone-icon { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 18px; background: #e6f0eb; color: #155043; font-size: 28px; margin-bottom: 10px; }
        .upload-dropzone strong { font-size: 13.5px; color: #193b32; }
        .upload-dropzone-hint { margin-top: 3px; font-size: 11px; color: #7b8781; }
        .upload-dropzone .form-control { max-width: 360px; }
        .upload-file-name { margin-top: 8px; font-size: 11.5px; font-weight: 700; color: #155043; }
        .upload-format-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .upload-format-list li { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #33443e; }
        .upload-format-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 54px; padding: 3px 8px; border-radius: 8px; background: #eef3ef; color: #155043; font-size: 10px; font-weight: 800; letter-spacing: .04em; }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var zone = document.querySelector('[data-upload-dropzone]');
            var input = document.querySelector('[data-upload-input]');
            var label = document.querySelector('[data-upload-file-name]');

            if (!zone || !input) {
                return;
            }

            function showName() {
                if (label && input.files && input.files.length > 0) {
                    label.textContent = input.files[0].name;
                }
            }

            input.addEventListener('change', showName);

            ['dragenter', 'dragover'].forEach(function (eventName) {
                zone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    zone.classList.add('is-dragging');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                zone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    zone.classList.remove('is-dragging');
                });
            });

            zone.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files.length > 0) {
                    input.files = event.dataTransfer.files;
                    showName();
                }
            });
        });
    </script>
@endpush
