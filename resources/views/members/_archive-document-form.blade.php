@php
    $isEdit = $document->exists;
    $selectedJenis = old('jenis', $document->jenis ?? App\Models\Arsip::JENIS_MASUK);
    $selectedTanggal = old('tanggal', $document->tanggal?->toDateString() ?? now()->toDateString());
    $maxUploadMb = App\Support\ArchiveFileStorage::maxUploadMegabytes();
    $maxUploadBytes = App\Support\ArchiveFileStorage::maxUploadBytes();
    $allowedExtensions = App\Support\ArchiveFileStorage::allowedExtensions();
    $clientAccept = App\Support\ArchiveFileStorage::clientAcceptAttribute();
    $supportedFormatsLabel = App\Support\ArchiveFileStorage::supportedFormatsLabel();
@endphp

<form method="POST"
      id="archiveDocumentForm"
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
                            <label class="form-label d-flex justify-content-between align-items-center" for="file">
                                <span>
                                    Berkas Dokumen
                                    @if (! $isEdit)
                                        <span class="req">*</span>
                                    @endif
                                </span>
                                <span class="badge bg-light text-muted border fw-semibold">
                                    Maksimal {{ $maxUploadMb }} MB
                                </span>
                            </label>

                            <div class="document-upload-card @error('file') is-invalid-drop @enderror"
                                 id="archiveDocumentDropzone"
                                 data-max-bytes="{{ $maxUploadBytes }}"
                                 data-max-mb="{{ $maxUploadMb }}"
                                 data-allowed-exts="{{ implode(',', $allowedExtensions) }}">
                                <input type="file" id="file" name="file"
                                       class="d-none @error('file') is-invalid @enderror"
                                       accept="{{ $clientAccept }}"
                                       @if (! $isEdit) required @endif>

                                <div class="upload-dropzone-default" id="uploadDefaultState">
                                    <div class="document-upload-icon">
                                        <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>
                                    </div>
                                    <div class="document-upload-title">Tarik &amp; letakkan berkas di sini</div>
                                    <div class="document-upload-hint">
                                        atau klik tombol di bawah untuk memilih berkas dari komputer Anda
                                    </div>
                                    <button type="button" class="btn btn-outline-soft btn-sm px-3" id="btnBrowseFile">
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

                                <div class="upload-selected-box d-none" id="uploadSelectedState">
                                    <div class="upload-selected-info">
                                        <div class="upload-selected-icon" id="selectedFileIcon">
                                            <i class="ri-file-text-line" aria-hidden="true"></i>
                                        </div>
                                        <div class="upload-selected-details">
                                            <div class="upload-selected-name" id="selectedFileName">—</div>
                                            <div class="upload-selected-size" id="selectedFileSize">—</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="ri-checkbox-circle-line me-1" aria-hidden="true"></i>Siap Diunggah
                                        </span>
                                        <button type="button" class="btn btn-outline-soft btn-sm" id="btnChangeFile" title="Ganti berkas">
                                            <i class="ri-refresh-line" aria-hidden="true"></i> Ganti
                                        </button>
                                    </div>
                                </div>

                                <div class="upload-client-error d-none" id="uploadClientError" role="alert">
                                    <i class="ri-error-warning-fill flex-shrink-0" aria-hidden="true"></i>
                                    <span id="uploadClientErrorText"></span>
                                </div>

                                <div class="upload-progress-container d-none" id="uploadProgressContainer">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fs-11 fw-700 text-ink">
                                            <i class="ri-loader-4-line ri-spin me-1 text-gold" aria-hidden="true"></i>
                                            Sedang Mengunggah Dokumen...
                                        </span>
                                        <span class="text-muted fs-10" id="uploadProgressDetail">Mohon tunggu</span>
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

                            @if ($isEdit && $document->file_path)
                                <div class="form-text mt-2">
                                    <i class="ri-information-line" aria-hidden="true"></i>
                                    Biarkan kosong untuk mempertahankan berkas saat ini ({{ $document->file_name }} &middot; {{ $document->file_size_label }}).
                                </div>
                            @endif
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
                            <dd id="summaryDocTitle">{{ old('nama', $document->nama ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jenis</dt>
                            <dd id="summaryDocJenis">{{ App\Models\Arsip::JENIS_LABELS[$selectedJenis] ?? '—' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Berkas</dt>
                            <dd id="summaryDocFile">
                                @if ($isEdit && $document->file_path)
                                    {{ $document->file_name }} ({{ $document->file_size_label }})
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
                        <button type="submit" class="btn btn-gold" id="btnSubmitArchiveDocument">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            <span id="btnSubmitText">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Dokumen' }}</span>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropzone = document.getElementById('archiveDocumentDropzone');
        if (!dropzone) return;

        const input = document.getElementById('file');
        const defaultState = document.getElementById('uploadDefaultState');
        const selectedState = document.getElementById('uploadSelectedState');
        const progressState = document.getElementById('uploadProgressContainer');
        const clientError = document.getElementById('uploadClientError');
        const clientErrorText = document.getElementById('uploadClientErrorText');
        const btnBrowse = document.getElementById('btnBrowseFile');
        const btnChange = document.getElementById('btnChangeFile');
        const selectedFileName = document.getElementById('selectedFileName');
        const selectedFileSize = document.getElementById('selectedFileSize');
        const selectedFileIcon = document.getElementById('selectedFileIcon');
        const summaryFile = document.getElementById('summaryDocFile');
        const summaryTitle = document.getElementById('summaryDocTitle');
        const summaryJenis = document.getElementById('summaryDocJenis');
        const inputTitle = document.getElementById('nama');
        const selectJenis = document.getElementById('jenis');
        const form = document.getElementById('archiveDocumentForm');
        const submitBtn = document.getElementById('btnSubmitArchiveDocument');
        const submitText = document.getElementById('btnSubmitText');

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
            if (e.target.closest('#btnChangeFile') || e.target.closest('#btnBrowseFile')) return;
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

            if (inputTitle && !inputTitle.value.trim()) {
                const rawName = fileName.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
                inputTitle.value = rawName.charAt(0).toUpperCase() + rawName.slice(1);
                if (summaryTitle) summaryTitle.textContent = inputTitle.value;
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

        if (inputTitle && summaryTitle) {
            inputTitle.addEventListener('input', function () {
                summaryTitle.textContent = inputTitle.value.trim() || 'Belum diisi';
            });
        }
        if (selectJenis && summaryJenis) {
            selectJenis.addEventListener('change', function () {
                summaryJenis.textContent = selectJenis.options[selectJenis.selectedIndex]?.text || '—';
            });
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
                        if (submitText) submitText.textContent = 'Mengunggah dokumen...';
                        submitBtn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
                    }
                }
            });
        }
    });
</script>
