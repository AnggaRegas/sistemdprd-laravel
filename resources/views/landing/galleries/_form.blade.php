@php
    $isEdit = isset($isEdit) ? (bool) $isEdit : (isset($gallery) && $gallery->exists);
    $selectedStatus = (string) old('status', $gallery->status ?: \App\Models\LandingGallery::STATUS_DRAFT);
    $currentCover = $isEdit && $gallery->cover_image ? $gallery->cover_image : null;
    $selectedDate = old('activity_date', $gallery->activity_date?->format('Y-m-d') ?? '');
@endphp

<form method="POST"
      action="{{ $isEdit ? route('landing.galleries.update', $gallery) : route('landing.galleries.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Album</h2>
                        <p class="app-card-subtitle">Judul, deskripsi, tanggal kegiatan, dan lokasi dokumentasi.</p>
                    </div>
                    <span class="status-badge {{ $selectedStatus === \App\Models\LandingGallery::STATUS_PUBLISHED ? 'status-success' : 'status-neutral' }}">
                        {{ \App\Models\LandingGallery::STATUSES[$selectedStatus] ?? 'Draft' }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="title">Judul Album <span class="req">*</span></label>
                            <input type="text" id="title" name="title"
                                   value="{{ old('title', $gallery->title ?? '') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   required maxlength="180" autofocus
                                   placeholder="Contoh: Rapat Paripurna Penetapan APBD 2026">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Slug album dibuat otomatis dari judul. Perubahan judul akan memperbarui slug agar tautan publik tetap sesuai.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="6" maxlength="5000"
                                      placeholder="Ceritakan jalannya kegiatan, peserta, dan hal penting lain yang perlu diketahui publik.">{{ old('description', $gallery->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maksimal 5.000 karakter. Deskripsi singkat juga tampil pada daftar album di situs publik.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="activity_date">Tanggal Kegiatan <span class="req">*</span></label>
                            <input type="date" id="activity_date" name="activity_date"
                                   value="{{ $selectedDate }}"
                                   class="form-control @error('activity_date') is-invalid @enderror"
                                   required max="{{ now()->toDateString() }}">
                            @error('activity_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tanggal kegiatan tidak boleh melewati hari ini.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="location">Lokasi</label>
                            <input type="text" id="location" name="location"
                                   value="{{ old('location', $gallery->location ?? '') }}"
                                   class="form-control @error('location') is-invalid @enderror"
                                   maxlength="180"
                                   placeholder="Contoh: Ruang Rapat Paripurna DPRD Kota Parepare">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Hanya album berstatus <strong>Published</strong> yang tampil pada halaman dokumentasi publik.
                            Simpan sebagai <strong>Draft</strong> untuk menyiapkan album terlebih dahulu.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Publikasi</h2>
                        <p class="app-card-subtitle">Status tampil pada situs publik.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <label class="form-label" for="status">Status Publikasi <span class="req">*</span></label>
                    <select id="status" name="status"
                            class="form-select @error('status') is-invalid @enderror" required>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Draft tidak tampil ke publik. Published langsung dapat diakses melalui halaman dokumentasi.
                    </div>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Foto Sampul</h2>
                        <p class="app-card-subtitle">Gambar utama album pada daftar dokumentasi.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <label class="form-label" for="cover_image">Unggah Sampul</label>
                    <input type="file" id="cover_image" name="cover_image"
                           class="form-control @error('cover_image') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp">
                    @error('cover_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.</div>

                    <div id="gallery-cover-preview" class="mt-3">
                        @if ($currentCover)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($currentCover) }}"
                                 alt="Sampul album saat ini"
                                 class="w-100 rounded border"
                                 style="aspect-ratio: 16/10; object-fit: cover;">
                            <p class="form-text mb-0 mt-2">Sampul saat ini</p>
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded border bg-light text-muted"
                                 style="aspect-ratio: 16/10;">
                                <i class="ri-image-line" style="font-size: 28px;" aria-hidden="true"></i>
                            </div>
                            <p class="form-text mb-0 mt-2">Belum ada sampul dipilih.</p>
                        @endif
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-image-2-line" aria-hidden="true"></i>
                        <div>
                            Jika tidak ada sampul yang diunggah, foto pertama yang diunggah menjadi sampul album.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            Simpan Album
                        </button>
                        <a href="{{ route('landing.galleries.index') }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! $isEdit)
        <div class="app-card mt-3">
            <div class="app-card-header">
                <div>
                    <h2 class="app-card-title">Unggah Foto Kegiatan</h2>
                    <p class="app-card-subtitle">Tambahkan foto-foto kegiatan ke album baru ini.</p>
                </div>
                <span class="status-badge status-neutral" id="gallery-photos-count">0 foto dipilih</span>
            </div>

            <div class="app-card-body">
                <label class="form-label" for="gallery-photos-input">Pilih Foto</label>
                <input type="file" id="gallery-photos-input" name="photos[]"
                       class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror"
                       accept="image/jpeg,image/png,image/webp"
                       multiple>
                @error('photos')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('photos.*')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-text">
                    Banyak foto sekaligus, maksimal 2 MB per foto. Caption dapat diatur setelah album disimpan.
                </div>

                <div id="gallery-photo-preview" class="row g-2 mt-1"></div>
            </div>
        </div>
    @endif
</form>

@push('scripts')
    <script>
        (function () {
            'use strict';

            var coverInput = document.getElementById('cover_image');
            var coverPreview = document.getElementById('gallery-cover-preview');

            if (coverInput && coverPreview) {
                var coverInitial = coverPreview.innerHTML;

                coverInput.addEventListener('change', function () {
                    var file = coverInput.files && coverInput.files[0];

                    coverPreview.innerHTML = '';

                    if (!file) {
                        coverPreview.innerHTML = coverInitial;

                        return;
                    }

                    var reader = new FileReader();

                    reader.addEventListener('load', function (event) {
                        var image = document.createElement('img');
                        image.src = event.target.result;
                        image.alt = 'Pratinjau sampul album';
                        image.className = 'w-100 rounded border';
                        image.style.aspectRatio = '16/10';
                        image.style.objectFit = 'cover';

                        var caption = document.createElement('p');
                        caption.className = 'form-text mb-0 mt-2';
                        caption.textContent = file.name;

                        coverPreview.appendChild(image);
                        coverPreview.appendChild(caption);
                    });

                    reader.readAsDataURL(file);
                });
            }

            var photosInput = document.getElementById('gallery-photos-input');
            var photosPreview = document.getElementById('gallery-photo-preview');
            var photosCount = document.getElementById('gallery-photos-count');

            if (photosInput && photosPreview) {
                photosInput.addEventListener('change', function () {
                    var files = Array.prototype.slice.call(photosInput.files || []);

                    photosPreview.innerHTML = '';

                    if (photosCount) {
                        photosCount.textContent = files.length + ' foto dipilih';
                    }

                    files.forEach(function (file) {
                        var column = document.createElement('div');
                        column.className = 'col-6 col-md-4 col-xl-3';

                        var figure = document.createElement('figure');
                        figure.className = 'mb-0';

                        var image = document.createElement('img');
                        image.alt = 'Pratinjau ' + file.name;
                        image.className = 'w-100 rounded border';
                        image.style.aspectRatio = '4/3';
                        image.style.objectFit = 'cover';

                        var caption = document.createElement('figcaption');
                        caption.className = 'form-text text-truncate mb-0 mt-1';
                        caption.textContent = file.name;

                        figure.appendChild(image);
                        figure.appendChild(caption);
                        column.appendChild(figure);
                        photosPreview.appendChild(column);

                        var reader = new FileReader();

                        reader.addEventListener('load', function (event) {
                            image.src = event.target.result;
                        });

                        reader.readAsDataURL(file);
                    });
                });
            }
        })();
    </script>
@endpush
