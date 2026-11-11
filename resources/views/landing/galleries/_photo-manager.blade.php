@php
    $photos = $gallery->photos;
@endphp

<div class="app-card mt-3">
    <div class="app-card-header">
        <div>
            <h2 class="app-card-title">Tambah Foto ke Album</h2>
            <p class="app-card-subtitle">Unggah banyak foto sekaligus. Total foto album tidak dibatasi, maksimal 2 MB per foto.</p>
        </div>
        <span class="status-badge status-neutral" id="photo-add-count">0 foto dipilih</span>
    </div>

    <div class="app-card-body">
        <form method="POST" enctype="multipart/form-data"
              action="{{ route('landing.galleries.photos.store', $gallery) }}">
            @csrf

            <label class="form-label" for="photo-add-input">Pilih Foto</label>
            <input type="file" id="photo-add-input" name="photos[]"
                   class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror"
                   accept="image/jpeg,image/png,image/webp"
                   multiple required>
            @error('photos')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @error('photos.*')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <div class="form-text">
                Maksimal {{ number_format(50, 0, ',', '.') }} foto per unggahan dan 2 MB per foto. Caption dapat diatur setelah foto tersimpan.
            </div>

            <div id="photo-add-preview" class="row g-2 mt-1"></div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-emerald">
                    <i class="ri-upload-2-line" aria-hidden="true"></i>
                    Unggah Foto
                </button>
            </div>
        </form>
    </div>
</div>

<div class="app-card mt-3">
    <div class="app-card-header">
        <div>
            <h2 class="app-card-title">Foto Album ({{ $photos->count() }})</h2>
            <p class="app-card-subtitle">
                Atur caption, sampul, dan urutan tampil foto. Urutan akan disimpan di server saat tombol disimpan ditekan.
            </p>
        </div>
        @if ($photos->isNotEmpty() && $photos->count() > 1)
            <button type="button" class="btn btn-emerald btn-sm" id="save-photo-order">
                <i class="ri-save-3-line" aria-hidden="true"></i>
                Simpan Urutan Foto
            </button>
        @endif
    </div>

    <div class="app-card-body">
        @error('order')
            <div class="alert alert-danger app-alert mb-3" role="alert">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                <div>{{ $message }}</div>
            </div>
        @enderror

        @error('caption')
            <div class="alert alert-danger app-alert mb-3" role="alert">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                <div>{{ $message }}</div>
            </div>
        @enderror

        @if ($photos->isEmpty())
            <x-admin.empty-state
                icon="ri-image-add-line"
                title="Belum ada foto"
                description="Album ini belum memiliki foto. Unggah foto melalui bagian Tambah Foto ke Album di atas untuk mulai mendokumentasikan kegiatan.">
            </x-admin.empty-state>
        @else
            <div class="row g-3" id="photo-grid">
                @foreach ($photos as $photo)
                    <div class="col-6 col-md-4 col-xl-3"
                         data-photo-id="{{ $photo->id }}"
                         draggable="true">
                        <article class="app-card h-100 mb-0">
                            <div class="position-relative">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}"
                                     alt="{{ $photo->caption ?: 'Foto kegiatan album '.$gallery->title }}"
                                     class="w-100 rounded-top"
                                     style="aspect-ratio: 4/3; object-fit: cover; object-position: center;"
                                     loading="lazy">

                                @if ($gallery->cover_image === $photo->image_path)
                                    <span class="status-badge status-gold position-absolute" style="top: 8px; left: 8px;">
                                        <i class="ri-star-fill" aria-hidden="true"></i>
                                        Sampul
                                    </span>
                                @endif

                                <span class="position-absolute d-inline-flex align-items-center justify-content-center rounded bg-white shadow-sm text-muted"
                                      style="top: 8px; right: 8px; width: 26px; height: 26px; cursor: grab;"
                                      data-drag-handle
                                      title="Tarik untuk mengubah urutan foto"
                                      aria-label="Tarik untuk mengubah urutan foto">
                                    <i class="ri-drag-move-2-line fs-10" aria-hidden="true"></i>
                                </span>
                            </div>

                            <div class="app-card-body p-2">
                                <form method="POST"
                                      action="{{ route('landing.galleries.photos.update', [$gallery, $photo]) }}"
                                      class="d-flex align-items-center gap-1 mb-2">
                                    @csrf
                                    @method('PATCH')

                                    <label class="visually-hidden" for="caption-{{ $photo->id }}">Caption foto</label>
                                    <input type="text" id="caption-{{ $photo->id }}" name="caption"
                                           value="{{ old('caption', $photo->caption) }}"
                                           class="form-control form-control-sm"
                                           maxlength="255"
                                           placeholder="Caption foto..."
                                           style="min-width: 0;">
                                    <button type="submit"
                                            class="btn btn-outline-soft btn-sm text-nowrap"
                                            title="Simpan caption"
                                            aria-label="Simpan caption foto">
                                        <i class="ri-check-line" aria-hidden="true"></i>
                                    </button>
                                </form>

                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    @if ($gallery->cover_image !== $photo->image_path)
                                        <form method="POST"
                                              action="{{ route('landing.galleries.photos.cover', [$gallery, $photo]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-outline-soft btn-sm"
                                                    title="Jadikan sampul album"
                                                    aria-label="Jadikan foto ini sampul album">
                                                <i class="ri-image-2-line" aria-hidden="true"></i>
                                                <span class="fs-10">Sampul</span>
                                            </button>
                                        </form>
                                    @endif

                                    <div class="ms-auto d-flex align-items-center gap-1">
                                        <button type="button"
                                                class="btn btn-outline-soft btn-sm px-2"
                                                data-move="up"
                                                title="Pindah ke atas"
                                                aria-label="Pindahkan foto ini ke atas"
                                                @disabled($loop->first)>
                                            <i class="ri-arrow-up-s-line" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-soft btn-sm px-2"
                                                data-move="down"
                                                title="Pindah ke bawah"
                                                aria-label="Pindahkan foto ini ke bawah"
                                                @disabled($loop->last)>
                                            <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-soft-danger btn-sm px-2"
                                                data-confirm-action
                                                data-confirm-title="Hapus foto ini?"
                                                data-confirm-message="Foto ini akan dihapus dari album. Foto lain tidak terpengaruh."
                                                data-confirm-label="Hapus foto"
                                                data-confirm-form="#photo-delete-{{ $photo->id }}"
                                                title="Hapus foto"
                                                aria-label="Hapus foto ini dari album">
                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="demo-note mt-3 mb-0">
                <i class="ri-drag-move-2-line" aria-hidden="true"></i>
                <div>
                    Geser kartu foto dengan tangan atau gunakan tombol panah untuk mengubah urutan.
                    Tekan <strong>Simpan Urutan Foto</strong> agar perubahan urutan tersimpan.
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Form pengurutan foto --}}
<form id="photo-order-form" method="POST"
      action="{{ route('landing.galleries.photos.reorder', $gallery) }}" class="d-none">
    @csrf
    @method('PATCH')
</form>

{{-- Form hapus per foto --}}
@foreach ($photos as $photo)
    <form id="photo-delete-{{ $photo->id }}" method="POST"
          action="{{ route('landing.galleries.photos.destroy', [$gallery, $photo]) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endforeach

@push('scripts')
    <script>
        (function () {
            'use strict';

            var grid = document.getElementById('photo-grid');
            var addInput = document.getElementById('photo-add-input');
            var addPreview = document.getElementById('photo-add-preview');
            var addCount = document.getElementById('photo-add-count');
            var saveOrderButton = document.getElementById('save-photo-order');
            var orderForm = document.getElementById('photo-order-form');

            /* Pratinjau foto tambahan ------------------------------------------------ */

            if (addInput && addPreview) {
                addInput.addEventListener('change', function () {
                    var files = Array.prototype.slice.call(addInput.files || []);

                    addPreview.innerHTML = '';

                    if (addCount) {
                        addCount.textContent = files.length + ' foto dipilih';
                    }

                    files.forEach(function (file) {
                        var column = document.createElement('div');
                        column.className = 'col-4 col-md-3 col-xl-2';

                        var figure = document.createElement('figure');
                        figure.className = 'mb-0';

                        var image = document.createElement('img');
                        image.alt = 'Pratinjau ' + file.name;
                        image.className = 'w-100 rounded border';
                        image.style.aspectRatio = '1/1';
                        image.style.objectFit = 'cover';

                        var caption = document.createElement('figcaption');
                        caption.className = 'form-text text-truncate text-center mb-0 mt-1';
                        caption.textContent = file.name;

                        figure.appendChild(image);
                        figure.appendChild(caption);
                        column.appendChild(figure);
                        addPreview.appendChild(column);

                        var reader = new FileReader();

                        reader.addEventListener('load', function (event) {
                            image.src = event.target.result;
                        });

                        reader.readAsDataURL(file);
                    });
                });
            }

            /* Urutan foto ------------------------------------------------------------- */

            var draggedCard = null;

            function cards() {
                return grid ? Array.prototype.slice.call(grid.querySelectorAll('[data-photo-id]')) : [];
            }

            function refreshMoveButtons() {
                var list = cards();

                list.forEach(function (card, index) {
                    var upButton = card.querySelector('[data-move="up"]');
                    var downButton = card.querySelector('[data-move="down"]');

                    if (upButton) {
                        upButton.disabled = index === 0;
                    }

                    if (downButton) {
                        downButton.disabled = index === list.length - 1;
                    }
                });
            }

            if (grid) {
                cards().forEach(function (card) {
                    card.addEventListener('dragstart', function (event) {
                        draggedCard = card;
                        card.style.opacity = '0.55';
                        card.style.cursor = 'grabbing';

                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';

                            try {
                                event.dataTransfer.setData('text/plain', card.getAttribute('data-photo-id'));
                            } catch (error) {
                                /* Beberapa peramban tidak mengizinkan setData. */
                            }
                        }
                    });

                    card.addEventListener('dragend', function () {
                        card.style.opacity = '';
                        card.style.cursor = '';
                        draggedCard = null;
                        refreshMoveButtons();
                    });

                    card.addEventListener('dragover', function (event) {
                        if (!draggedCard || draggedCard === card) {
                            return;
                        }

                        event.preventDefault();

                        if (event.dataTransfer) {
                            event.dataTransfer.dropEffect = 'move';
                        }

                        var rect = card.getBoundingClientRect();
                        var after = event.clientX - rect.left > rect.width / 2;

                        if (after) {
                            grid.insertBefore(draggedCard, card.nextSibling);
                        } else {
                            grid.insertBefore(draggedCard, card);
                        }
                    });

                    card.addEventListener('drop', function (event) {
                        event.preventDefault();
                    });
                });

                grid.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-move]');

                    if (!button || button.disabled) {
                        return;
                    }

                    var card = button.closest('[data-photo-id]');

                    if (!card) {
                        return;
                    }

                    if (button.getAttribute('data-move') === 'up') {
                        var previous = card.previousElementSibling;

                        if (previous) {
                            grid.insertBefore(card, previous);
                        }
                    } else {
                        var next = card.nextElementSibling;

                        if (next) {
                            grid.insertBefore(next, card);
                        }
                    }

                    refreshMoveButtons();
                });

                refreshMoveButtons();
            }

            /* Simpan urutan ----------------------------------------------------------- */

            if (saveOrderButton && orderForm && grid) {
                saveOrderButton.addEventListener('click', function () {
                    var list = cards();

                    if (!list.length) {
                        return;
                    }

                    Array.prototype.slice
                        .call(orderForm.querySelectorAll('input[data-order-input]'))
                        .forEach(function (input) {
                            input.remove();
                        });

                    list.forEach(function (card) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'order[]';
                        input.value = card.getAttribute('data-photo-id');
                        input.setAttribute('data-order-input', '1');
                        orderForm.appendChild(input);
                    });

                    orderForm.submit();
                });
            }
        })();
    </script>
@endpush
