<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingNewsRequest;
use App\Models\AktivitasSistem;
use App\Models\LandingNews;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingNewsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-landing-page');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'category' => $this->allowedValue($request->query('category'), array_keys(LandingNews::CATEGORIES)),
            'status' => $this->allowedValue($request->query('status'), array_keys(LandingNews::STATUSES)),
        ];

        $news = LandingNews::query()
            ->when($filters['q'] !== '', fn ($query) => $query->where('title', 'like', "%{$filters['q']}%"))
            ->when($filters['category'] !== null, fn ($query) => $query->where('category', $filters['category']))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => LandingNews::count(),
            'published' => LandingNews::where('status', LandingNews::STATUS_PUBLISHED)->count(),
            'draft' => LandingNews::where('status', LandingNews::STATUS_DRAFT)->count(),
            'archived' => LandingNews::where('status', LandingNews::STATUS_ARCHIVED)->count(),
        ];

        return view('landing.news.index', [
            'news' => $news,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-landing-page');

        return view('landing.news.create', [
            'news' => new LandingNews(),
            'statusOptions' => LandingNews::STATUSES,
            'categoryOptions' => LandingNews::CATEGORIES,
        ]);
    }

    public function store(LandingNewsRequest $request): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $this->validatedPayload($request);
        $validated['thumbnail'] = $this->storeThumbnail($request);

        $news = DB::transaction(function () use ($request, $validated) {
            $news = LandingNews::query()->create($validated + [
                'slug' => $this->uniqueSlug($validated['title']),
                'created_by' => $request->user()->id,
            ]);

            if ($news->status === LandingNews::STATUS_PUBLISHED && $news->published_at === null) {
                $news->update(['published_at' => now()]);
            }

            return $news;
        });

        AktivitasSistem::catat(
            'landing_news.created',
            $request->user(),
            $news,
            ['status' => $news->status],
            'Berita landing page "'.$news->title.'" ditambahkan.'
        );

        return redirect()
            ->route('landing.news.index')
            ->with('success', 'Berita berhasil ditambahkan.');
    }

    public function edit(LandingNews $news): View
    {
        $this->authorize('manage-landing-page');

        return view('landing.news.edit', [
            'news' => $news,
            'statusOptions' => LandingNews::STATUSES,
            'categoryOptions' => LandingNews::CATEGORIES,
        ]);
    }

    public function update(LandingNewsRequest $request, LandingNews $news): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $this->validatedPayload($request);

        $newThumbnail = $request->hasFile('thumbnail')
            ? $this->storeThumbnail($request)
            : null;

        $previousThumbnail = $news->thumbnail;
        $wasPublished = $news->status === LandingNews::STATUS_PUBLISHED;

        DB::transaction(function () use ($news, $validated, $newThumbnail, $wasPublished) {
            $attributes = [
                'title' => $validated['title'],
                'category' => $validated['category'],
                'excerpt' => $validated['excerpt'],
                'content' => $validated['content'],
                'published_at' => $validated['published_at'],
                'status' => $validated['status'],
            ];

            if ($newThumbnail !== null) {
                $attributes['thumbnail'] = $newThumbnail;
            }

            if ($news->title !== $validated['title']) {
                $attributes['slug'] = $this->uniqueSlug($validated['title'], $news->id);
            }

            if ($validated['status'] === LandingNews::STATUS_PUBLISHED
                && ! $wasPublished
                && $validated['published_at'] === null) {
                $attributes['published_at'] = now();
            }

            $news->update($attributes);
        });

        // File lama dihapus setelah transaksi sukses agar DB tidak menunjuk
        // ke file yang sudah hilang.
        if ($newThumbnail !== null && $previousThumbnail !== null && $previousThumbnail !== $newThumbnail) {
            Storage::disk('public')->delete($previousThumbnail);
        }

        AktivitasSistem::catat(
            'landing_news.updated',
            $request->user(),
            $news,
            ['status' => $validated['status']],
            'Berita landing page "'.$news->title.'" diperbarui.'
        );

        return redirect()
            ->route('landing.news.index')
            ->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Request $request, LandingNews $news): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $thumbnail = $news->thumbnail;

        DB::transaction(static fn () => $news->delete());

        if ($thumbnail !== null) {
            Storage::disk('public')->delete($thumbnail);
        }

        AktivitasSistem::catat(
            'landing_news.deleted',
            $request->user(),
            $news,
            ['status' => $news->status],
            'Berita landing page "'.$news->title.'" dihapus.'
        );

        return redirect()
            ->route('landing.news.index')
            ->with('success', 'Berita berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(LandingNewsRequest $request): array
    {
        return collect($request->validated())->except(['thumbnail'])->all();
    }

    private function storeThumbnail(LandingNewsRequest $request): ?string
    {
        if (! $request->hasFile('thumbnail')) {
            return null;
        }

        $path = $request->file('thumbnail')->store('landing/news', 'public');

        if ($path === false) {
            abort(500, 'Gagal menyimpan gambar utama berita.');
        }

        return $path;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $counter = 2;

        while (LandingNews::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  mixed  $value
     * @param  array<int, string>  $allowed
     */
    private function allowedValue(mixed $value, array $allowed): ?string
    {
        $value = (string) $value;

        return in_array($value, $allowed, true) ? $value : null;
    }
}
