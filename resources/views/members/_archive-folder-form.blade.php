@php
    $isEdit = $folder->exists;
@endphp

<form method="POST"
      action="{{ $isEdit
          ? route('anggota.arsip.folder.update', [$member, $category, $archiveYear, $folder])
          : route('anggota.arsip.folder.store', [$member, $category, $archiveYear]) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Metadata Folder</h2>
                        <p class="app-card-subtitle">Nama folder sebaiknya menggambarkan kegiatan atau keperluan pengarsipan.</p>
                    </div>
                    <span class="status-badge status-neutral">{{ $isEdit ? 'Edit folder' : 'Folder baru' }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="nama">Nama Folder <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $folder->nama ?? '') }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   required maxlength="150"
                                   placeholder="Contoh: Reses Kelurahan A" autofocus>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="deskripsi">Keterangan</label>
                            <textarea id="deskripsi" name="deskripsi"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      rows="4" maxlength="1000"
                                      placeholder="Jelaskan isi atau tujuan folder ini.">{{ old('deskripsi', $folder->deskripsi ?? '') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
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
                        <p class="app-card-subtitle">Konteks folder arsip.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Anggota</dt>
                            <dd>{{ $member->nama_lengkap }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kategori</dt>
                            <dd>{{ $category->nama }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tahun</dt>
                            <dd>{{ $archiveYear->tahun }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Folder</dt>
                            <dd>{{ old('nama', $folder->nama ?? 'Belum diisi') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Folder' }}
                        </button>
                        <a href="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}"
                           class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
