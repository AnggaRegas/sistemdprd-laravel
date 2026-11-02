<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\LandingAgenda;
use App\Models\LandingGallery;
use App\Models\LandingGalleryPhoto;
use App\Models\LandingNews;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Batas foto yang dimuat untuk carousel dokumentasi. Angka ini hanya
     * membatasi pemakaian memori/ukuran respons, bukan batas jumlah foto
     * yang dapat diunggah pada sebuah album.
     */
    private const DOCS_PREVIEW_LIMIT = 12;

    /**
     * Landing page publik. Seluruh konten diambil dari database dengan
     * query terbatas (tidak ada N+1) dan hanya berstatus Published.
     */
    public function index(): View
    {
        $latestNews = LandingNews::query()
            ->visible()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $upcomingAgendas = LandingAgenda::query()
            ->published()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(4)
            ->get();

        return view('welcome', [
            'latestNews' => $latestNews,
            'upcomingAgendas' => $upcomingAgendas,
            'galleryPhotos' => $this->galleryPreview(),
        ]);
    }

    /**
     * Kumpulan foto untuk carousel dokumentasi beranda.
     *
     * Sumber utama adalah tabel foto album (LandingGalleryPhoto). Agar
     * dokumentasi selalu terlihat meski foto belum diunggah (album baru
     * hanya memiliki sampul), tiap album Published dengan cover_image
     * menjadi fallback. Tidak ada gambar dummy: URL tetap dari Storage.
     *
     * Struktur item: url, caption, date (Carbon|null), location,
     * gallery_title, gallery_slug.
     */
    private function galleryPreview(): Collection
    {
        $photos = LandingGalleryPhoto::query()
            ->select('landing_gallery_photos.*')
            ->join('landing_galleries', 'landing_galleries.id', '=', 'landing_gallery_photos.landing_gallery_id')
            ->where('landing_galleries.status', LandingGallery::STATUS_PUBLISHED)
            ->with('gallery:id,title,slug,activity_date,location')
            ->orderByDesc('landing_galleries.activity_date')
            ->orderBy('landing_gallery_photos.sort_order')
            ->orderBy('landing_gallery_photos.id')
            ->limit(self::DOCS_PREVIEW_LIMIT)
            ->get()
            ->map(fn (LandingGalleryPhoto $photo) => $this->snapshotItem($photo));

        $remaining = self::DOCS_PREVIEW_LIMIT - $photos->count();

        if ($remaining <= 0) {
            return $photos;
        }

        // Album yang sudah terwakili oleh foto-nya tidak diulang lewat sampul.
        $coveredGalleryIds = LandingGalleryPhoto::query()
            ->select('landing_gallery_photos.landing_gallery_id')
            ->join('landing_galleries', 'landing_galleries.id', '=', 'landing_gallery_photos.landing_gallery_id')
            ->where('landing_galleries.status', LandingGallery::STATUS_PUBLISHED)
            ->pluck('landing_gallery_id')
            ->unique();

        $covers = LandingGallery::query()
            ->published()
            ->whereNotNull('cover_image')
            ->when($coveredGalleryIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $coveredGalleryIds))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->limit($remaining)
            ->get()
            ->map(function (LandingGallery $gallery) {
                return [
                    'url' => Storage::url($gallery->cover_image),
                    'caption' => null,
                    'date' => $gallery->activity_date,
                    'location' => (string) $gallery->location,
                    'gallery_title' => $gallery->title,
                    'gallery_slug' => $gallery->slug,
                ];
            });

        return $photos->concat($covers)->values();
    }

    private function snapshotItem(LandingGalleryPhoto $photo): array
    {
        $gallery = $photo->gallery;

        return [
            'url' => Storage::url($photo->image_path),
            'caption' => $photo->caption,
            'date' => $gallery?->activity_date,
            'location' => (string) ($gallery?->location ?? ''),
            'gallery_title' => $gallery?->title ?? 'Dokumentasi',
            'gallery_slug' => $gallery?->slug,
        ];
    }
}
