<?php

use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Portal\ArchiveCategoryController;
use App\Http\Controllers\Portal\ArchiveController;
use App\Http\Controllers\Portal\ArchiveDocumentController;
use App\Http\Controllers\Portal\CommissionController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\MemberArchiveController;
use App\Http\Controllers\Portal\MemberController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\RdpDocumentController;
use App\Http\Controllers\Portal\RdpMeetingController;
use App\Http\Controllers\Portal\ReportController;
use App\Http\Controllers\Portal\StaffAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSite\AspirationController;
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

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

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

    Route::middleware('role:administrator,staf_pendamping')->group(function () {
        Route::get('/arsip', [ArchiveController::class, 'index'])->name('arsip.index');
        Route::get('/arsip/create', [ArchiveController::class, 'create'])->name('arsip.create');
        Route::post('/arsip', [ArchiveController::class, 'store'])->name('arsip.store');
        Route::get('/arsip/{archive}/edit', [ArchiveController::class, 'edit'])->name('arsip.edit');
        Route::put('/arsip/{archive}', [ArchiveController::class, 'update'])->name('arsip.update');
        Route::delete('/arsip/{archive}', [ArchiveController::class, 'destroy'])->name('arsip.destroy');
        Route::get('/arsip/{archive}/unduh', [ArchiveController::class, 'download'])->name('arsip.download');

        Route::get('/anggota', [MemberController::class, 'index'])->name('anggota.index');

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

        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/{laporan}', [ReportController::class, 'show'])
            ->whereNumber('laporan')
            ->name('laporan.show');
        Route::get('/laporan/{laporan}/lampiran', [ReportController::class, 'downloadAttachment'])
            ->whereNumber('laporan')
            ->name('laporan.lampiran');

        Route::middleware('role:administrator')->group(function () {
            Route::patch('/laporan/{laporan}/penugasan', [ReportController::class, 'updateAssignment'])
                ->whereNumber('laporan')
                ->name('laporan.penugasan.update');
            Route::patch('/laporan/{laporan}/status', [ReportController::class, 'updateStatus'])
                ->whereNumber('laporan')
                ->name('laporan.status.update');
            Route::patch('/laporan/{laporan}/komisi', [ReportController::class, 'updateCommission'])
                ->whereNumber('laporan')
                ->name('laporan.komisi.update');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__.'/auth.php';
