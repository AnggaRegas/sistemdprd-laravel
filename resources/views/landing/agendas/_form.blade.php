@php
    $isEdit = isset($agenda) && $agenda->exists;
    $selectedStatus = (string) old('status', $agenda->status ?? \App\Models\LandingAgenda::STATUS_DRAFT);
    $selectedEventStatus = (string) old('event_status', $agenda->event_status ?? \App\Models\LandingAgenda::EVENT_UPCOMING);
    $eventDateValue = old('event_date', $isEdit && $agenda->event_date ? $agenda->event_date->format('Y-m-d') : '');
    $startTimeValue = old('start_time', $isEdit && $agenda->start_time ? substr($agenda->start_time, 0, 5) : '');
    $endTimeValue = old('end_time', $isEdit && $agenda->end_time ? substr($agenda->end_time, 0, 5) : '');
    $hasImage = $isEdit && $agenda->image;
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('landing.agendas.update', $agenda) : route('landing.agendas.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Kegiatan</h2>
                        <p class="app-card-subtitle">Judul, jadwal, waktu, dan lokasi kegiatan.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="title">Judul Kegiatan <span class="req">*</span></label>
                            <input type="text" id="title" name="title"
                                   value="{{ old('title', $agenda->title ?? '') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   required maxlength="180" autofocus
                                   placeholder="Contoh: Rapat Paripurna DPRD Kota Parepare">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Slug dibuat otomatis dan unik.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi <span class="req">*</span></label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="8" maxlength="10000" required
                                      placeholder="Jelaskan ruang lingkup kegiatan ini.">{{ old('description', $agenda->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 col-lg-4">
                            <label class="form-label" for="event_date">Tanggal Kegiatan <span class="req">*</span></label>
                            <input type="date" id="event_date" name="event_date"
                                   value="{{ $eventDateValue }}"
                                   class="form-control @error('event_date') is-invalid @enderror" required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="start_time">Waktu Mulai <span class="req">*</span></label>
                            <input type="time" id="start_time" name="start_time"
                                   value="{{ $startTimeValue }}"
                                   class="form-control @error('start_time') is-invalid @enderror" required>
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="end_time">Waktu Selesai <span class="req">*</span></label>
                            <input type="time" id="end_time" name="end_time"
                                   value="{{ $endTimeValue }}"
                                   class="form-control @error('end_time') is-invalid @enderror" required>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Waktu selesai tidak boleh lebih awal daripada waktu mulai.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="location">Lokasi <span class="req">*</span></label>
                            <input type="text" id="location" name="location"
                                   value="{{ old('location', $agenda->location ?? '') }}"
                                   class="form-control @error('location') is-invalid @enderror"
                                   required maxlength="180"
                                   placeholder="Contoh: Ruang Paripurna Kantor DPRD Kota Parepare">
                            @error('location')
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
                        <h2 class="app-card-title">Publikasi</h2>
                        <p class="app-card-subtitle">Status kegiatan dan status tayang agenda.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="event_status">Status Kegiatan <span class="req">*</span></label>
                            <select id="event_status" name="event_status"
                                    class="form-select @error('event_status') is-invalid @enderror" required>
                                @foreach ($eventStatusOptions as $slug => $label)
                                    <option value="{{ $slug }}" @selected($selectedEventStatus === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('event_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Gambar Kegiatan</h2>
                        <p class="app-card-subtitle">Opsional, tampil pada kartu agenda.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="mb-3">
                        <img id="imagePreview"
                             src="{{ $hasImage ? \Illuminate\Support\Facades\Storage::url($agenda->image) : '' }}"
                             alt="Pratinjau gambar kegiatan"
                             class="rounded w-100 {{ $hasImage ? '' : 'd-none' }}"
                             style="max-height: 200px; object-fit: cover;">

                        <div id="imagePlaceholder"
                             class="d-flex align-items-center justify-content-center rounded text-muted-soft {{ $hasImage ? 'd-none' : '' }}"
                             style="height: 140px; border: 1px dashed #cfd8d3; background: #f7f9f8;">
                            <span class="text-center">
                                <i class="ri-image-add-line fs-3 d-block mb-1" aria-hidden="true"></i>
                                Belum ada gambar
                            </span>
                        </div>
                    </div>

                    <label class="form-label" for="image">Pilih Gambar</label>
                    <input type="file" id="image" name="image"
                           class="form-control @error('image') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">JPG/PNG/WEBP, maksimal 2 MB.</div>

                    @if ($hasImage)
                        <div class="form-text mt-2">
                            Gambar saat ini: {{ basename($agenda->image) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mt-3">
        <div class="app-card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ route('landing.agendas.index') }}" class="btn btn-outline-soft">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    Batal
                </a>
                <button type="submit" class="btn btn-emerald">
                    <i class="ri-save-3-line" aria-hidden="true"></i>
                    Simpan Agenda
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var startTime = document.getElementById('start_time');
            var endTime = document.getElementById('end_time');

            if (startTime && endTime) {
                var syncMin = function () {
                    if (startTime.value) {
                        endTime.min = startTime.value;
                    }
                };

                startTime.addEventListener('input', syncMin);
                syncMin();
            }

            var fileInput = document.getElementById('image');
            var preview = document.getElementById('imagePreview');
            var placeholder = document.getElementById('imagePlaceholder');

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
