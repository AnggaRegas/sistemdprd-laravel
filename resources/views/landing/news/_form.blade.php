@php
    $isEdit = isset($news) && $news->exists;
    $selectedStatus = (string) old('status', $news->status ?? \App\Models\LandingNews::STATUS_DRAFT);
    $selectedCategory = (string) old('category', $news->category ?? '');
    $publishedAtValue = old(
        'published_at',
        $isEdit && $news->published_at ? $news->published_at->format('Y-m-d\TH:i') : ''
    );
    $hasThumbnail = $isEdit && $news->thumbnail;
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('landing.news.update', $news) : route('landing.news.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Berita</h2>
                        <p class="app-card-subtitle">Judul, kategori, ringkasan, dan isi berita yang tampil di halaman publik.</p>
                    </div>
                    <span class="status-badge {{ $selectedStatus === \App\Models\LandingNews::STATUS_PUBLISHED ? 'status-success' : ($selectedStatus === \App\Models\LandingNews::STATUS_DRAFT ? 'status-warning' : 'status-neutral') }}">
                        {{ $statusOptions[$selectedStatus] ?? $selectedStatus }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="title">Judul Berita <span class="req">*</span></label>
                            <input type="text" id="title" name="title"
                                   value="{{ old('title', $news->title ?? '') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   required maxlength="180" autofocus
                                   placeholder="Contoh: Rapat Paripurna DPRD Kota Parepare">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="slugPreview">Slug</label>
                            <input type="text" id="slugPreview"
                                   value="{{ $news->slug ?? '' }}"
                                   class="form-control" readonly disabled
                                   placeholder="slug-dibuat-otomatis">
                            <div class="form-text">Slug dibuat otomatis dan unik.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="category">Kategori</label>
                            <select id="category" name="category"
                                    class="form-select @error('category') is-invalid @enderror">
                                <option value="">Tanpa kategori</option>
                                @foreach ($categoryOptions as $slug => $label)
                                    <option value="{{ $slug }}" @selected($selectedCategory === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="excerpt">Ringkasan</label>
                            <textarea id="excerpt" name="excerpt"
                                      class="form-control @error('excerpt') is-invalid @enderror"
                                      rows="3" maxlength="500"
                                      placeholder="Ringkasan singkat yang tampil pada daftar berita.">{{ old('excerpt', $news->excerpt ?? '') }}</textarea>
                            @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Opsional, maksimal 500 karakter.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="content">Isi Berita <span class="req">*</span></label>
                            <textarea id="content" name="content"
                                      class="form-control @error('content') is-invalid @enderror"
                                      rows="12" maxlength="50000" required
                                      placeholder="Tulis isi berita di sini.">{{ old('content', $news->content ?? '') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Isi berupa teks biasa; baris baru ditampilkan otomatis. Jangan menempel HTML.
                            </div>
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
                        <p class="app-card-subtitle">Status dan jadwal tayang berita.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="status">Status Publikasi <span class="req">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statusOptions as $slug => $label)
                                    <option value="{{ $slug }}" @selected($selectedStatus === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="published_at">Tanggal Publikasi</label>
                            <input type="datetime-local" id="published_at" name="published_at"
                                   value="{{ $publishedAtValue }}"
                                   class="form-control @error('published_at') is-invalid @enderror">
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kosongkan untuk menggunakan waktu saat dipublikasikan.</div>
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Berita berstatus Published wajib memiliki gambar utama sebelum dapat disimpan.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Gambar Utama</h2>
                        <p class="app-card-subtitle">Thumbnail yang tampil pada daftar berita.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="mb-3">
                        <img id="thumbnailPreview"
                             src="{{ $hasThumbnail ? \Illuminate\Support\Facades\Storage::url($news->thumbnail) : '' }}"
                             alt="Pratinjau gambar utama"
                             class="rounded w-100 {{ $hasThumbnail ? '' : 'd-none' }}"
                             style="max-height: 200px; object-fit: cover;">

                        <div id="thumbnailPlaceholder"
                             class="d-flex align-items-center justify-content-center rounded text-muted-soft {{ $hasThumbnail ? 'd-none' : '' }}"
                             style="height: 140px; border: 1px dashed #cfd8d3; background: #f7f9f8;">
                            <span class="text-center">
                                <i class="ri-image-add-line fs-3 d-block mb-1" aria-hidden="true"></i>
                                Belum ada gambar
                            </span>
                        </div>
                    </div>

                    <label class="form-label" for="thumbnail">Pilih Gambar</label>
                    <input type="file" id="thumbnail" name="thumbnail"
                           class="form-control @error('thumbnail') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp">
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">JPG/PNG/WEBP, maksimal 2 MB. Wajib diunggah saat status Published bila belum ada gambar.</div>

                    @if ($hasThumbnail)
                        <div class="form-text mt-2">
                            Gambar saat ini: {{ basename($news->thumbnail) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mt-3">
        <div class="app-card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ route('landing.news.index') }}" class="btn btn-outline-soft">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    Batal
                </a>
                <button type="submit" class="btn btn-emerald">
                    <i class="ri-save-3-line" aria-hidden="true"></i>
                    Simpan Berita
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var titleInput = document.getElementById('title');
            var slugPreview = document.getElementById('slugPreview');

            if (titleInput && slugPreview) {
                var buildSlug = function () {
                    slugPreview.value = titleInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                };

                titleInput.addEventListener('input', buildSlug);
                buildSlug();
            }

            var fileInput = document.getElementById('thumbnail');
            var preview = document.getElementById('thumbnailPreview');
            var placeholder = document.getElementById('thumbnailPlaceholder');

            if (fileInput && preview) {
                fileInput.addEventListener('change', function () {
                    var file = fileInput.files && fileInput.files[0];

                    if (!file) {
                        return;
                    }

                    var reader = new FileReader();

                    reader.addEventListener('load', function () {
                        preview.src = reader.result;
                        preview.classList.remove('d-none');

                        if (placeholder) {
                            placeholder.classList.add('d-none');
                        }
                    });

                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endpush
