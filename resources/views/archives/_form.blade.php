@php
    $isEdit = $archive->exists;
    $selectedMember = old('dprd_member_id', $archive->dprd_member_id ?? $selectedMemberId ?? '');
    $selectedKategori = old('kategori_arsip_id', $archive->kategori_arsip_id ?? '');
    $selectedJenis = old('jenis', $archive->jenis ?? App\Models\Arsip::JENIS_MASUK);
    $selectedStatus = old('status', $archive->status ?? App\Models\Arsip::STATUS_DRAF);
    $selectedTanggal = old('tanggal', $archive->tanggal?->toDateString() ?? now()->toDateString());
@endphp

<form method="POST"
      action="{{ $isEdit ? route('arsip.update', $archive) : route('arsip.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Metadata Arsip</h2>
                        <p class="app-card-subtitle">Informasi dokumen yang akan ditampilkan pada daftar arsip dan halaman anggota.</p>
                    </div>
                    <span class="status-badge status-neutral">{{ $isEdit ? 'Edit arsip' : 'Arsip baru' }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nomor">Nomor Arsip <span class="req">*</span></label>
                            <input type="text" id="nomor" name="nomor"
                                   value="{{ old('nomor', $archive->nomor ?? '') }}"
                                   class="form-control @error('nomor') is-invalid @enderror"
                                   required maxlength="100" placeholder="090/DPRD-PRP/X/2026" autofocus>
                            @error('nomor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kategori_arsip_id">Kategori <span class="req">*</span></label>
                            <select id="kategori_arsip_id" name="kategori_arsip_id"
                                    class="form-select @error('kategori_arsip_id') is-invalid @enderror" required>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $selectedKategori === (string) $category->id)>
                                        {{ $category->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kategori_arsip_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="nama">Nama Dokumen <span class="req">*</span></label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $archive->nama ?? '') }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   required maxlength="200"
                                   placeholder="Contoh: Notulen Rapat Paripurna Pengesahan APBD">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tanggal">Tanggal Arsip <span class="req">*</span></label>
                            <input type="date" id="tanggal" name="tanggal"
                                   value="{{ $selectedTanggal }}"
                                   class="form-control @error('tanggal') is-invalid @enderror" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="jenis">Jenis <span class="req">*</span></label>
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

                        <div class="col-md-3">
                            <label class="form-label" for="status">Status <span class="req">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (App\Models\Arsip::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="dprd_member_id">Anggota DPRD Terkait</label>
                            <select id="dprd_member_id" name="dprd_member_id"
                                    class="form-select @error('dprd_member_id') is-invalid @enderror"
                                    @if (auth()->user()->isStaff()) required @endif>
                                @if (auth()->user()->isAdministrator())
                                    <option value="">Tidak terkait anggota (Sekretariat DPRD)</option>
                                @endif
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected((string) $selectedMember === (string) $member->id)>
                                        {{ $member->nama_lengkap }}@if ($member->kode) — {{ $member->kode }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('dprd_member_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                @if (auth()->user()->isStaff())
                                    Staf hanya dapat menambahkan arsip untuk anggota DPRD yang ditugaskan kepadanya.
                                @else
                                    Pilih anggota jika dokumen ini memang terkait dengan anggota tertentu.
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="deskripsi">Keterangan</label>
                            <textarea id="deskripsi" name="deskripsi"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      maxlength="2000"
                                      placeholder="Catatan singkat mengenai isi atau asal dokumen.">{{ old('deskripsi', $archive->deskripsi ?? '') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="file">Berkas Dokumen</label>
                            <input type="file" id="file" name="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Maksimal 10 MB. Format: PDF, Word, Excel, PowerPoint, JPG, PNG, WEBP, atau TXT.
                                @if ($isEdit && $archive->file_path)
                                    Biarkan kosong untuk mempertahankan berkas saat ini.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($isEdit && $archive->file_path)
                <div class="app-card">
                    <div class="app-card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-attachment-2 text-gold" aria-hidden="true"></i>
                                <div>
                                    <strong class="fs-11 text-ink">{{ $archive->file_name ?: 'Berkas arsip' }}</strong>
                                    <div class="text-muted-soft fs-10">Berkas saat ini tersimpan pada penyimpanan privat.</div>
                                </div>
                            </div>
                            <a href="{{ route('arsip.download', $archive) }}" class="btn btn-outline-soft btn-sm">
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
                        <p class="app-card-subtitle">Pratinjau metadata arsip.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Nomor</dt>
                            <dd>{{ old('nomor', $archive->nomor ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Dokumen</dt>
                            <dd>{{ old('nama', $archive->nama ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kategori</dt>
                            <dd>
                                {{ $categories->firstWhere('id', (int) $selectedKategori)?->nama ?? 'Belum dipilih' }}
                            </dd>
                        </div>
                        <div class="detail-row">
                            <dt>Anggota</dt>
                            <dd>
                                {{ $members->firstWhere('id', (int) $selectedMember)?->nama_lengkap ?? 'Sekretariat DPRD' }}
                            </dd>
                        </div>
                        <div class="detail-row">
                            <dt>Tanggal</dt>
                            <dd>{{ $selectedTanggal ?: 'Belum diisi' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ App\Models\Arsip::STATUS_LABELS[$selectedStatus] ?? 'Draf' }}</dd>
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
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Arsip' }}
                        </button>
                        <a href="{{ $selectedMember ? route('anggota.arsip', $selectedMember) : route('arsip.index') }}"
                           class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
