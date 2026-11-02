<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\LandingGallery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryPageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $galleries = LandingGallery::query()
            ->published()
            ->withCount('photos')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        return view('public.dokumentasi.index', [
            'galleries' => $galleries,
            'search' => mb_substr($search, 0, 100),
        ]);
    }

    public function show(string $slug): View
    {
        $gallery = LandingGallery::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $gallery->load(['photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);

        $otherGalleries = LandingGallery::query()
            ->published()
            ->whereKeyNot($gallery->id)
            ->withCount('photos')
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('public.dokumentasi.show', [
            'gallery' => $gallery,
            'otherGalleries' => $otherGalleries,
        ]);
    }
}
