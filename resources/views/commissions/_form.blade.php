@php
    $isEdit = isset($commission) && $commission->exists;
    $selectedStatus = (string) old('is_active', $isEdit ? ($commission->is_active ? '1' : '0') : '1');
    $keywordsText = old('keywords_text', $isEdit ? $commission->keywords_text : '');
@endphp

<form method="POST" action="{{ $isEdit ? route('komisi.update', $commission) : route('komisi.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Komisi</h2>
                        <p class="app-card-subtitle">Nama, bidang, deskripsi, dan kata kunci klasifikasi aspirasi.</p>
                    </div>
                    <span class="status-badge {{ $selectedStatus === '1' ? 'status-success' : 'status-danger' }}">
                        {{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Komisi <span class="req">*</span></label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name', $commission->name ?? '') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required maxlength="100" autofocus placeholder="Contoh: Komisi 1">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Slug folder dibuat otomatis dari nama komisi.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="field">Bidang <span class="req">*</span></label>
                            <input type="text" id="field" name="field"
                                   value="{{ old('field', $commission->field ?? '') }}"
                                   class="form-control @error('field') is-invalid @enderror"
                                   maxlength="180" placeholder="Contoh: Pemerintahan, Hukum, dan Ketertiban">
                            @error('field')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      maxlength="2000" rows="5"
                                      placeholder="Jelaskan ruang lingkup dan tugas komisi ini.">{{ old('description', $commission->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="keywords_text">Kata Kunci Klasifikasi</label>
                            <textarea id="keywords_text" name="keywords_text"
                                      class="form-control @error('keywords_text') is-invalid @enderror"
                                      rows="8" maxlength="20000"
                                      placeholder="Contoh:&#10;jalan|3&#10;lampu jalan|3&#10;drainase|3&#10;pelayanan publik|2">{{ $keywordsText }}</textarea>
                            @error('keywords_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Satu kata kunci atau frasa per baris. Tambahkan <code>|bobot</code> dengan bobot 1–10.
                                Baris tanpa bobot memakai bobot 2. Kata kunci dipakai untuk rekomendasi komisi
                                berbasis aturan, bukan AI. Rekomendasi tidak menugaskan staf secara otomatis.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="is_active">Status Komisi <span class="req">*</span></label>
                            <select id="is_active" name="is_active"
                                    class="form-select @error('is_active') is-invalid @enderror" required>
                                <option value="1" @selected($selectedStatus === '1')>Aktif — dapat menerima RDP baru</option>
                                <option value="0" @selected($selectedStatus === '0')>Nonaktif — tidak menerima RDP baru</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Komisi yang masih memiliki RDP tidak dapat dihapus. Nonaktifkan komisi untuk menutup
                            penerimaan RDP baru tanpa menghilangkan arsip lama.
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
                        <p class="app-card-subtitle">Relasi arsip komisi.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>RDP tersimpan</dt>
                            <dd>{{ $isEdit ? $commission->rdp_count : 0 }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Dokumen RDP</dt>
                            <dd>{{ $isEdit ? $commission->document_count : 0 }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kata kunci</dt>
                            <dd>{{ $isEdit ? count($commission->classificationKeywords()) : 0 }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-shield-check-line" aria-hidden="true"></i>
                        <div>
                            Hanya administrator yang dapat mengelola komisi, RDP, dan dokumen arsip.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Komisi' }}
                        </button>
                        <a href="{{ $isEdit ? route('komisi.rdp.index', $commission) : route('komisi.index') }}"
                           class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Buka Folder RDP' : 'Kembali ke Daftar Komisi' }}
                        </a>
                        <a href="{{ route('komisi.index') }}" class="btn btn-outline-soft">
                            <i class="ri-government-line" aria-hidden="true"></i>
                            Daftar Komisi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
