@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => $commission->name, 'url' => route('komisi.rdp.index', $commission)],
        ['label' => 'Detail RDP'],
    ],
])

@section('title', 'Detail RDP')

@section('content')
    @php
        $documentCount = $meeting->documents->count();
    @endphp

    <x-admin.page-header
        eyebrow="Detail RDP"
        :title="$meeting->title"
        :description="$meeting->code
            ? 'Kode '.$meeting->code.' · '.$commission->name
            : 'Tanpa kode RDP · '.$commission->name"
    >
        <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Folder RDP
        </a>
        <a href="{{ route('komisi.rdp.edit', [$commission, $meeting]) }}" class="btn btn-gold">
            <i class="ri-edit-line" aria-hidden="true"></i>
            Edit RDP
        </a>
        <button type="button" class="btn btn-soft-danger"
                data-confirm-action
                data-confirm-title="Hapus RDP ini?"
                data-confirm-message="RDP {{ $meeting->title }} akan dihapus (soft delete). Dokumen terkait tetap tersimpan untuk keperluan audit."
                data-confirm-label="Hapus RDP"
                data-confirm-form="#deleteMeetingShow">
            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
            Hapus
        </button>
    </x-admin.page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Rapat</h2>
                        <p class="app-card-subtitle">Agenda, jadwal, tempat, dan catatan RDP.</p>
                    </div>
                    <span class="status-badge status-{{ $meeting->status_tone }}">{{ $meeting->status_label }}</span>
                </div>

                <div class="app-card-body">
                    <div class="rdp-detail-grid">
                        <div class="rdp-detail-item">
                            <span class="rdp-detail-label"><i class="ri-calendar-line" aria-hidden="true"></i> Jadwal</span>
                            <strong>{{ $meeting->schedule_label }}</strong>
                        </div>
                        <div class="rdp-detail-item">
                            <span class="rdp-detail-label"><i class="ri-map-pin-line" aria-hidden="true"></i> Tempat</span>
                            <strong>{{ $meeting->location ?: 'Belum ditentukan' }}</strong>
                        </div>
                        <div class="rdp-detail-item">
                            <span class="rdp-detail-label"><i class="ri-government-line" aria-hidden="true"></i> Komisi</span>
                            <strong>{{ $commission->name }}</strong>
                        </div>
                        <div class="rdp-detail-item">
                            <span class="rdp-detail-label"><i class="ri-user-star-line" aria-hidden="true"></i> Dibuat oleh</span>
                            <strong>{{ $meeting->creator?->name ?? 'Sistem' }}</strong>
                        </div>
                    </div>

                    <div class="rdp-section">
                        <h3 class="rdp-section-title">Deskripsi / Ringkasan</h3>
                        <p class="rdp-section-text">
                            {{ $meeting->description ?: 'Belum ada deskripsi atau ringkasan untuk RDP ini.' }}
                        </p>
                    </div>

                    <div class="rdp-section">
                        <h3 class="rdp-section-title">Catatan</h3>
                        <p class="rdp-section-text">
                            {{ $meeting->notes ?: 'Belum ada catatan internal untuk RDP ini.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Dokumen RDP</h2>
                        <p class="app-card-subtitle">
                            {{ $documentCount }} dokumen tersimpan pada penyimpanan privat.
                        </p>
                    </div>
                    <button type="button" class="btn btn-emerald btn-sm" data-bs-toggle="modal" data-bs-target="#documentUploadModal">
                        <i class="ri-upload-2-line" aria-hidden="true"></i>
                        Unggah Dokumen
                    </button>
                </div>

                @if ($documentCount === 0)
                    <x-admin.empty-state
                        icon="ri-file-3-line"
                        title="Belum ada dokumen"
                        description="Unggah risalah, notula, undangan, daftar hadir, materi, atau dokumen pendukung untuk RDP ini."
                    >
                        <button type="button" class="btn btn-emerald btn-sm" data-bs-toggle="modal" data-bs-target="#documentUploadModal">
                            <i class="ri-upload-2-line" aria-hidden="true"></i>
                            Unggah Dokumen Pertama
                        </button>
                    </x-admin.empty-state>
                @else
                    <div class="document-list">
                        @foreach ($meeting->documents as $document)
                            @php
                                $documentIcon = match ($document->category) {
                                    'risalah' => 'ri-file-text-line',
                                    'notula' => 'ri-draft-line',
                                    'undangan' => 'ri-mail-line',
                                    'daftar_hadir' => 'ri-user-follow-line',
                                    'materi' => 'ri-presentation-line',
                                    default => 'ri-attachment-2',
                                };
                            @endphp

                            <div class="document-item">
                                <span class="document-icon" aria-hidden="true">
                                    <i class="{{ $documentIcon }}"></i>
                                </span>

                                <div class="document-main">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <strong class="document-title">{{ $document->title }}</strong>
                                        <span class="status-badge status-info">{{ $document->category_label }}</span>
                                    </div>
                                    <div class="document-meta">
                                        <span><i class="ri-calendar-line" aria-hidden="true"></i> {{ $document->document_date?->locale('id')->translatedFormat('d M Y') ?? 'Tanpa tanggal' }}</span>
                                        <span><i class="ri-hard-drive-2-line" aria-hidden="true"></i> {{ $document->file_size_label }}</span>
                                        <span><i class="ri-user-line" aria-hidden="true"></i> {{ $document->uploader?->name ?? 'Sistem' }}</span>
                                        <span><i class="ri-time-line" aria-hidden="true"></i> {{ $document->created_at?->locale('id')->translatedFormat('d M Y H:i') }}</span>
                                    </div>
                                    @if ($document->description)
                                        <p class="document-description">{{ $document->description }}</p>
                                    @endif
                                </div>

                                <div class="document-actions">
                                    <a href="{{ route('komisi.rdp.documents.download', [$commission, $meeting, $document]) }}"
                                       class="btn btn-outline-soft btn-sm" title="Unduh {{ $document->original_name }}">
                                        <i class="ri-download-2-line" aria-hidden="true"></i>
                                        Unduh
                                    </a>
                                    <button type="button" class="btn btn-soft-danger btn-sm"
                                            data-confirm-action
                                            data-confirm-title="Hapus dokumen ini?"
                                            data-confirm-message="Dokumen {{ $document->title }} akan dihapus dari daftar. Berkas fisik tetap disimpan untuk keperluan audit."
                                            data-confirm-label="Hapus dokumen"
                                            data-confirm-form="#deleteDocument{{ $document->id }}">
                                        <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <form id="deleteDocument{{ $document->id }}" method="POST"
                                  action="{{ route('komisi.rdp.documents.destroy', [$commission, $meeting, $document]) }}"
                                  class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Ringkasan RDP</h2>
                        <p class="app-card-subtitle">Status dan kelengkapan arsip.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd><span class="status-badge status-{{ $meeting->status_tone }}">{{ $meeting->status_label }}</span></dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kode RDP</dt>
                            <dd>{{ $meeting->code ?: '—' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jumlah dokumen</dt>
                            <dd>{{ $documentCount }} dokumen</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Terakhir diperbarui</dt>
                            <dd>{{ $meeting->updated_at?->locale('id')->translatedFormat('d M Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-lock-line" aria-hidden="true"></i>
                        <div>
                            Dokumen disimpan pada disk privat dan hanya dapat diunduh melalui endpoint yang memeriksa
                            otorisasi administrator.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('komisi.rdp.edit', [$commission, $meeting]) }}" class="btn btn-outline-soft">
                            <i class="ri-edit-line" aria-hidden="true"></i>
                            Edit Informasi RDP
                        </a>
                        <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-outline-soft">
                            <i class="ri-folder-open-line" aria-hidden="true"></i>
                            Kembali ke Folder {{ $commission->name }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteMeetingShow" method="POST"
          action="{{ route('komisi.rdp.destroy', [$commission, $meeting]) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Modal unggah dokumen --}}
    <div class="modal fade" id="documentUploadModal" tabindex="-1" aria-labelledby="documentUploadTitle" aria-hidden="true"
         data-upload-open="{{ $errors->documentUpload->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('komisi.rdp.documents.store', [$commission, $meeting]) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="documentUploadTitle">Unggah Dokumen RDP</h5>
                            <p class="app-card-subtitle mb-0">{{ $meeting->title }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        @if ($errors->documentUpload->any())
                            <div class="alert alert-danger app-alert" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Dokumen belum dapat diunggah.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->documentUpload->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="documentTitle">Judul Dokumen <span class="req">*</span></label>
                                <input type="text" id="documentTitle" name="title"
                                       value="{{ old('title') }}"
                                       class="form-control @if ($errors->documentUpload->has('title')) is-invalid @endif"
                                       required maxlength="180" placeholder="Contoh: Risalah RDP Pembahasan APBD">
                                @if ($errors->documentUpload->has('title'))
                                    <div class="invalid-feedback">{{ $errors->documentUpload->first('title') }}</div>
                                @endif
                            </div>

                            <div class="col-md-5">
                                <label class="form-label" for="documentCategory">Kategori <span class="req">*</span></label>
                                <select id="documentCategory" name="category"
                                        class="form-select @if ($errors->documentUpload->has('category')) is-invalid @endif" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->documentUpload->has('category'))
                                    <div class="invalid-feedback">{{ $errors->documentUpload->first('category') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="documentDate">Tanggal Dokumen</label>
                                <input type="date" id="documentDate" name="document_date"
                                       value="{{ old('document_date') }}"
                                       class="form-control @if ($errors->documentUpload->has('document_date')) is-invalid @endif">
                                @if ($errors->documentUpload->has('document_date'))
                                    <div class="invalid-feedback">{{ $errors->documentUpload->first('document_date') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="documentFile">Berkas <span class="req">*</span></label>
                                <input type="file" id="documentFile" name="file"
                                       class="form-control @if ($errors->documentUpload->has('file')) is-invalid @endif"
                                       required
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt">
                                @if ($errors->documentUpload->has('file'))
                                    <div class="invalid-feedback">{{ $errors->documentUpload->first('file') }}</div>
                                @endif
                                <div class="form-text">Maksimal 10 MB. Format: PDF, Word, Excel, PowerPoint, gambar, atau teks.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="documentDescription">Keterangan</label>
                                <textarea id="documentDescription" name="description"
                                          class="form-control @if ($errors->documentUpload->has('description')) is-invalid @endif"
                                          maxlength="2000" rows="3"
                                          placeholder="Catatan singkat mengenai isi dokumen.">{{ old('description') }}</textarea>
                                @if ($errors->documentUpload->has('description'))
                                    <div class="invalid-feedback">{{ $errors->documentUpload->first('description') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="demo-note mt-3 mb-0">
                            <i class="ri-lock-line" aria-hidden="true"></i>
                            <div>
                                Berkas disimpan di penyimpanan privat. Nama berkas asli tidak digunakan langsung sebagai
                                path dan tidak dapat diakses tanpa otorisasi.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-upload-2-line" aria-hidden="true"></i>
                            Unggah Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
