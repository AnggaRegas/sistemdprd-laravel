@php
    $isEdit = $archiveYear->exists;
@endphp

<form method="POST"
      action="{{ $isEdit
          ? route('anggota.arsip.tahun.update', [$member, $category, $archiveYear])
          : route('anggota.arsip.tahun.store', [$member, $category]) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Tahun Anggaran</h2>
                        <p class="app-card-subtitle">Gunakan tahun yang sesuai dengan periode dokumen.</p>
                    </div>
                    <span class="status-badge status-neutral">{{ $isEdit ? 'Edit tahun' : 'Tahun baru' }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tahun">Tahun <span class="req">*</span></label>
                            <input type="number" id="tahun" name="tahun"
                                   value="{{ old('tahun', $archiveYear->tahun ?? '') }}"
                                   class="form-control @error('tahun') is-invalid @enderror"
                                   required min="1900" max="2100" step="1" inputmode="numeric"
                                   placeholder="{{ now()->format('Y') }}" autofocus>
                            @error('tahun')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tahun harus berupa angka 4 digit, misalnya 2024, 2025, atau 2026.</div>
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
                        <p class="app-card-subtitle">Konteks arsip yang sedang dikelola.</p>
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
                            <dd>{{ old('tahun', $archiveYear->tahun ?? 'Belum diisi') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Tahun' }}
                        </button>
                        <a href="{{ route('anggota.arsip.tahun', [$member, $category]) }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
