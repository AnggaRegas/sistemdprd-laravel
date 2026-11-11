<?php

use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Portal\AnalysisController;
use App\Http\Controllers\Portal\ArchiveCategoryController;
use App\Http\Controllers\Portal\ArchiveController;
use App\Http\Controllers\Portal\ArchiveDocumentController;
use App\Http\Controllers\Portal\CommissionController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\KamusAcuanController;
use App\Http\Controllers\Portal\KamusPemeriksaanController;
use App\Http\Controllers\Portal\LandingAgendaController;
use App\Http\Controllers\Portal\LandingGalleryController;
use App\Http\Controllers\Portal\LandingNewsController;
use App\Http\Controllers\Portal\MemberArchiveController;
use App\Http\Controllers\Portal\MemberController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PenugasanController;
use App\Http\Controllers\Portal\RdpDocumentController;
use App\Http\Controllers\Portal\RdpMeetingController;
use App\Http\Controllers\Portal\ReportController;
use App\Http\Controllers\Portal\SimilarityGroupController;
use App\Http\Controllers\Portal\StaffAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSite\AgendaPageController;
use App\Http\Controllers\PublicSite\AspirationController;
use App\Http\Controllers\PublicSite\GalleryPageController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\NewsPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public pages keep their existing Tailwind design. The administration area
| uses a separate Bootstrap based layout (resources/views/layouts/admin).
| Setiap route internal dilindungi middleware auth dan role di backend.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('welcome');

/*
|--------------------------------------------------------------------------
| Informasi publik landing page
|--------------------------------------------------------------------------
| Hanya konten berstatus Published yang dapat dibaca masyarakat. Halaman
| admin pengelolaannya berada di grup /landing-page (khusus administrator).
*/
Route::get('/berita', [NewsPageController::class, 'index'])->name('berita.index');
Route::get('/berita/{slug}', [NewsPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('berita.show');

Route::get('/dokumentasi', [GalleryPageController::class, 'index'])->name('dokumentasi.index');
Route::get('/dokumentasi/{slug}', [GalleryPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('dokumentasi.show');

Route::get('/agenda', [AgendaPageController::class, 'index'])->name('agenda.index');
Route::get('/agenda/arsip', [AgendaPageController::class, 'archive'])->name('agenda.arsip');
Route::get('/agenda/{slug}', [AgendaPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('agenda.show');

/*
|--------------------------------------------------------------------------
| Aspirasi masyarakat (tanpa login)
|--------------------------------------------------------------------------
| Formulir publik dibatasi rate limit untuk mengurangi spam. Seluruh input
| tetap divalidasi di backend dan status/penugasan tidak dapat dikirim dari
| browser masyarakat.
*/
Route::get('/aspirasi', [AspirationController::class, 'create'])->name('aspirasi.create');
Route::post('/aspirasi', [AspirationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('aspirasi.store');
Route::get('/aspirasi/berhasil', [AspirationController::class, 'success'])->name('aspirasi.success');
Route::get('/aspirasi/anggota', [AspirationController::class, 'members'])
    ->middleware('throttle:60,1')
    ->name('aspirasi.members');
Route::post('/aspirasi/analisis', [AspirationController::class, 'analyze'])
    ->middleware('throttle:30,1')
    ->name('aspirasi.analyze');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('ganti-kata-sandi-wajib', [ForcePasswordController::class, 'edit'])
        ->name('password.force.edit');

    Route::put('ganti-kata-sandi-wajib', [ForcePasswordController::class, 'update'])
        ->name('password.force.update');
});

Route::middleware(['auth', 'active', 'password.current'])->group(function () {
    Route::middleware('role:administrator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        /*
        |----------------------------------------------------------------------
        | Kelola akun staf
        |----------------------------------------------------------------------
        | Policy UserPolicy tetap menjadi lapisan otorisasi utama. Middleware
        | role dan gate mencegah staf mengakses URL secara langsung.
        */
        Route::get('/akun', [StaffAccountController::class, 'index'])->name('akun.index');
        Route::get('/akun/create', [StaffAccountController::class, 'create'])->name('akun.create');
        Route::post('/akun', [StaffAccountController::class, 'store'])->name('akun.store');
        Route::post('/akun/reset-password', [StaffAccountController::class, 'resetPassword'])
            ->name('akun.reset-password');
        Route::get('/akun/{user}/edit', [StaffAccountController::class, 'edit'])->name('akun.edit');
        Route::put('/akun/{user}', [StaffAccountController::class, 'update'])->name('akun.update');
        Route::patch('/akun/{user}/status', [StaffAccountController::class, 'toggleStatus'])
            ->name('akun.toggle-status');
        Route::delete('/akun/{user}', [StaffAccountController::class, 'destroy'])->name('akun.destroy');

        /*
        |----------------------------------------------------------------------
        | Anggota DPRD
        |----------------------------------------------------------------------
        | Staf pendamping tetap boleh membuka daftar dan arsip anggota yang
        | ditugaskan, tetapi CRUD anggota hanya untuk administrator. Policy
        | MemberPolicy menjadi lapisan otorisasi kedua di controller.
        */
        Route::get('/anggota/create', [MemberController::class, 'create'])->name('anggota.create');
        Route::post('/anggota', [MemberController::class, 'store'])->name('anggota.store');
        Route::get('/anggota/{member}/edit', [MemberController::class, 'edit'])->name('anggota.edit');
        Route::put('/anggota/{member}', [MemberController::class, 'update'])->name('anggota.update');
        Route::patch('/anggota/{member}/status', [MemberController::class, 'toggleStatus'])
            ->name('anggota.toggle-status');
        Route::delete('/anggota/{member}', [MemberController::class, 'destroy'])->name('anggota.destroy');
    });

    Route::middleware('role:staf_pendamping')->group(function () {
        Route::get('/dashboard/staf', [DashboardController::class, 'staff'])->name('dashboard.staf');
        Route::get('/dashboard/staf/ekspor', [PenugasanController::class, 'export'])
            ->name('dashboard.staf.ekspor');
    });

    /*
    |--------------------------------------------------------------------------
    | Kamus Acuan (khusus administrator / Super Admin)
    |--------------------------------------------------------------------------
    | Kamus memiliki versi tahunan dan riwayat sendiri. Hasil pemeriksaan
    | otomatis serta keputusan manual disimpan terpisah agar perubahan kamus
    | tidak mengubah riwayat laporan lama.
    */
    Route::middleware(['role:administrator', 'can:manage-kamus-acuan'])
        ->prefix('kamus-acuan')
        ->name('kamus.')
        ->group(function () {
            Route::get('/', [KamusAcuanController::class, 'index'])->name('index');

            Route::get('/versi/create', [KamusAcuanController::class, 'createVersion'])->name('versi.create');
            Route::post('/versi', [KamusAcuanController::class, 'storeVersion'])->name('versi.store');
            Route::get('/versi/{version}/edit', [KamusAcuanController::class, 'editVersion'])
                ->whereNumber('version')
                ->name('versi.edit');
            Route::put('/versi/{version}', [KamusAcuanController::class, 'updateVersion'])
                ->whereNumber('version')
                ->name('versi.update');
            Route::patch('/versi/{version}/aktifkan', [KamusAcuanController::class, 'activateVersion'])
                ->whereNumber('version')
                ->name('versi.aktifkan');

            Route::get('/versi/{version}/entri/create', [KamusAcuanController::class, 'createEntry'])
                ->whereNumber('version')
                ->name('entri.create');
            Route::post('/versi/{version}/entri', [KamusAcuanController::class, 'storeEntry'])
                ->whereNumber('version')
                ->name('entri.store');
            Route::get('/entri/{entry}/edit', [KamusAcuanController::class, 'editEntry'])
                ->whereNumber('entry')
                ->name('entri.edit');
            Route::put('/entri/{entry}', [KamusAcuanController::class, 'updateEntry'])
                ->whereNumber('entry')
                ->name('entri.update');
            Route::patch('/entri/{entry}/status', [KamusAcuanController::class, 'toggleEntry'])
                ->whereNumber('entry')
                ->name('entri.status');
            Route::delete('/entri/{entry}', [KamusAcuanController::class, 'destroyEntry'])
                ->whereNumber('entry')
                ->name('entri.destroy');

            Route::post('/versi/{version}/kriteria', [KamusAcuanController::class, 'storeKriteria'])
                ->whereNumber('version')
                ->name('kriteria.store');
            Route::put('/kriteria/{criteria}', [KamusAcuanController::class, 'updateKriteria'])
                ->whereNumber('criteria')
                ->name('kriteria.update');
            Route::delete('/kriteria/{criteria}', [KamusAcuanController::class, 'destroyKriteria'])
                ->whereNumber('criteria')
                ->name('kriteria.destroy');

            Route::get('/versi/{version}/impor', [KamusAcuanController::class, 'importForm'])
                ->whereNumber('version')
                ->name('versi.impor');
            Route::post('/versi/{version}/impor', [KamusAcuanController::class, 'import'])
                ->whereNumber('version')
                ->name('versi.impor.store');
            Route::get('/versi/{version}/unggah', [KamusAcuanController::class, 'uploadForm'])
                ->whereNumber('version')
                ->name('versi.unggah');
            Route::post('/versi/{version}/unggah', [KamusAcuanController::class, 'uploadPreview'])
                ->whereNumber('version')
                ->name('versi.unggah.preview');
            Route::get('/versi/{version}/unggah/pratinjau', [KamusAcuanController::class, 'uploadPreviewShow'])
                ->whereNumber('version')
                ->name('versi.unggah.pratinjau');
            Route::post('/versi/{version}/unggah/terapkan', [KamusAcuanController::class, 'uploadApply'])
                ->whereNumber('version')
                ->name('versi.unggah.apply');
            Route::post('/versi/{version}/unggah/batal', [KamusAcuanController::class, 'uploadCancel'])
                ->whereNumber('version')
                ->name('versi.unggah.cancel');
            Route::get('/versi/{version}/ekspor', [KamusAcuanController::class, 'export'])
                ->whereNumber('version')
                ->name('versi.ekspor');
            Route::get('/templat', [KamusAcuanController::class, 'template'])->name('templat');

            Route::get('/riwayat', [KamusAcuanController::class, 'riwayat'])->name('riwayat');

            Route::post('/laporan/{laporan}/periksa', [KamusPemeriksaanController::class, 'periksa'])
                ->whereNumber('laporan')
                ->name('laporan.periksa');
            Route::post('/laporan/{laporan}/verifikasi', [KamusPemeriksaanController::class, 'verifikasi'])
                ->whereNumber('laporan')
                ->name('laporan.verifikasi');
        });

    Route::middleware(['role:administrator', 'can:manage-rdp-archives'])->group(function () {
        /*
        |----------------------------------------------------------------------
        | Arsip komisi dan RDP
        |----------------------------------------------------------------------
        */
        Route::get('/komisi', [CommissionController::class, 'index'])->name('komisi.index');
        Route::get('/komisi/create', [CommissionController::class, 'create'])->name('komisi.create');
        Route::post('/komisi', [CommissionController::class, 'store'])->name('komisi.store');
        Route::get('/komisi/{commission}/edit', [CommissionController::class, 'edit'])->name('komisi.edit');
        Route::put('/komisi/{commission}', [CommissionController::class, 'update'])->name('komisi.update');
        Route::delete('/komisi/{commission}', [CommissionController::class, 'destroy'])->name('komisi.destroy');

        Route::get('/komisi/{commission}/rdp', [RdpMeetingController::class, 'index'])
            ->name('komisi.rdp.index');
        Route::get('/komisi/{commission}/rdp/create', [RdpMeetingController::class, 'create'])
            ->name('komisi.rdp.create');
        Route::post('/komisi/{commission}/rdp', [RdpMeetingController::class, 'store'])
            ->name('komisi.rdp.store');
        Route::get('/komisi/{commission}/rdp/{meeting}/edit', [RdpMeetingController::class, 'edit'])
            ->name('komisi.rdp.edit');
        Route::put('/komisi/{commission}/rdp/{meeting}', [RdpMeetingController::class, 'update'])
            ->name('komisi.rdp.update');
        Route::delete('/komisi/{commission}/rdp/{meeting}', [RdpMeetingController::class, 'destroy'])
            ->name('komisi.rdp.destroy');
        Route::get('/komisi/{commission}/rdp/{meeting}', [RdpMeetingController::class, 'show'])
            ->name('komisi.rdp.show');

        Route::post('/komisi/{commission}/rdp/{meeting}/dokumen', [RdpDocumentController::class, 'store'])
            ->name('komisi.rdp.documents.store');
        Route::get('/komisi/{commission}/rdp/{meeting}/dokumen/{document}/unduh', [RdpDocumentController::class, 'download'])
            ->name('komisi.rdp.documents.download');
        Route::delete('/komisi/{commission}/rdp/{meeting}/dokumen/{document}', [RdpDocumentController::class, 'destroy'])
            ->name('komisi.rdp.documents.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Manajemen Landing Page (khusus administrator / Superadmin)
    |--------------------------------------------------------------------------
    | Middleware role dan gate berlapis; staf pendamping tidak dapat membuka
    | modul ini meskipun mengetik URL secara langsung.
    |
    | Berita & Agenda tidak lagi ditautkan dari navigasi Manajemen Landing
    | Page. Route dan datanya dipertahankan sebagai arsip; hanya Dokumentasi
    | Kegiatan yang tampil pada menu pengelolaan.
    */
    Route::middleware(['role:administrator', 'can:manage-landing-page'])
        ->prefix('landing-page')
        ->name('landing.')
        ->group(function () {
            Route::get('/berita', [LandingNewsController::class, 'index'])->name('news.index');
            Route::get('/berita/create', [LandingNewsController::class, 'create'])->name('news.create');
            Route::post('/berita', [LandingNewsController::class, 'store'])->name('news.store');
            Route::get('/berita/{news}/edit', [LandingNewsController::class, 'edit'])
                ->whereNumber('news')
                ->name('news.edit');
            Route::put('/berita/{news}', [LandingNewsController::class, 'update'])
                ->whereNumber('news')
                ->name('news.update');
            Route::delete('/berita/{news}', [LandingNewsController::class, 'destroy'])
                ->whereNumber('news')
                ->name('news.destroy');

            Route::get('/dokumentasi', [LandingGalleryController::class, 'index'])->name('galleries.index');
            Route::get('/dokumentasi/create', [LandingGalleryController::class, 'create'])->name('galleries.create');
            Route::post('/dokumentasi', [LandingGalleryController::class, 'store'])->name('galleries.store');
            Route::get('/dokumentasi/{gallery}/edit', [LandingGalleryController::class, 'edit'])
                ->whereNumber('gallery')
                ->name('galleries.edit');
            Route::put('/dokumentasi/{gallery}', [LandingGalleryController::class, 'update'])
                ->whereNumber('gallery')
                ->name('galleries.update');
            Route::delete('/dokumentasi/{gallery}', [LandingGalleryController::class, 'destroy'])
                ->whereNumber('gallery')
                ->name('galleries.destroy');

            Route::post('/dokumentasi/{gallery}/foto', [LandingGalleryController::class, 'storePhotos'])
                ->whereNumber('gallery')
                ->name('galleries.photos.store');
            Route::patch('/dokumentasi/{gallery}/foto/urutkan', [LandingGalleryController::class, 'reorderPhotos'])
                ->whereNumber('gallery')
                ->name('galleries.photos.reorder');
            Route::patch('/dokumentasi/{gallery}/foto/{photo}', [LandingGalleryController::class, 'updatePhoto'])
                ->whereNumber('gallery')
                ->whereNumber('photo')
                ->name('galleries.photos.update');
            Route::patch('/dokumentasi/{gallery}/foto/{photo}/sampul', [LandingGalleryController::class, 'setCover'])
                ->whereNumber('gallery')
                ->whereNumber('photo')
                ->name('galleries.photos.cover');
            Route::delete('/dokumentasi/{gallery}/foto/{photo}', [LandingGalleryController::class, 'destroyPhoto'])
                ->whereNumber('gallery')
                ->whereNumber('photo')
                ->name('galleries.photos.destroy');

            Route::get('/agenda', [LandingAgendaController::class, 'index'])->name('agendas.index');
            Route::get('/agenda/create', [LandingAgendaController::class, 'create'])->name('agendas.create');
            Route::post('/agenda', [LandingAgendaController::class, 'store'])->name('agendas.store');
            Route::get('/agenda/{agenda}/edit', [LandingAgendaController::class, 'edit'])
                ->whereNumber('agenda')
                ->name('agendas.edit');
            Route::put('/agenda/{agenda}', [LandingAgendaController::class, 'update'])
                ->whereNumber('agenda')
                ->name('agendas.update');
            Route::delete('/agenda/{agenda}', [LandingAgendaController::class, 'destroy'])
                ->whereNumber('agenda')
                ->name('agendas.destroy');
        });

    Route::middleware('role:administrator,staf_pendamping')->group(function () {
        Route::get('/arsip', [ArchiveController::class, 'index'])->name('arsip.index');
        Route::get('/arsip/create', [ArchiveController::class, 'create'])->name('arsip.create');
        Route::post('/arsip', [ArchiveController::class, 'store'])->name('arsip.store');
        Route::get('/arsip/{archive}/edit', [ArchiveController::class, 'edit'])->name('arsip.edit');
        Route::put('/arsip/{archive}', [ArchiveController::class, 'update'])->name('arsip.update');
        Route::delete('/arsip/{archive}', [ArchiveController::class, 'destroy'])->name('arsip.destroy');
        Route::get('/arsip/{archive}/unduh', [ArchiveController::class, 'download'])->name('arsip.download');

        Route::get('/anggota', [MemberController::class, 'index'])->name('anggota.index');
        Route::get('/anggota/{member}', [MemberController::class, 'show'])
            ->whereNumber('member')
            ->name('anggota.show');

        /*
        |----------------------------------------------------------------------
        | Arsip bertingkat per anggota
        |----------------------------------------------------------------------
        | Anggota -> Kategori -> Tahun Anggaran -> Folder -> Dokumen.
        | Seluruh endpoint memeriksa kepemilikan anggota di controller dan
        | policy, bukan hanya mengandalkan middleware login.
        */
        Route::get('/anggota-dprd/{member}/arsip', [MemberArchiveController::class, 'categories'])
            ->name('anggota.arsip');

        Route::get('/anggota-dprd/{member}/arsip/kategori/create', [ArchiveCategoryController::class, 'create'])
            ->name('anggota.arsip.kategori.create');
        Route::post('/anggota-dprd/{member}/arsip/kategori', [ArchiveCategoryController::class, 'store'])
            ->name('anggota.arsip.kategori.store');
        Route::get('/anggota-dprd/{member}/arsip/kategori/{category}/edit', [ArchiveCategoryController::class, 'edit'])
            ->name('anggota.arsip.kategori.edit');
        Route::put('/anggota-dprd/{member}/arsip/kategori/{category}', [ArchiveCategoryController::class, 'update'])
            ->name('anggota.arsip.kategori.update');
        Route::delete('/anggota-dprd/{member}/arsip/kategori/{category}', [ArchiveCategoryController::class, 'destroy'])
            ->name('anggota.arsip.kategori.destroy');

        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun', [MemberArchiveController::class, 'years'])
            ->name('anggota.arsip.tahun');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/create', [MemberArchiveController::class, 'createYear'])
            ->name('anggota.arsip.tahun.create');
        Route::post('/anggota-dprd/{member}/arsip/{category}/tahun', [MemberArchiveController::class, 'storeYear'])
            ->name('anggota.arsip.tahun.store');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/edit', [MemberArchiveController::class, 'editYear'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.tahun.edit');
        Route::put('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}', [MemberArchiveController::class, 'updateYear'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.tahun.update');
        Route::delete('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}', [MemberArchiveController::class, 'destroyYear'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.tahun.destroy');

        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder', [MemberArchiveController::class, 'folders'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/create', [MemberArchiveController::class, 'createFolder'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder.create');
        Route::post('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder', [MemberArchiveController::class, 'storeFolder'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder.store');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/edit', [MemberArchiveController::class, 'editFolder'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder.edit');
        Route::put('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}', [MemberArchiveController::class, 'updateFolder'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder.update');
        Route::delete('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}', [MemberArchiveController::class, 'destroyFolder'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.folder.destroy');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}', [MemberArchiveController::class, 'documents'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.dokumen');

        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen/create', [ArchiveDocumentController::class, 'create'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.dokumen.create');
        Route::post('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen', [ArchiveDocumentController::class, 'store'])
            ->whereNumber('archiveYear')
            ->name('anggota.arsip.dokumen.store');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen/{document}/edit', [ArchiveDocumentController::class, 'edit'])
            ->whereNumber('archiveYear')
            ->whereNumber('document')
            ->name('anggota.arsip.dokumen.edit');
        Route::put('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen/{document}', [ArchiveDocumentController::class, 'update'])
            ->whereNumber('archiveYear')
            ->whereNumber('document')
            ->name('anggota.arsip.dokumen.update');
        Route::delete('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen/{document}', [ArchiveDocumentController::class, 'destroy'])
            ->whereNumber('archiveYear')
            ->whereNumber('document')
            ->name('anggota.arsip.dokumen.destroy');
        Route::get('/anggota-dprd/{member}/arsip/{category}/tahun/{archiveYear}/folder/{folder}/dokumen/{document}/unduh', [ArchiveDocumentController::class, 'download'])
            ->whereNumber('archiveYear')
            ->whereNumber('document')
            ->name('anggota.arsip.dokumen.download');

        Route::get('/notifikasi/{notificationKey}', [NotificationController::class, 'open'])
            ->name('notifikasi.buka');
        Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAll'])
            ->name('notifikasi.baca-semua');

        /*
        |----------------------------------------------------------------------
        | Monitoring penugasan staf pendamping
        |----------------------------------------------------------------------
        | Superadmin melihat seluruh penugasan dan hasil tindak lanjut. Staf
        | hanya dapat membuka penugasan miliknya sendiri (diperiksa controller).
        */
        Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan.index');
        Route::get('/penugasan/{penugasan}', [PenugasanController::class, 'show'])
            ->whereNumber('penugasan')
            ->name('penugasan.show');
        Route::get('/penugasan/{penugasan}/bukti', [PenugasanController::class, 'downloadBukti'])
            ->whereNumber('penugasan')
            ->name('penugasan.bukti');

        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
        Route::post('/laporan/kemiripan/{laporan}/keputusan', [SimilarityGroupController::class, 'decideForReport'])
            ->whereNumber('laporan')
            ->name('laporan.kemiripan.decide');
        Route::post('/laporan/kemiripan/periksa/{member}', [SimilarityGroupController::class, 'recheck'])
            ->whereNumber('member')
            ->name('laporan.kemiripan.recheck');
        Route::get('/laporan/{laporan}', [ReportController::class, 'show'])
            ->whereNumber('laporan')
            ->name('laporan.show');
        Route::get('/laporan/{laporan}/lampiran', [ReportController::class, 'downloadAttachment'])
            ->whereNumber('laporan')
            ->name('laporan.lampiran');
        Route::get('/laporan/{laporan}/lampiran/lihat', [ReportController::class, 'viewAttachment'])
            ->whereNumber('laporan')
            ->name('laporan.lampiran.lihat');

        Route::middleware('role:administrator')->group(function () {
            Route::get('/laporan/unread-counts', [ReportController::class, 'unreadCounts'])
                ->name('laporan.unread-counts');
            Route::patch('/laporan/{laporan}/persetujuan', [ReportController::class, 'approve'])
                ->whereNumber('laporan')
                ->name('laporan.persetujuan');
            Route::patch('/laporan/{laporan}/status', [ReportController::class, 'updateStatus'])
                ->whereNumber('laporan')
                ->name('laporan.status.update');
            Route::delete('/laporan/{laporan}', [ReportController::class, 'destroy'])
                ->whereNumber('laporan')
                ->name('laporan.destroy');
            Route::get('/laporan/{laporan}/analisis', [AnalysisController::class, 'show'])
                ->whereNumber('laporan')
                ->name('laporan.analisis');
            Route::post('/penugasan/{penugasan}/verifikasi', [PenugasanController::class, 'verify'])
                ->whereNumber('penugasan')
                ->name('penugasan.verifikasi');
        });

        Route::middleware('role:staf_pendamping')->group(function () {
            Route::patch('/laporan/{laporan}/tindak-lanjut', [ReportController::class, 'updateStatusForStaff'])
                ->whereNumber('laporan')
                ->name('laporan.tindak-lanjut.update');
            Route::post('/penugasan/{penugasan}/mulai', [PenugasanController::class, 'start'])
                ->whereNumber('penugasan')
                ->name('penugasan.mulai');
            Route::post('/penugasan/{penugasan}/selesai', [PenugasanController::class, 'complete'])
                ->whereNumber('penugasan')
                ->name('penugasan.selesai');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__.'/auth.php';
