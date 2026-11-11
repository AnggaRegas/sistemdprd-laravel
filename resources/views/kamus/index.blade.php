@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan'],
    ],
])

@section('title', 'Kamus Acuan')

@section('content')
    <x-admin.page-header
        eyebrow="Pemeriksaan aspirasi"
        title="Kamus Acuan"
        description="Kelola data referensi yang digunakan untuk pemeriksaan aspirasi masyarakat dan perhitungan SPK."
    >
        @if ($selectedVersion || $activeVersion)
            <a href="{{ route('kamus.versi.unggah', $selectedVersion ?: $activeVersion) }}" class="btn btn-emerald">
                <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>
                Upload Kamus
            </a>
        @endif
        <a href="#kriteria-kamus" class="btn btn-outline-soft">
            <i class="ri-equalizer-line" aria-hidden="true"></i>
            Edit Kriteria
        </a>
        <a href="{{ route('kamus.versi.create') }}" class="btn btn-outline-soft">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Versi
        </a>
        <a href="{{ route('kamus.riwayat') }}" class="btn btn-outline-soft">
            <i class="ri-history-line" aria-hidden="true"></i>
            Riwayat Perubahan
        </a>
    </x-admin.page-header>

    @if (session('import_errors'))
        <div class="app-card mb-3">
            <div class="app-card-body">
                <div class="alert alert-warning app-alert mb-0" role="alert">
                    <i class="ri-alert-line" aria-hidden="true"></i>
                    <div>
                        <strong>Sebagian baris impor dilewati.</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach (session('import_errors') as $importError)
                                <li>{{ $importError }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Ringkasan Informasi Kamus (Clean Dashboard Cards) --}}
    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #e6f0eb; color: #155043;">
                            <i class="ri-book-open-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong class="d-block text-truncate" style="font-size: 14px;">
                                {{ $activeVersion ? $activeVersion->nama : ($selectedVersion ? $selectedVersion->nama : 'Belum Ada') }}
                            </strong>
                            <span class="text-muted" style="font-size: 11px;">
                                Versi Aktif ({{ $activeVersion?->tahun ?? ($selectedVersion?->tahun ?? '—') }})
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #e8f4fd; color: #0369a1;">
                            <i class="ri-equalizer-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong class="d-block" style="font-size: 14px;">
                                {{ $kriteria->count() }} Kriteria · {{ number_format($selectedVersion?->entri_count ?? ($activeVersion?->entri_count ?? 0), 0, ',', '.') }} Entri
                            </strong>
                            <span class="text-muted" style="font-size: 11px;">Jumlah Kriteria &amp; Data</span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fef3c7; color: #92400e;">
                            <i class="ri-time-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong class="d-block text-truncate" style="font-size: 13px;">
                                {{ $kriteriaTerakhirDiperbarui ? \App\Support\PortalData::dateTime($kriteriaTerakhirDiperbarui) : ($selectedVersion?->updated_at ? \App\Support\PortalData::dateTime($selectedVersion->updated_at) : '—') }}
                            </strong>
                            <span class="text-muted" style="font-size: 11px;">Terakhir Diperbarui</span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: {{ $activeVersion ? '#dcfce7' : '#f1f5f9' }}; color: {{ $activeVersion ? '#166534' : '#475569' }};">
                            <i class="{{ $activeVersion ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong class="d-block" style="font-size: 14px;">
                                <span class="status-badge {{ $activeVersion ? 'status-success' : 'status-neutral' }}">
                                    {{ $activeVersion ? 'Kamus Aktif' : 'Nonaktif' }}
                                </span>
                            </strong>
                            <span class="text-muted" style="font-size: 11px;">Status Kamus</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedVersion)
        {{-- Daftar Kriteria Kamus (SPK) --}}
        <div class="app-card mb-3" id="kriteria-kamus">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Kriteria &amp; Bobot Perhitungan (SPK)</h2>
                    <p class="app-card-subtitle">
                        Parameter ini dipakai langsung oleh mesin Kamus Acuan saat menghitung skor aspirasi.
                        Terakhir diperbarui:
                        <strong>{{ $kriteriaTerakhirDiperbarui ? \App\Support\PortalData::dateTime($kriteriaTerakhirDiperbarui) : '—' }}</strong>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="status-badge status-info">{{ $kriteria->count() }} kriteria</span>
                </div>
            </div>

            <div class="app-card-body">
                @if ($kriteria->isEmpty())
                    <x-admin.empty-state
                        icon="ri-equalizer-line"
                        title="Belum ada kriteria"
                        description="Tambahkan kriteria perhitungan agar bobot SPK dapat diatur tanpa mengubah kode program."
                    />
                @else
                    <div class="table-responsive">
                        <table class="table-modern kamus-kriteria-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 50px;">No</th>
                                    <th scope="col">Nama Kriteria</th>
                                    <th scope="col">Deskripsi</th>
                                    <th scope="col" style="width: 140px;">Bobot</th>
                                    <th scope="col" style="width: 110px;">Status</th>
                                    <th scope="col" style="width: 150px; text-align: right;"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kriteria as $item)
                                    <tr>
                                        <td><span class="cell-muted">{{ $loop->iteration }}</span></td>
                                        <td>
                                            <strong>{{ $item->nama }}</strong>
                                            <span class="cell-sub d-block" style="font-size: 10px;">{{ $item->faktor_label }} ({{ $item->kode }})</span>
                                        </td>
                                        <td>
                                            <span class="cell-sub" style="font-size: 11.5px; line-height: 1.6;">
                                                {{ $item->keterangan ?: ($item->faktor_keterangan ?: '—') }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ rtrim(rtrim(number_format($item->bobot, 3, '.', ''), '0'), '.') }}</strong>
                                            @if ($item->satuan)
                                                <span class="cell-sub ms-1">{{ $item->satuan }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge {{ $item->is_active ? 'status-success' : 'status-neutral' }}">
                                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <button type="button"
                                                        class="btn btn-outline-soft btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editKriteriaModal{{ $item->id }}">
                                                    <i class="ri-edit-line" aria-hidden="true"></i> Edit
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-soft btn-sm text-danger"
                                                        data-confirm-action
                                                        data-confirm-title="Hapus kriteria ini?"
                                                        data-confirm-message="Kriteria {{ $item->nama }} dihapus. Perhitungan berikutnya kembali memakai nilai bawaan."
                                                        data-confirm-label="Hapus kriteria"
                                                        data-confirm-form="#deleteKriteria{{ $item->id }}">
                                                    <i class="ri-delete-bin-line" aria-hidden="true"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Modals Edit Kriteria --}}
                @foreach ($kriteria as $item)
                    <div class="modal fade" id="editKriteriaModal{{ $item->id }}" tabindex="-1" aria-labelledby="editKriteriaLabel{{ $item->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editKriteriaLabel{{ $item->id }}">
                                        Edit Kriteria: {{ $item->nama }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <form method="POST" action="{{ route('kamus.kriteria.update', $item) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label" for="kriteriaNamaInput{{ $item->id }}">Nama Kriteria</label>
                                            <input id="kriteriaNamaInput{{ $item->id }}"
                                                   type="text"
                                                   name="nama"
                                                   value="{{ $item->nama }}"
                                                   maxlength="120"
                                                   class="form-control"
                                                   required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="kriteriaBobotInput{{ $item->id }}">
                                                Bobot @if ($item->satuan) ({{ $item->satuan }}) @endif
                                            </label>
                                            <input id="kriteriaBobotInput{{ $item->id }}"
                                                   type="number"
                                                   name="bobot"
                                                   value="{{ rtrim(rtrim(number_format($item->bobot, 3, '.', ''), '0'), '.') }}"
                                                   step="0.001"
                                                   min="0"
                                                   max="999"
                                                   class="form-control"
                                                   required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="kriteriaKetInput{{ $item->id }}">Deskripsi / Keterangan</label>
                                            <textarea id="kriteriaKetInput{{ $item->id }}"
                                                      name="keterangan"
                                                      rows="3"
                                                      maxlength="1000"
                                                      class="form-control">{{ $item->keterangan }}</textarea>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="activeSwitch{{ $item->id }}"
                                                   @checked($item->is_active)>
                                            <label class="form-check-label" for="activeSwitch{{ $item->id }}">
                                                Kriteria Aktif untuk Perhitungan SPK
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-emerald">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <form id="deleteKriteria{{ $item->id }}" method="POST"
                          action="{{ route('kamus.kriteria.destroy', $item) }}" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach

                @if ($kriteriaFaktorTersedia->isNotEmpty())
                    <div class="kamus-kriteria-add mt-4">
                        <h3 class="app-card-title mb-1">Tambah Kriteria</h3>
                        <p class="cell-muted mb-3" style="font-size: 11px;">
                            Pilih faktor perhitungan yang belum dipakai pada versi {{ $selectedVersion->tahun }}.
                            Perubahan berlaku pada pemeriksaan berikutnya dan tidak mengubah hasil historis.
                        </p>

                        <form method="POST" action="{{ route('kamus.kriteria.store', $selectedVersion) }}" class="row g-2 align-items-end" data-kriteria-form>
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label" for="kriteriaKode">Faktor perhitungan</label>
                                <select id="kriteriaKode" name="kode" class="form-select" required data-kriteria-select>
                                    @foreach ($kriteriaFaktorTersedia as $kode => $faktor)
                                        <option value="{{ $kode }}"
                                                data-nama="{{ $faktor['nama'] }}"
                                                data-bobot="{{ $faktor['bobot'] }}"
                                                data-keterangan="{{ $faktor['keterangan'] }}">
                                            {{ $faktor['nama'] }} ({{ $faktor['satuan'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="kriteriaNama">Nama tampilan</label>
                                <input id="kriteriaNama" type="text" name="nama" maxlength="120" class="form-control" data-kriteria-nama>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="kriteriaBobot">Bobot</label>
                                <input id="kriteriaBobot" type="number" name="bobot" step="0.001" min="0" max="999" class="form-control" required data-kriteria-bobot>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-emerald w-100">
                                    <i class="ri-add-line" aria-hidden="true"></i>
                                    Tambah Kriteria
                                </button>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="kriteriaKeterangan">Keterangan</label>
                                <textarea id="kriteriaKeterangan" name="keterangan" rows="2" maxlength="1000" class="form-control" data-kriteria-keterangan></textarea>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                        <div>Seluruh faktor perhitungan yang didukung sudah tersedia pada versi ini.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Entri Kamus Acuan --}}
        <div class="app-card mb-3">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Entri Kamus {{ $selectedVersion->tahun }}</h2>
                    <p class="app-card-subtitle">
                        {{ number_format($entries?->total() ?? 0, 0, ',', '.') }} entri pada versi {{ $selectedVersion->nama }}.
                        @if (! $selectedVersion->is_active)
                            Versi ini tidak aktif; entri tetap dapat disiapkan sebelum diaktifkan.
                        @endif
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('kamus.versi.unggah', $selectedVersion) }}" class="btn btn-emerald btn-sm">
                        <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>
                        Upload Kamus
                    </a>
                    <a href="{{ route('kamus.entri.create', $selectedVersion) }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-add-line" aria-hidden="true"></i>
                        Tambah Entri
                    </a>
                    <a href="{{ route('kamus.versi.impor', $selectedVersion) }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-upload-2-line" aria-hidden="true"></i>
                        Impor CSV
                    </a>
                    <a href="{{ route('kamus.versi.ekspor', $selectedVersion) }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-download-2-line" aria-hidden="true"></i>
                        Ekspor CSV
                    </a>
                </div>
            </div>

            <div class="app-card-body">
                <form method="GET" action="{{ route('kamus.index') }}" class="filter-bar mb-3" role="search">
                    <input type="hidden" name="versi" value="{{ $selectedVersion->id }}">
                    <div class="filter-search">
                        <i class="ri-search-line" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari kode, klasifikasi, uraian, atau kata kunci" aria-label="Cari entri kamus">
                    </div>

                    <select name="komisi" class="filter-select" data-auto-submit aria-label="Filter komisi">
                        <option value="">Semua komisi</option>
                        @foreach ($commissions as $commission)
                            <option value="{{ $commission->id }}" @selected((string) $filters['komisi'] === (string) $commission->id)>{{ $commission->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="filter-select" data-auto-submit aria-label="Filter status entri">
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                    </select>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-emerald btn-sm">Terapkan</button>
                        <a href="{{ route('kamus.index', ['versi' => $selectedVersion->id]) }}" class="filter-reset">Reset</a>
                    </div>
                </form>

                @if ($entries->isEmpty())
                    <x-admin.empty-state
                        icon="ri-book-2-line"
                        title="Belum ada entri"
                        description="Tambahkan entri secara manual atau impor dari berkas CSV."
                    />
                @else
                    <div class="table-responsive">
                        <table class="table-modern kamus-entry-table">
                            <thead>
                                <tr>
                                    <th scope="col">Kode</th>
                                    <th scope="col">Klasifikasi</th>
                                    <th scope="col" class="d-none d-md-table-cell">Komisi</th>
                                    <th scope="col" class="d-none d-lg-table-cell">Kata kunci</th>
                                    <th scope="col">Prioritas</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    @php
                                        $terms = collect($entry->kata_kunci_list)->pluck('term')->take(4)->implode(', ');
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $entry->kode }}</strong></td>
                                        <td>
                                            {{ $entry->klasifikasi }}
                                            @if ($entry->uraian)
                                                <span class="cell-sub d-block">{{ \Illuminate\Support\Str::limit($entry->uraian, 90) }}</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell"><span class="cell-muted">{{ $entry->commission?->name ?: '—' }}</span></td>
                                        <td class="d-none d-lg-table-cell">
                                            <span class="cell-sub d-block">{{ $entry->kata_kunci_count }} istilah · {{ $entry->sinonim_count }} sinonim</span>
                                            <span class="cell-muted" style="font-size: 10px;">{{ $terms ?: 'Belum ada kata kunci' }}</span>
                                        </td>
                                        <td>{{ $entry->prioritas_label }}</td>
                                        <td><span class="status-badge {{ $entry->is_active ? 'status-success' : 'status-neutral' }}">{{ $entry->status_label }}</span></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <a href="{{ route('kamus.entri.edit', $entry) }}" class="btn btn-outline-soft btn-sm">Edit</a>
                                                <form method="POST" action="{{ route('kamus.entri.status', $entry) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-soft btn-sm">{{ $entry->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                </form>
                                                <button type="button" class="btn btn-outline-soft btn-sm text-danger"
                                                        data-confirm-action
                                                        data-confirm-title="Hapus entri ini?"
                                                        data-confirm-message="Entri {{ $entry->kode }} akan dihapus secara lunak. Hasil pemeriksaan lama tetap tersimpan dan tidak berubah."
                                                        data-confirm-label="Hapus entri"
                                                        data-confirm-form="#deleteKamusEntry{{ $entry->id }}">
                                                    Hapus
                                                </button>
                                            </div>

                                            <form id="deleteKamusEntry{{ $entry->id }}" method="POST"
                                                  action="{{ route('kamus.entri.destroy', $entry) }}" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <p class="table-footer-note">Menampilkan <strong>{{ $entries->firstItem() }}–{{ $entries->lastItem() }}</strong> dari <strong>{{ number_format($entries->total(), 0, ',', '.') }}</strong> entri</p>
                        <x-admin.pagination :paginator="$entries" />
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Versi Kamus --}}
    <div class="app-card mb-3">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Versi Kamus</h2>
                <p class="app-card-subtitle">{{ $versions->count() }} versi tersimpan · {{ number_format($totalReports, 0, ',', '.') }} laporan masyarakat</p>
            </div>
            <span class="status-badge {{ $activeVersion ? 'status-success' : 'status-warning' }}">
                {{ $activeVersion ? 'Aktif: '.$activeVersion->tahun : 'Belum ada versi aktif' }}
            </span>
        </div>

        @if ($versions->isEmpty())
            <x-admin.empty-state
                icon="ri-book-open-line"
                title="Belum ada Kamus Acuan"
                description="Tambahkan versi pertama untuk mulai menyusun klasifikasi dan kata kunci acuan."
            >
                <a href="{{ route('kamus.versi.create') }}" class="btn btn-emerald btn-sm">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    Tambah Versi
                </a>
            </x-admin.empty-state>
        @else
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th scope="col">Tahun</th>
                            <th scope="col">Nama versi</th>
                            <th scope="col">Entri</th>
                            <th scope="col">Status</th>
                            <th scope="col">Ditetapkan</th>
                            <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($versions as $version)
                            <tr class="{{ $selectedVersion?->id === $version->id ? 'is-selected' : '' }}">
                                <td><strong>{{ $version->tahun }}</strong></td>
                                <td>
                                    {{ $version->nama }}
                                    @if ($selectedVersion?->id === $version->id)
                                        <span class="status-badge status-info ms-1">Dipilih</span>
                                    @endif
                                </td>
                                <td>{{ number_format($version->entri_count, 0, ',', '.') }}</td>
                                <td>
                                    <span class="status-badge {{ $version->is_active ? 'status-success' : 'status-neutral' }}">{{ $version->status_label }}</span>
                                </td>
                                <td>
                                    <span class="cell-muted">
                                        {{ $version->ditetapkan_at ? \App\Support\PortalData::dateTime($version->ditetapkan_at) : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft btn-sm">Pilih</a>
                                        <a href="{{ route('kamus.versi.edit', $version) }}" class="btn btn-outline-soft btn-sm">Edit</a>
                                        @if (! $version->is_active)
                                            <form method="POST" action="{{ route('kamus.versi.aktifkan', $version) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-emerald btn-sm">Aktifkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .kamus-entry-table { min-width: 860px; }
        .kamus-entry-table th, .kamus-entry-table td { padding: 14px 12px; font-size: 12.5px; vertical-align: middle; }
        .table-modern tr.is-selected { background: #f3f8f5; }
        .kamus-kriteria-table { min-width: 780px; }
        .kamus-kriteria-table th, .kamus-kriteria-table td { padding: 12px; font-size: 12.5px; vertical-align: middle; }
        .kamus-kriteria-add { padding: 18px; border: 1px dashed #d7e2dc; border-radius: 16px; background: #fbfcfa; }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-kriteria-form]').forEach(function (form) {
                var select = form.querySelector('[data-kriteria-select]');
                var nama = form.querySelector('[data-kriteria-nama]');
                var bobot = form.querySelector('[data-kriteria-bobot]');
                var keterangan = form.querySelector('[data-kriteria-keterangan]');

                function sync() {
                    var option = select && select.options[select.selectedIndex];

                    if (!option) {
                        return;
                    }

                    if (nama) {
                        nama.value = option.dataset.nama || '';
                    }

                    if (bobot) {
                        bobot.value = option.dataset.bobot || '';
                    }

                    if (keterangan) {
                        keterangan.value = option.dataset.keterangan || '';
                    }
                }

                if (select) {
                    select.addEventListener('change', sync);
                    sync();
                }
            });
        });
    </script>
@endpush
