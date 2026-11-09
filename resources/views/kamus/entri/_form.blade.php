@php
    $isEdit = isset($entry) && $entry?->exists;
    $selectedStatus = (string) old('is_active', $isEdit ? ($entry->is_active ? '1' : '0') : '1');
@endphp

<form method="POST" action="{{ $isEdit ? route('kamus.entri.update', $entry) : route('kamus.entri.store', $version) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Identitas Entri</h2>
                        <p class="app-card-subtitle">Kode dan klasifikasi yang akan ditampilkan pada hasil pemeriksaan.</p>
                    </div>
                    <span class="status-badge status-info">Kamus {{ $version->tahun }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="kode">Kode <span class="req">*</span></label>
                            <input type="text" id="kode" name="kode"
                                   value="{{ old('kode', $entry->kode ?? '') }}"
                                   class="form-control @error('kode') is-invalid @enderror"
                                   required maxlength="50" placeholder="Contoh: KOM-01">
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unik dalam satu versi kamus.</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="klasifikasi">Klasifikasi <span class="req">*</span></label>
                            <input type="text" id="klasifikasi" name="klasifikasi"
                                   value="{{ old('klasifikasi', $entry->klasifikasi ?? '') }}"
                                   class="form-control @error('klasifikasi') is-invalid @enderror"
                                   required maxlength="180" placeholder="Contoh: Infrastruktur jalan dan drainase">
                            @error('klasifikasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="dprd_commission_id">Komisi Terkait</label>
                            <select id="dprd_commission_id" name="dprd_commission_id"
                                    class="form-select @error('dprd_commission_id') is-invalid @enderror">
                                <option value="">Tidak terkait komisi tertentu</option>
                                @foreach ($commissions as $commission)
                                    <option value="{{ $commission->id }}" @selected((string) old('dprd_commission_id', $entry->dprd_commission_id ?? '') === (string) $commission->id)>
                                        {{ $commission->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dprd_commission_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="is_active">Status Entri <span class="req">*</span></label>
                            <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                                <option value="1" @selected($selectedStatus === '1')>Aktif — dipakai saat pencocokan</option>
                                <option value="0" @selected($selectedStatus === '0')>Nonaktif — tidak dipakai</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="uraian">Uraian</label>
                            <textarea id="uraian" name="uraian" rows="4" maxlength="5000"
                                      class="form-control @error('uraian') is-invalid @enderror"
                                      placeholder="Penjelasan ringkas mengenai cakupan klasifikasi ini.">{{ old('uraian', $entry->uraian ?? '') }}</textarea>
                            @error('uraian')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Istilah Pencocokan</h2>
                        <p class="app-card-subtitle">Kata kunci, sinonim, kode, dan klasifikasi dicocokkan sebagai kata/frasa utuh. Uraian dan kriteria tidak dipecah menjadi kata.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="kata_kunci_text">Kata Kunci</label>
                            <textarea id="kata_kunci_text" name="kata_kunci_text" rows="10" maxlength="50000"
                                      class="form-control @error('kata_kunci_text') is-invalid @enderror"
                                      placeholder="Contoh:&#10;jalan|3&#10;jalan rusak|3&#10;drainase|2">{{ old('kata_kunci_text', $isEdit ? $entry->kata_kunci_text : '') }}</textarea>
                            @error('kata_kunci_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Satu istilah per baris. Tambahkan <code>|bobot</code> 1–10. Baris tanpa bobot memakai bobot 2.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sinonim_text">Sinonim</label>
                            <textarea id="sinonim_text" name="sinonim_text" rows="10" maxlength="50000"
                                      class="form-control @error('sinonim_text') is-invalid @enderror"
                                      placeholder="Contoh:&#10;jalanan|2&#10;aspal rusak|2&#10;gorong gorong|2">{{ old('sinonim_text', $isEdit ? $entry->sinonim_text : '') }}</textarea>
                            @error('sinonim_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Sinonim memperluas pencocokan tanpa mengubah kata kunci utama.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="kriteria">Kriteria</label>
                            <textarea id="kriteria" name="kriteria" rows="3" maxlength="5000"
                                      class="form-control @error('kriteria') is-invalid @enderror"
                                      placeholder="Kriteria yang dapat diverifikasi manusia. Tidak dipakai untuk pencocokan otomatis.">{{ old('kriteria', $entry->kriteria ?? '') }}</textarea>
                            @error('kriteria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Tindak Lanjut</h2>
                        <p class="app-card-subtitle">Prioritas tidak dibuat otomatis oleh sistem. Isi hanya jika kebijakan prioritas sudah ditetapkan.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="prioritas">Prioritas</label>
                            <input type="text" id="prioritas" name="prioritas"
                                   value="{{ old('prioritas', $entry->prioritas ?? '') }}"
                                   class="form-control @error('prioritas') is-invalid @enderror"
                                   maxlength="100" placeholder="Contoh: Tinggi / Segera">
                            @error('prioritas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="tindak_lanjut">Tindak Lanjut</label>
                            <textarea id="tindak_lanjut" name="tindak_lanjut" rows="3" maxlength="5000"
                                      class="form-control @error('tindak_lanjut') is-invalid @enderror"
                                      placeholder="Arahan tindak lanjut umum untuk klasifikasi ini.">{{ old('tindak_lanjut', $entry->tindak_lanjut ?? '') }}</textarea>
                            @error('tindak_lanjut')
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
                        <p class="app-card-subtitle">Entri ini hanya berlaku pada versi {{ $version->tahun }}.</p>
                    </div>
                </div>
                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Versi</dt>
                            <dd>{{ $version->nama }} ({{ $version->tahun }})</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kata kunci</dt>
                            <dd>{{ $isEdit ? $entry->kata_kunci_count : 0 }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Sinonim</dt>
                            <dd>{{ $isEdit ? $entry->sinonim_count : 0 }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>Bobot istilah menunjukkan kekuatan pencocokan, bukan prioritas tindak lanjut.</div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Entri' }}
                        </button>
                        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Kembali ke Daftar Entri
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
