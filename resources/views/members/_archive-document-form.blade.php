@php
    $isEdit = $document->exists;
    $selectedJenis = old('jenis', $document->jenis ?? App\Models\Arsip::JENIS_MASUK);
    $selectedTanggal = old('tanggal', $document->tanggal?->toDateString() ?? now()->toDateString());
@endphp

<form method="POST"
      enctype="multipart/form-data"
      action="{{ $isEdit
          ? route('anggota.arsip.dokumen.update', [$member, $category, $archiveYear, $folder, $document])
          : route('anggota.arsip.dokumen.store', [$member, $category, $archiveYear, $folder]) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Metadata Dokumen</h2>
                        <p class="app-card-subtitle">Informasi dokumen yang tampil pada daftar berkas arsip.</p>
                    </div>
                    <span class="status-badge status-neutral">{{ $isEdit ? 'Edit dokumen' : 'Dokumen baru' }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="nama">Judul Dokumen <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $document->nama ?? '') }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   required maxlength="200"
                                   placeholder="Contoh: Laporan Hasil Reses Kelurahan A" autofocus>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="jenis">Jenis Dokumen <span class="req">*</span></label>
                            <select id="jenis" name="jenis"
                                    class="form-select @error('jenis') is-invalid @enderror" required>
                                @foreach (App\Models\Arsip::JENIS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedJenis === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('jenis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal">Tanggal Dokumen</label>
                            <input type="date" id="tanggal" name="tanggal"
                                   value="{{ $selectedTanggal }}"
                                   class="form-control @error('tanggal') is-invalid @enderror">
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="deskripsi">Deskripsi / Keterangan</label>
                            <textarea id="deskripsi" name="deskripsi"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      rows="4" maxlength="2000"
                                      placeholder="Catatan singkat mengenai isi atau asal dokumen.">{{ old('deskripsi', $document->deskripsi ?? '') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="file">
                                Berkas Dokumen
                                @if (! $isEdit)
                                    <span class="req">*</span>
                                @endif
                            </label>
                            <input type="file" id="file" name="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt"
                                   @if (! $isEdit) required @endif>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Maksimal 10 MB. Format: PDF, Word, Excel, PowerPoint, JPG, PNG, WEBP, atau TXT.
                                @if ($isEdit && $document->file_path)
                                    Biarkan kosong untuk mempertahankan berkas saat ini.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($isEdit && $document->file_path)
                <div class="app-card">
                    <div class="app-card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-attachment-2 text-gold" aria-hidden="true"></i>
                                <div>
                                    <strong class="fs-11 text-ink">{{ $document->file_name ?: 'Berkas dokumen' }}</strong>
                                    <div class="text-muted-soft fs-10">
                                        {{ $document->file_size_label }} · tersimpan pada penyimpanan privat.
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('anggota.arsip.dokumen.download', [$member, $category, $archiveYear, $folder, $document]) }}"
                               class="btn btn-outline-soft btn-sm">
                                <i class="ri-download-2-line" aria-hidden="true"></i>
                                Unduh Berkas
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Ringkasan</h2>
                        <p class="app-card-subtitle">Pratinjau metadata dokumen.</p>
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
                            <dd>{{ $folder->nama }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Dokumen</dt>
                            <dd>{{ old('nama', $document->nama ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jenis</dt>
                            <dd>{{ App\Models\Arsip::JENIS_LABELS[$selectedJenis] ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-lock-2-line" aria-hidden="true"></i>
                        <div>Berkas disimpan pada penyimpanan privat dan hanya dapat diunduh oleh pengguna yang berwenang.</div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Dokumen' }}}
                        </button>
                        <a href="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
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
