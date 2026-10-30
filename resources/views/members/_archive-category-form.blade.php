@php
    $isEdit = $category->exists;
    $selectedActive = (bool) old('is_active', $category->is_active ?? true);
@endphp

<form method="POST"
      action="{{ $isEdit
          ? route('anggota.arsip.kategori.update', [$member, $category])
          : route('anggota.arsip.kategori.store', $member) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Metadata Kategori</h2>
                        <p class="app-card-subtitle">Informasi kategori yang tampil pada halaman arsip anggota.</p>
                    </div>
                    <span class="status-badge status-neutral">{{ $isEdit ? 'Edit kategori' : 'Kategori baru' }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="nama">Nama Kategori <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $category->nama ?? '') }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   required maxlength="100" placeholder="Contoh: HASIL RESES" autofocus>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="kode">Kode Kategori</label>
                            <input type="text" id="kode" name="kode"
                                   value="{{ old('kode', $category->kode ?? '') }}"
                                   class="form-control @error('kode') is-invalid @enderror"
                                   maxlength="20" placeholder="RESES">
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="deskripsi">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      rows="4" maxlength="1000"
                                      placeholder="Jelaskan jenis dokumen yang disimpan pada kategori ini.">{{ old('deskripsi', $category->deskripsi ?? '') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active" value="1"
                                       @checked($selectedActive)>
                                <label class="form-check-label" for="is_active">
                                    Kategori aktif dan dapat dipakai untuk arsip baru
                                </label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
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
                        <p class="app-card-subtitle">Pratinjau kategori arsip.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Nama</dt>
                            <dd>{{ old('nama', $category->nama ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kode</dt>
                            <dd>{{ old('kode', $category->kode ?? '—') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedActive ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-lock-2-line" aria-hidden="true"></i>
                        <div>Kategori yang masih digunakan oleh tahun anggaran atau dokumen tidak dapat dihapus.</div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                        </button>
                        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
