<?php

use App\Models\LandingAgenda;
use App\Models\LandingGallery;
use App\Models\LandingNews;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$disk = Storage::disk('public');
$source = file_get_contents(public_path('images/gedung-dprd-parepare.jpeg'));
$manifest = ['gallery' => null, 'news' => [], 'agendas' => [], 'files' => []];

$galleryPaths = [];
for ($i = 1; $i <= 6; $i++) {
    $path = "landing/galleries/demo-carousel-{$i}.jpeg";
    $disk->put($path, $source);
    $galleryPaths[] = $path;
    $manifest['files'][] = $path;
}

$gallery = LandingGallery::query()->create([
    'title' => 'DEMO Uji Carousel Dokumentasi',
    'slug' => 'demo-uji-carousel-dokumentasi',
    'description' => 'Konten sementara untuk verifikasi tampilan carousel dokumentasi.',
    'activity_date' => now()->toDateString(),
    'location' => 'Kota Parepare',
    'cover_image' => $galleryPaths[0],
    'status' => LandingGallery::STATUS_PUBLISHED,
    'created_by' => 2,
]);

foreach ($galleryPaths as $index => $path) {
    $gallery->photos()->create([
        'image_path' => $path,
        'caption' => 'Demo Foto '.($index + 1),
        'sort_order' => $index + 1,
    ]);
}

$manifest['gallery'] = $gallery->id;

foreach (['Demo Berita Pertama', 'Demo Berita Kedua'] as $index => $title) {
    $path = 'landing/news/demo-berita-'.$index.'.jpeg';
    $disk->put($path, $source);
    $manifest['files'][] = $path;

    $news = LandingNews::query()->create([
        'title' => $title,
        'slug' => 'demo-berita-'.$index,
        'category' => 'kegiatan',
        'excerpt' => 'Ringkasan sementara untuk verifikasi tampilan berita pada landing page.',
        'content' => 'Konten sementara untuk verifikasi tampilan halaman berita publik.',
        'thumbnail' => $path,
        'published_at' => now()->subHours($index + 1),
        'status' => LandingNews::STATUS_PUBLISHED,
        'created_by' => 2,
    ]);

    $manifest['news'][] = $news->id;
}

$agendaData = [
    ['Demo Agenda Mendatang Satu', 2, '09:00', '11:00', LandingAgenda::EVENT_UPCOMING],
    ['Demo Agenda Mendatang Dua', 5, '13:00', '15:00', LandingAgenda::EVENT_UPCOMING],
    ['Demo Agenda Lampau', -10, '08:00', '10:00', LandingAgenda::EVENT_COMPLETED],
];

foreach ($agendaData as $index => [$title, $offset, $start, $end, $eventStatus]) {
    $agenda = LandingAgenda::query()->create([
        'title' => $title,
        'slug' => 'demo-agenda-'.$index,
        'description' => 'Deskripsi sementara untuk verifikasi tampilan agenda publik.',
        'event_date' => now()->addDays($offset)->toDateString(),
        'start_time' => $start,
        'end_time' => $end,
        'location' => 'Aula DPRD Kota Parepare',
        'event_status' => $eventStatus,
        'status' => LandingAgenda::STATUS_PUBLISHED,
        'created_by' => 2,
    ]);

    $manifest['agendas'][] = $agenda->id;
}

file_put_contents(storage_path('demo-landing-manifest.json'), json_encode($manifest));

echo 'SEEDED gallery='.$gallery->id.' news='.implode(',', $manifest['news']).' agendas='.implode(',', $manifest['agendas']).PHP_EOL;
