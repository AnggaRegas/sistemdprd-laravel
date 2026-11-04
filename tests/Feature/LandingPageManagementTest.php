<?php

namespace Tests\Feature;

use App\Models\LandingAgenda;
use App\Models\LandingGallery;
use App\Models\LandingGalleryPhoto;
use App\Models\LandingNews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_administrator_can_open_landing_page_module(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->get(route('landing.news.index'))->assertOk();
        $this->actingAs($admin)->get(route('landing.news.create'))->assertOk();
        $this->actingAs($admin)->get(route('landing.galleries.index'))->assertOk();
        $this->actingAs($admin)->get(route('landing.galleries.create'))->assertOk();
        $this->actingAs($admin)->get(route('landing.agendas.index'))->assertOk();
        $this->actingAs($admin)->get(route('landing.agendas.create'))->assertOk();
    }

    public function test_guest_and_staff_cannot_access_landing_page_module(): void
    {
        $this->get(route('landing.news.index'))->assertRedirect(route('login'));

        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('landing.news.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('landing.galleries.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('landing.agendas.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('landing.news.store'))->assertForbidden();
    }

    public function test_news_crud_and_public_visibility(): void
    {
        $admin = User::factory()->administrator()->create();

        $store = $this->actingAs($admin)->post(route('landing.news.store'), [
            'title' => 'Rapat Paripurna DPRD Kota Parepare',
            'category' => 'kegiatan',
            'excerpt' => 'Ringkasan rapat paripurna.',
            'content' => "Paragraf pertama.\nParagraf kedua.",
            'thumbnail' => UploadedFile::fake()->image('paripurna.jpg', 1200, 675),
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'status' => LandingNews::STATUS_PUBLISHED,
        ]);

        $news = LandingNews::query()->firstOrFail();
        $store->assertRedirect(route('landing.news.index'));

        $this->assertSame('rapat-paripurna-dprd-kota-parepare', $news->slug);
        Storage::disk('public')->assertExists($news->thumbnail);

        $this->get(route('berita.index'))->assertOk()->assertSee($news->title);
        $this->get(route('berita.show', $news->slug))->assertOk()->assertSee($news->title);
        $this->get('/')->assertOk()->assertSee($news->title);

        // Draft tidak tampil di publik.
        $draft = LandingNews::query()->create([
            'title' => 'Draf Internal',
            'slug' => 'draf-internal',
            'category' => 'berita',
            'content' => 'Konten draf.',
            'status' => LandingNews::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);

        $this->get(route('berita.index'))->assertDontSee($draft->title);
        $this->get(route('berita.show', $draft->slug))->assertNotFound();

        // Archived juga tidak tampil.
        $draft->update(['status' => LandingNews::STATUS_ARCHIVED]);
        $this->get(route('berita.show', 'draf-internal'))->assertNotFound();

        // Update mengganti thumbnail dan menghapus file lama.
        $oldThumbnail = $news->thumbnail;

        $this->actingAs($admin)->put(route('landing.news.update', $news), [
            'title' => 'Rapat Paripurna DPRD Kota Parepare (Diperbarui)',
            'category' => 'kegiatan',
            'excerpt' => 'Ringkasan diperbarui.',
            'content' => 'Konten diperbarui.',
            'thumbnail' => UploadedFile::fake()->image('baru.png', 1200, 675),
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'status' => LandingNews::STATUS_PUBLISHED,
        ])->assertRedirect(route('landing.news.index'));

        $news->refresh();
        $this->assertNotSame($oldThumbnail, $news->thumbnail);
        Storage::disk('public')->assertMissing($oldThumbnail);
        Storage::disk('public')->assertExists($news->thumbnail);

        $this->actingAs($admin)->delete(route('landing.news.destroy', $news))
            ->assertRedirect(route('landing.news.index'));

        $this->assertDatabaseMissing('landing_news', ['id' => $news->id]);
        Storage::disk('public')->assertMissing($news->thumbnail);
    }

    public function test_published_news_requires_thumbnail(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->from(route('landing.news.create'))
            ->post(route('landing.news.store'), [
                'title' => 'Berita Tanpa Gambar',
                'content' => 'Konten.',
                'status' => LandingNews::STATUS_PUBLISHED,
            ])
            ->assertRedirect(route('landing.news.create'))
            ->assertSessionHasErrors('thumbnail');
    }

    public function test_gallery_album_photo_upload_delete_isolation_and_reorder(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->post(route('landing.galleries.store'), [
            'title' => 'Sosialisasi Perda Kota Parepare',
            'description' => 'Dokumentasi sosialisasi.',
            'activity_date' => now()->subDays(2)->toDateString(),
            'location' => 'Aula DPRD',
            'status' => LandingGallery::STATUS_PUBLISHED,
            'photos' => [
                UploadedFile::fake()->image('foto-1.jpg', 800, 600),
                UploadedFile::fake()->image('foto-2.jpg', 800, 600),
                UploadedFile::fake()->image('foto-3.jpg', 800, 600),
            ],
            'captions' => ['Foto pertama', 'Foto kedua', 'Foto ketiga'],
        ])->assertRedirect();

        $gallery = LandingGallery::query()->firstOrFail();
        $gallery->load('photos');

        $this->assertCount(3, $gallery->photos);
        $this->assertSame('Foto pertama', $gallery->photos->first()->caption);
        $this->assertNotNull($gallery->cover_image);
        Storage::disk('public')->assertExists($gallery->photos->first()->image_path);

        // Hapus satu foto tidak menghapus foto lain.
        $photoToDelete = $gallery->photos->last();
        $remaining = $gallery->photos->take(2)->pluck('image_path')->all();

        $this->actingAs($admin)
            ->delete(route('landing.galleries.photos.destroy', [$gallery, $photoToDelete]))
            ->assertRedirect(route('landing.galleries.edit', $gallery));

        $this->assertDatabaseMissing('landing_gallery_photos', ['id' => $photoToDelete->id]);
        Storage::disk('public')->assertMissing($photoToDelete->image_path);

        foreach ($remaining as $path) {
            Storage::disk('public')->assertExists($path);
        }

        // Urutkan ulang foto yang tersisa.
        $photos = $gallery->photos()->orderByDesc('sort_order')->get();
        $orderedIds = $photos->pluck('id')->all();

        $this->actingAs($admin)->patch(route('landing.galleries.photos.reorder', $gallery), [
            'order' => $orderedIds,
        ])->assertRedirect(route('landing.galleries.edit', $gallery));

        foreach ($orderedIds as $index => $id) {
            $this->assertDatabaseHas('landing_gallery_photos', [
                'id' => $id,
                'sort_order' => $index + 1,
            ]);
        }

        // Caption dapat diperbarui dan sampul dapat diganti.
        $firstPhoto = $gallery->photos()->orderBy('sort_order')->first();

        $this->actingAs($admin)->patch(route('landing.galleries.photos.update', [$gallery, $firstPhoto]), [
            'caption' => 'Caption baru',
        ])->assertRedirect(route('landing.galleries.edit', $gallery));

        $this->assertDatabaseHas('landing_gallery_photos', [
            'id' => $firstPhoto->id,
            'caption' => 'Caption baru',
        ]);

        $this->actingAs($admin)->patch(route('landing.galleries.photos.cover', [$gallery, $firstPhoto]))
            ->assertRedirect(route('landing.galleries.edit', $gallery));

        $this->assertSame($firstPhoto->image_path, $gallery->fresh()->cover_image);

        // Tampil di halaman publik dan carousel landing page.
        $this->get(route('dokumentasi.index'))->assertOk()->assertSee($gallery->title);
        $this->get(route('dokumentasi.show', $gallery->slug))->assertOk()->assertSee('Caption baru');
        $this->get('/')->assertOk()->assertSee('Lihat Semua Dokumentasi');
    }

    public function test_draft_gallery_is_not_publicly_accessible(): void
    {
        $gallery = LandingGallery::query()->create([
            'title' => 'Album Draf',
            'slug' => 'album-draf',
            'activity_date' => now()->toDateString(),
            'status' => LandingGallery::STATUS_DRAFT,
        ]);

        $this->get(route('dokumentasi.index'))->assertDontSee($gallery->title);
        $this->get(route('dokumentasi.show', $gallery->slug))->assertNotFound();
    }

    public function test_agenda_crud_time_validation_and_public_archive(): void
    {
        $admin = User::factory()->administrator()->create();

        // Waktu selesai lebih awal daripada waktu mulai ditolak.
        $this->actingAs($admin)->from(route('landing.agendas.create'))->post(route('landing.agendas.store'), [
            'title' => 'Agenda Salah Waktu',
            'description' => 'Deskripsi.',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '09:00',
            'location' => 'Ruang Rapat',
            'event_status' => LandingAgenda::EVENT_UPCOMING,
            'status' => LandingAgenda::STATUS_PUBLISHED,
        ])->assertRedirect(route('landing.agendas.create'))->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('landing_agendas', 0);

        // Agenda mendatang yang dipublikasikan.
        $this->actingAs($admin)->post(route('landing.agendas.store'), [
            'title' => 'Rapat Kerja Komisi',
            'description' => 'Pembahasan program kerja.',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location' => 'Ruang Komisi 1',
            'event_status' => LandingAgenda::EVENT_UPCOMING,
            'status' => LandingAgenda::STATUS_PUBLISHED,
        ])->assertRedirect(route('landing.agendas.index'));

        $upcoming = LandingAgenda::query()->firstOrFail();

        $this->get(route('agenda.index'))->assertOk()->assertSee($upcoming->title);
        $this->get(route('agenda.show', $upcoming->slug))->assertOk()->assertSee($upcoming->title);

        // Beranda tidak lagi menampilkan section agenda; daftar lengkap ada di
        // halaman agenda publik.
        $this->get('/')->assertOk()->assertDontSee($upcoming->title);

        // Agenda lampau tetap tersedia di arsip, bukan di daftar mendatang.
        $past = LandingAgenda::query()->create([
            'title' => 'Agenda Lampau',
            'slug' => 'agenda-lampau',
            'description' => 'Kegiatan yang telah selesai.',
            'event_date' => now()->subMonth()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Aula',
            'event_status' => LandingAgenda::EVENT_COMPLETED,
            'status' => LandingAgenda::STATUS_PUBLISHED,
            'created_by' => $admin->id,
        ]);

        $this->get(route('agenda.index'))->assertDontSee($past->title);
        $this->get(route('agenda.arsip'))->assertOk()->assertSee($past->title);
        $this->get(route('agenda.show', $past->slug))->assertOk()->assertSee($past->title);

        // Draft tidak dapat diakses publik.
        $past->update(['status' => LandingAgenda::STATUS_DRAFT]);
        $this->get(route('agenda.show', 'agenda-lampau'))->assertNotFound();

        // Update agenda.
        $this->actingAs($admin)->put(route('landing.agendas.update', $upcoming), [
            'title' => 'Rapat Kerja Komisi (Revisi)',
            'description' => 'Deskripsi revisi.',
            'event_date' => now()->addDays(4)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '13:00',
            'location' => 'Ruang Komisi 2',
            'event_status' => LandingAgenda::EVENT_UPCOMING,
            'status' => LandingAgenda::STATUS_PUBLISHED,
        ])->assertRedirect(route('landing.agendas.index'));

        $this->assertDatabaseHas('landing_agendas', [
            'id' => $upcoming->id,
            'title' => 'Rapat Kerja Komisi (Revisi)',
        ]);

        $this->actingAs($admin)->delete(route('landing.agendas.destroy', $upcoming))
            ->assertRedirect(route('landing.agendas.index'));

        $this->assertDatabaseMissing('landing_agendas', ['id' => $upcoming->id]);
    }
}
