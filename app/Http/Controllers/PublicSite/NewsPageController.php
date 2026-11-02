<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\LandingNews;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsPageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $news = LandingNews::query()
            ->visible()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        return view('public.berita.index', [
            'news' => $news,
            'search' => mb_substr($search, 0, 100),
        ]);
    }

    public function show(string $slug): View
    {
        $article = LandingNews::query()
            ->visible()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = LandingNews::query()
            ->visible()
            ->whereKeyNot($article->id)
            ->when($article->category, fn ($query) => $query->where('category', $article->category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($related->count() < 3) {
            $fallback = LandingNews::query()
                ->visible()
                ->whereKeyNot($article->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3 - $related->count())
                ->get();

            $related = $related->concat($fallback);
        }

        return view('public.berita.show', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
