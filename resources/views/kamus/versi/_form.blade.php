@php
    $isEdit = isset($version) && $version?->exists;
    $selectedStatus = (string) old('is_active', $isEdit ? ($version->is_active ? '1' : '0') : '1');
@endphp

<form method="POST" action="{{ $isEdit ? route('kamus.versi.update', $version) : route('kamus.versi.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Versi</h2>
                        <p class="app-card-subtitle">Satu versi mewakili satu tahun kamus. Versi lama tetap tersimpan untuk riwayat.</p>
                    </div>
                    <span class="status-badge {{ $selectedStatus === '1' ? 'status-success' : 'status-neutral' }}">
                        {{ $selectedStatus === '1' ? 'Akan aktif' : 'Tidak aktif' }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="tahun">Tahun <span class="req">*</span></label>
                            <input type="number" id="tahun" name="tahun"
                                   value="{{ old('tahun', $version->tahun ?? now()->format('Y')) }}"
                                   class="form-control @error('tahun') is-invalid @enderror"
                                   required min="2000" max="2100" inputmode="numeric">
                            @error('tahun')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Hanya satu versi untuk setiap tahun.</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="nama">Nama Versi <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $version->nama ?? 'Kamus Acuan '.now()->format('Y')) }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   required maxlength="150">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="keterangan">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="4" maxlength="2000"
                                      class="form-control @error('keterangan') is-invalid @enderror"
                                      placeholder="Catatan dasar penyusunan versi kamus ini.">{{ old('keterangan', $version->keterangan ?? '') }}</textarea>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="is_active">Status Versi <span class="req">*</span></label>
                            <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                                <option value="1" @selected($selectedStatus === '1')>Aktif — dipakai untuk pemeriksaan otomatis</option>
                                <option value="0" @selected($selectedStatus === '0')>Nonaktif — hanya disimpan sebagai riwayat</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Mengaktifkan versi ini akan menonaktifkan versi lain.</div>
                        </div>

                        @if (! $isEdit)
                            <div class="col-md-6">
                                <label class="form-label" for="salin_dari">Salin Entri Dari Versi</label>
                                <select id="salin_dari" name="salin_dari" class="form-select @error('salin_dari') is-invalid @enderror">
                                    <option value="">Tidak menyalin entri</option>
                                    @foreach ($versions as $source)
                                        <option value="{{ $source->id }}" @selected((string) old('salin_dari') === (string) $source->id)>
                                            {{ $source->tahun }} — {{ $source->nama }} ({{ $source->entri_count ?? $source->entri()->count() }} entri)
                                        </option>
                                    @endforeach
                                </select>
                                @error('salin_dari')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Salinan memudahkan penyusunan tahun baru. Hasil pemeriksaan lama tidak berubah sampai diperiksa ulang.</div>
                            </div>
                        @endif
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Perubahan versi tidak mengubah hasil pemeriksaan laporan lama. Pemeriksaan ulang hanya memperbarui hasil otomatis dan tidak menimpa keputusan manual Super Admin.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Ringkasan</h2>
                        <p class="app-card-subtitle">Dampak versi terhadap pemeriksaan.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Tahun</dt>
                            <dd>{{ old('tahun', $version->tahun ?? now()->format('Y')) }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jumlah entri</dt>
                            <dd>{{ $isEdit ? $version->entri_count : 0 }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-shield-check-line" aria-hidden="true"></i>
                        <div>Hanya Super Admin yang dapat mengelola Kamus Acuan.</div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Buat Versi' }}
                        </button>
                        <a href="{{ route('kamus.index', $isEdit ? ['versi' => $version->id] : []) }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Kembali ke Kamus Acuan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
