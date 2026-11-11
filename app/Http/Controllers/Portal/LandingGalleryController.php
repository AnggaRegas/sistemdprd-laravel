<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingGalleryRequest;
use App\Models\AktivitasSistem;
use App\Models\LandingGallery;
use App\Models\LandingGalleryPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LandingGalleryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-landing-page');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'status' => $this->allowedValue($request->query('status'), array_keys(LandingGallery::STATUSES)),
            'date' => $this->validDate($request->query('date')),
        ];

        $galleries = LandingGallery::query()
            ->withCount('photos')
            ->when($filters['q'] !== '', fn ($query) => $query->where('title', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['date'] !== null, fn ($query) => $query->whereDate('activity_date', $filters['date']))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => LandingGallery::count(),
            'photos' => LandingGalleryPhoto::count(),
            'published' => LandingGallery::where('status', LandingGallery::STATUS_PUBLISHED)->count(),
            'draft' => LandingGallery::where('status', LandingGallery::STATUS_DRAFT)->count(),
        ];

        return view('landing.galleries.index', [
            'galleries' => $galleries,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-landing-page');

        return view('landing.galleries.create', [
            'gallery' => new LandingGallery(),
            'statusOptions' => LandingGallery::STATUSES,
        ]);
    }

    public function store(LandingGalleryRequest $request): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validated();
        $storedFiles = [];

        try {
            $coverPath = $this->storeUpload($request, 'cover_image', $storedFiles);
            $photoPaths = $this->storeUploadedPhotos($request, $storedFiles);

            $gallery = DB::transaction(function () use ($request, $validated, $coverPath, $photoPaths) {
                $gallery = LandingGallery::query()->create([
                    'title' => $validated['title'],
                    'slug' => $this->uniqueSlug($validated['title']),
                    'description' => $validated['description'] ?: null,
                    'activity_date' => $validated['activity_date'],
                    'location' => $validated['location'] ?: null,
                    'cover_image' => $coverPath ?? ($photoPaths[0]['path'] ?? null),
                    'status' => $validated['status'],
                    'created_by' => $request->user()->id,
                ]);

                foreach ($photoPaths as $index => $photo) {
                    $gallery->photos()->create([
                        'image_path' => $photo['path'],
                        'caption' => $photo['caption'],
                        'sort_order' => $index + 1,
                    ]);
                }

                return $gallery;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedFiles);

            throw $exception;
        }

        AktivitasSistem::catat(
            'landing_gallery.created',
            $request->user(),
            $gallery,
            ['status' => $gallery->status, 'photos' => count($photoPaths)],
            'Album dokumentasi "'.$gallery->title.'" ditambahkan.'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Album dokumentasi berhasil dibuat. Anda dapat menambah atau mengatur foto di halaman ini.');
    }

    public function edit(LandingGallery $gallery): View
    {
        $this->authorize('manage-landing-page');

        $gallery->load(['photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);

        return view('landing.galleries.edit', [
            'gallery' => $gallery,
            'statusOptions' => LandingGallery::STATUSES,
        ]);
    }

    public function update(LandingGalleryRequest $request, LandingGallery $gallery): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validated();
        $storedFiles = [];
        $previousCover = $gallery->cover_image;

        try {
            $coverPath = $this->storeUpload($request, 'cover_image', $storedFiles);
            $photoPaths = $this->storeUploadedPhotos($request, $storedFiles);

            DB::transaction(function () use ($gallery, $validated, $coverPath, $photoPaths) {
                $attributes = [
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?: null,
                    'activity_date' => $validated['activity_date'],
                    'location' => $validated['location'] ?: null,
                    'status' => $validated['status'],
                ];

                if ($coverPath !== null) {
                    $attributes['cover_image'] = $coverPath;
                }

                if ($gallery->title !== $validated['title']) {
                    $attributes['slug'] = $this->uniqueSlug($validated['title'], $gallery->id);
                }

                $gallery->update($attributes);

                $nextOrder = (int) $gallery->photos()->max('sort_order');
                foreach ($photoPaths as $photo) {
                    $nextOrder++;
                    $gallery->photos()->create([
                        'image_path' => $photo['path'],
                        'caption' => $photo['caption'],
                        'sort_order' => $nextOrder,
                    ]);
                }

                if (! $gallery->cover_image && $photoPaths !== []) {
                    $gallery->update(['cover_image' => $photoPaths[0]['path']]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedFiles);

            throw $exception;
        }

        // Sampul lama hanya dihapus jika bukan foto album yang masih dipakai.
        if ($coverPath !== null && $previousCover !== null && $previousCover !== $coverPath) {
            $this->deleteFileIfUnused($gallery, $previousCover);
        }

        AktivitasSistem::catat(
            'landing_gallery.updated',
            $request->user(),
            $gallery,
            ['status' => $gallery->status, 'photos_added' => count($photoPaths)],
            'Album dokumentasi "'.$gallery->title.'" diperbarui.'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Album dokumentasi berhasil diperbarui.');
    }

    public function destroy(Request $request, LandingGallery $gallery): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $paths = $gallery->photos()->pluck('image_path')->all();
        $cover = $gallery->cover_image;

        DB::transaction(static fn () => $gallery->delete());

        Storage::disk('public')->delete($paths);

        if ($cover !== null && ! in_array($cover, $paths, true)) {
            Storage::disk('public')->delete($cover);
        }

        AktivitasSistem::catat(
            'landing_gallery.deleted',
            $request->user(),
            $gallery,
            ['photos' => count($paths)],
            'Album dokumentasi "'.$gallery->title.'" dihapus.'
        );

        return redirect()
            ->route('landing.galleries.index')
            ->with('success', 'Album dokumentasi berhasil dihapus.');
    }

    public function storePhotos(Request $request, LandingGallery $gallery): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:50'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ], [], [
            'photos' => 'foto kegiatan',
            'photos.*' => 'foto kegiatan',
            'captions.*' => 'caption foto',
        ]);

        $storedFiles = [];

        try {
            $photos = $this->storeUploadedPhotos($request, $storedFiles, $validated['captions'] ?? []);

            DB::transaction(function () use ($gallery, $photos) {
                $nextOrder = (int) $gallery->photos()->max('sort_order');

                foreach ($photos as $photo) {
                    $nextOrder++;
                    $gallery->photos()->create([
                        'image_path' => $photo['path'],
                        'caption' => $photo['caption'],
                        'sort_order' => $nextOrder,
                    ]);
                }

                if (! $gallery->cover_image && $photos !== []) {
                    $gallery->update(['cover_image' => $photos[0]['path']]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedFiles);

            throw $exception;
        }

        AktivitasSistem::catat(
            'landing_gallery.photos_added',
            $request->user(),
            $gallery,
            ['count' => count($photos)],
            count($photos).' foto ditambahkan ke album "'.$gallery->title.'".'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', count($photos).' foto berhasil diunggah.');
    }

    public function updatePhoto(Request $request, LandingGallery $gallery, LandingGalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('manage-landing-page');
        $this->ensurePhotoBelongsToGallery($gallery, $photo);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
        ], [], ['caption' => 'caption foto']);

        $photo->update(['caption' => trim((string) ($validated['caption'] ?? '')) ?: null]);

        AktivitasSistem::catat(
            'landing_gallery.photo_updated',
            $request->user(),
            $photo,
            ['gallery_id' => $gallery->id],
            'Caption foto pada album "'.$gallery->title.'" diperbarui.'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Caption foto berhasil diperbarui.');
    }

    public function setCover(Request $request, LandingGallery $gallery, LandingGalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('manage-landing-page');
        $this->ensurePhotoBelongsToGallery($gallery, $photo);

        $gallery->update(['cover_image' => $photo->image_path]);

        AktivitasSistem::catat(
            'landing_gallery.cover_changed',
            $request->user(),
            $gallery,
            ['photo_id' => $photo->id],
            'Sampul album "'.$gallery->title.'" diperbarui.'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Foto sampul album berhasil diperbarui.');
    }

    public function destroyPhoto(Request $request, LandingGallery $gallery, LandingGalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('manage-landing-page');
        $this->ensurePhotoBelongsToGallery($gallery, $photo);

        $path = $photo->image_path;
        $isCover = $gallery->cover_image === $path;

        DB::transaction(function () use ($gallery, $photo, $isCover) {
            $photo->delete();

            if ($isCover) {
                $next = $gallery->photos()->orderBy('sort_order')->orderBy('id')->first();
                $gallery->update(['cover_image' => $next?->image_path]);
            }
        });

        Storage::disk('public')->delete($path);

        AktivitasSistem::catat(
            'landing_gallery.photo_deleted',
            $request->user(),
            $gallery,
            ['photo_id' => $photo->id, 'path' => $path],
            'Satu foto dihapus dari album "'.$gallery->title.'".'
        );

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Foto berhasil dihapus.');
    }

    public function reorderPhotos(Request $request, LandingGallery $gallery): RedirectResponse|JsonResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct'],
        ], [], ['order' => 'urutan foto']);

        $photos = $gallery->photos()->get()->keyBy('id');
        $orderedIds = array_map('intval', $validated['order']);

        // Seluruh ID harus milik album ini; mencegah pengurutan lintas album.
        if (count($orderedIds) !== $photos->count()) {
            throw ValidationException::withMessages([
                'order' => 'Urutan foto tidak sesuai dengan jumlah foto album.',
            ]);
        }

        foreach ($orderedIds as $id) {
            if (! $photos->has($id)) {
                throw ValidationException::withMessages([
                    'order' => 'Terdapat foto yang bukan bagian dari album ini.',
                ]);
            }
        }

        DB::transaction(function () use ($photos, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $photos->get($id)->update(['sort_order' => $index + 1]);
            }
        });

        AktivitasSistem::catat(
            'landing_gallery.photos_reordered',
            $request->user(),
            $gallery,
            ['order' => $orderedIds],
            'Urutan foto album "'.$gallery->title.'" diperbarui.'
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('landing.galleries.edit', $gallery)
            ->with('success', 'Urutan foto berhasil disimpan.');
    }

    private function ensurePhotoBelongsToGallery(LandingGallery $gallery, LandingGalleryPhoto $photo): void
    {
        abort_unless((int) $photo->landing_gallery_id === (int) $gallery->id, 404);
    }

    /**
     * @param  array<int, string>  $storedFiles
     */
    private function storeUpload(Request $request, string $field, array &$storedFiles): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $path = $request->file($field)->store('landing/galleries', 'public');

        if ($path === false) {
            abort(500, 'Gagal menyimpan foto album.');
        }

        $storedFiles[] = $path;

        return $path;
    }

    /**
     * @param  array<int, string>  $storedFiles
     * @param  array<int, string|null>  $captions
     * @return array<int, array{path: string, caption: string|null}>
     */
    private function storeUploadedPhotos(Request $request, array &$storedFiles, ?array $captions = null): array
    {
        if (! $request->hasFile('photos')) {
            return [];
        }

        $captions ??= (array) $request->input('captions', []);
        $photos = [];

        foreach ((array) $request->file('photos') as $index => $file) {
            $path = $file->store('landing/galleries', 'public');

            if ($path === false) {
                abort(500, 'Gagal menyimpan salah satu foto kegiatan.');
            }

            $storedFiles[] = $path;
            $caption = trim((string) ($captions[$index] ?? ''));

            $photos[] = [
                'path' => $path,
                'caption' => $caption !== '' ? $caption : null,
            ];
        }

        return $photos;
    }

    private function deleteFileIfUnused(LandingGallery $gallery, string $path): void
    {
        $usedByPhoto = $gallery->photos()->where('image_path', $path)->exists();

        if (! $usedByPhoto) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'dokumentasi';
        $slug = $base;
        $counter = 2;

        while (LandingGallery::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedValue(mixed $value, array $allowed): ?string
    {
        $value = (string) $value;

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }
}
