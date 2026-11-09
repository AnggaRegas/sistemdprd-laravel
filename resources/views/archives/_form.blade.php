@php
    $isEdit = $archive->exists;
    $selectedMember = old('dprd_member_id', $archive->dprd_member_id ?? $selectedMemberId ?? '');
    $selectedKategori = old('kategori_arsip_id', $archive->kategori_arsip_id ?? '');
    $selectedJenis = old('jenis', $archive->jenis ?? App\Models\Arsip::JENIS_MASUK);
    $selectedStatus = old('status', $archive->status ?? App\Models\Arsip::STATUS_DRAF);
    $selectedTanggal = old('tanggal', $archive->tanggal?->toDateString() ?? now()->toDateString());
    $maxUploadMb = App\Support\ArchiveFileStorage::maxUploadMegabytes();
    $maxUploadBytes = App\Support\ArchiveFileStorage::maxUploadBytes();
    $allowedExtensions = App\Support\ArchiveFileStorage::allowedExtensions();
    $clientAccept = App\Support\ArchiveFileStorage::clientAcceptAttribute();
    $supportedFormatsLabel = App\Support\ArchiveFileStorage::supportedFormatsLabel();
@endphp

<form method="POST"
      id="generalArchiveForm"
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
                            <label class="form-label d-flex justify-content-between align-items-center" for="file">
                                <span>Berkas Dokumen</span>
                                <span class="badge bg-light text-muted border fw-semibold">
                                    Maksimal {{ $maxUploadMb }} MB
                                </span>
                            </label>

                            <div class="document-upload-card @error('file') is-invalid-drop @enderror"
                                 id="generalArchiveDropzone"
                                 data-max-bytes="{{ $maxUploadBytes }}"
                                 data-max-mb="{{ $maxUploadMb }}"
                                 data-allowed-exts="{{ implode(',', $allowedExtensions) }}">
                                <input type="file" id="file" name="file"
                                       class="d-none @error('file') is-invalid @enderror"
                                       accept="{{ $clientAccept }}">

                                <div class="upload-dropzone-default" id="archiveUploadDefaultState">
                                    <div class="document-upload-icon">
                                        <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>
                                    </div>
                                    <div class="document-upload-title">Tarik &amp; letakkan berkas di sini</div>
                                    <div class="document-upload-hint">
                                        atau klik tombol di bawah untuk memilih berkas dari komputer Anda
                                    </div>
                                    <button type="button" class="btn btn-outline-soft btn-sm px-3" id="btnBrowseArchiveFile">
                                        <i class="ri-folder-open-line me-1" aria-hidden="true"></i>
                                        Pilih Berkas
                                    </button>

                                    <div class="document-upload-meta-pills">
                                        <span class="upload-pill upload-pill-gold">Maks. {{ $maxUploadMb }} MB</span>
                                        <span class="upload-pill">PDF</span>
                                        <span class="upload-pill">DOC / DOCX</span>
                                        <span class="upload-pill">XLS / XLSX</span>
                                        <span class="upload-pill">PPT / PPTX</span>
                                        <span class="upload-pill">JPG / PNG / WEBP</span>
                                        <span class="upload-pill">TXT</span>
                                    </div>
                                </div>

                                <div class="upload-selected-box d-none" id="archiveUploadSelectedState">
                                    <div class="upload-selected-info">
                                        <div class="upload-selected-icon" id="archiveSelectedFileIcon">
                                            <i class="ri-file-text-line" aria-hidden="true"></i>
                                        </div>
                                        <div class="upload-selected-details">
                                            <div class="upload-selected-name" id="archiveSelectedFileName">—</div>
                                            <div class="upload-selected-size" id="archiveSelectedFileSize">—</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="ri-checkbox-circle-line me-1" aria-hidden="true"></i>Siap Diunggah
                                        </span>
                                        <button type="button" class="btn btn-outline-soft btn-sm" id="btnChangeArchiveFile" title="Ganti berkas">
                                            <i class="ri-refresh-line" aria-hidden="true"></i> Ganti
                                        </button>
                                    </div>
                                </div>

                                <div class="upload-client-error d-none" id="archiveUploadClientError" role="alert">
                                    <i class="ri-error-warning-fill flex-shrink-0" aria-hidden="true"></i>
                                    <span id="archiveUploadClientErrorText"></span>
                                </div>

                                <div class="upload-progress-container d-none" id="archiveUploadProgressContainer">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fs-11 fw-700 text-ink">
                                            <i class="ri-loader-4-line ri-spin me-1 text-gold" aria-hidden="true"></i>
                                            Sedang Mengunggah Dokumen...
                                        </span>
                                        <span class="text-muted fs-10">Mohon tunggu</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-gold"
                                             role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-muted fs-10 mt-2">
                                        Berkas sedang dikirim ke penyimpanan server. Mohon jangan menutup halaman ini.
                                    </div>
                                </div>
                            </div>

                            @error('file')
                                <div class="text-danger fs-10 mt-1 d-block">{{ $message }}</div>
                            @enderror

                            @if ($isEdit && $archive->file_path)
                                <div class="form-text mt-2">
                                    <i class="ri-information-line" aria-hidden="true"></i>
                                    Biarkan kosong untuk mempertahankan berkas saat ini ({{ $archive->file_name }} &middot; {{ $archive->file_size_label }}).
                                </div>
                            @endif
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
                            <dd id="summaryTanggal">{{ $selectedTanggal ?: 'Belum diisi' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd id="summaryStatus">{{ App\Models\Arsip::STATUS_LABELS[$selectedStatus] ?? 'Draf' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Berkas</dt>
                            <dd id="summaryArchiveFile">
                                @if ($isEdit && $archive->file_path)
                                    {{ $archive->file_name }} ({{ $archive->file_size_label }})
                                @else
                                    <span class="text-muted">Belum dipilih</span>
                                @endif
                            </dd>
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
                        <button type="submit" class="btn btn-gold" id="btnSubmitGeneralArchive">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            <span id="btnSubmitArchiveText">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Arsip' }}</span>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropzone = document.getElementById('generalArchiveDropzone');
        if (!dropzone) return;

        const input = document.getElementById('file');
        const defaultState = document.getElementById('archiveUploadDefaultState');
        const selectedState = document.getElementById('archiveUploadSelectedState');
        const progressState = document.getElementById('archiveUploadProgressContainer');
        const clientError = document.getElementById('archiveUploadClientError');
        const clientErrorText = document.getElementById('archiveUploadClientErrorText');
        const btnBrowse = document.getElementById('btnBrowseArchiveFile');
        const btnChange = document.getElementById('btnChangeArchiveFile');
        const selectedFileName = document.getElementById('archiveSelectedFileName');
        const selectedFileSize = document.getElementById('archiveSelectedFileSize');
        const selectedFileIcon = document.getElementById('archiveSelectedFileIcon');
        const summaryFile = document.getElementById('summaryArchiveFile');
        const form = document.getElementById('generalArchiveForm');
        const submitBtn = document.getElementById('btnSubmitGeneralArchive');
        const submitText = document.getElementById('btnSubmitArchiveText');

        const maxBytes = parseInt(dropzone.getAttribute('data-max-bytes') || '104857600', 10);
        const maxMb = dropzone.getAttribute('data-max-mb') || '100';
        const allowedExts = (dropzone.getAttribute('data-allowed-exts') || 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt')
            .toLowerCase().split(',').map(s => s.trim());

        function formatBytes(bytes) {
            if (!bytes || bytes <= 0) return '0 B';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
            if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
            return bytes + ' B';
        }

        function getFileIcon(ext) {
            if (ext === 'pdf') return 'ri-file-pdf-2-line text-danger';
            if (['doc', 'docx'].includes(ext)) return 'ri-file-word-2-line text-primary';
            if (['xls', 'xlsx'].includes(ext)) return 'ri-file-excel-2-line text-success';
            if (['ppt', 'pptx'].includes(ext)) return 'ri-file-ppt-2-line text-warning';
            if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) return 'ri-image-2-line text-info';
            return 'ri-file-text-line text-secondary';
        }

        function triggerBrowse() {
            if (input) input.click();
        }

        if (btnBrowse) btnBrowse.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); triggerBrowse(); });
        if (btnChange) btnChange.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); triggerBrowse(); });
        dropzone.addEventListener('click', function (e) {
            if (e.target.closest('#btnChangeArchiveFile') || e.target.closest('#btnBrowseArchiveFile')) return;
            if (!input.files || input.files.length === 0) triggerBrowse();
        });

        ['dragenter', 'dragover'].forEach(function (name) {
            dropzone.addEventListener(name, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach(function (name) {
            dropzone.addEventListener(name, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                handleFileSelected(input.files[0]);
            }
        });

        if (input) {
            input.addEventListener('change', function () {
                if (input.files && input.files.length > 0) {
                    handleFileSelected(input.files[0]);
                } else {
                    resetDropzone();
                }
            });
        }

        function handleFileSelected(file) {
            if (!file) return;

            if (clientError) clientError.classList.add('d-none');
            dropzone.classList.remove('is-invalid-drop');

            const fileName = file.name || '';
            const ext = fileName.split('.').pop().toLowerCase();
            const fileSize = file.size || 0;

            if (fileSize > maxBytes) {
                showError('Ukuran berkas (' + formatBytes(fileSize) + ') melebihi batas maksimal ' + maxMb + ' MB. Silakan pilih berkas yang lebih kecil.');
                input.value = '';
                return;
            }

            if (!allowedExts.includes(ext)) {
                showError('Format berkas .' + ext + ' tidak didukung. Format yang diizinkan: PDF, Word, Excel, PowerPoint, Gambar, atau Teks.');
                input.value = '';
                return;
            }

            if (defaultState) defaultState.classList.add('d-none');
            if (selectedState) selectedState.classList.remove('d-none');
            if (selectedFileName) {
                selectedFileName.textContent = fileName;
                selectedFileName.title = fileName;
            }
            if (selectedFileSize) selectedFileSize.textContent = formatBytes(fileSize);
            if (selectedFileIcon) {
                selectedFileIcon.innerHTML = '<i class="' + getFileIcon(ext) + '" aria-hidden="true"></i>';
            }
            if (summaryFile) {
                summaryFile.innerHTML = '<strong>' + fileName + '</strong> <span class="text-muted-soft">(' + formatBytes(fileSize) + ')</span>';
            }
        }

        function showError(msg) {
            if (clientError && clientErrorText) {
                clientErrorText.textContent = msg;
                clientError.classList.remove('d-none');
            }
            dropzone.classList.add('is-invalid-drop');
            resetDropzone();
        }

        function resetDropzone() {
            if (defaultState) defaultState.classList.remove('d-none');
            if (selectedState) selectedState.classList.add('d-none');
            if (selectedFileName) selectedFileName.textContent = '—';
            if (selectedFileSize) selectedFileSize.textContent = '—';
        }

        if (form) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) return;

                if (input && input.files && input.files.length > 0) {
                    if (progressState) {
                        if (selectedState) selectedState.classList.add('d-none');
                        if (defaultState) defaultState.classList.add('d-none');
                        progressState.classList.remove('d-none');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        if (submitText) submitText.textContent = 'Mengunggah arsip...';
                        submitBtn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
                    }
                }
            });
        }
    });
</script>
