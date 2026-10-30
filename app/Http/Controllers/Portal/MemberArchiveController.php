<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesArchiveHierarchy;
use App\Http\Requests\ArchiveFolderRequest;
use App\Http\Requests\ArchiveYearRequest;
use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberArchiveController extends Controller
{
    use ResolvesArchiveHierarchy;

    /**
     * Halaman kategori arsip milik satu anggota.
     */
    public function categories(Request $request, DprdMember $member): View
    {
        $this->authorizeArchiveView($member);
        $member->loadMissing('staff');

        $categories = KategoriArsip::query()
            ->where(function ($query) use ($member) {
                $query->where('is_active', true)
                    ->orWhereHas('archiveYears', function ($yearQuery) use ($member) {
                        $yearQuery->where('dprd_member_id', $member->id);
                    });
            })
            ->withCount([
                'archiveYears as years_count' => function ($query) use ($member) {
                    $query->where('dprd_member_id', $member->id);
                },
                'arsips as documents_count' => function ($query) use ($member) {
                    $query->where('dprd_member_id', $member->id)
                        ->whereNotNull('archive_folder_id');
                },
            ])
            ->orderByRaw("CASE WHEN nama = 'HASIL RESES' THEN 0 WHEN nama = 'POKOK-POKOK PIKIRAN' THEN 1 ELSE 2 END")
            ->orderBy('nama')
            ->get();

        $totalFolders = ArchiveFolder::query()
            ->whereHas('year', function ($query) use ($member) {
                $query->where('dprd_member_id', $member->id);
            })
            ->count();

        return view('members.archives', [
            'member' => $member,
            'categories' => $categories,
            'totalDocuments' => (int) $categories->sum('documents_count'),
            'totalYears' => (int) $categories->sum('years_count'),
            'totalFolders' => $totalFolders,
            'stepper' => $this->stepper($member),
            'currentStep' => 2,
        ]);
    }

    /**
     * Daftar tahun anggaran untuk anggota + kategori terpilih.
     */
    public function years(Request $request, DprdMember $member, KategoriArsip $category): View
    {
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $member->loadMissing('staff');

        $years = ArchiveYear::query()
            ->where('dprd_member_id', $member->id)
            ->where('kategori_arsip_id', $category->id)
            ->withCount(['folders', 'documents'])
            ->orderByDesc('tahun')
            ->get();

        return view('members.archive-years', [
            'member' => $member,
            'category' => $category,
            'years' => $years,
            'stepper' => $this->stepper($member, $category),
            'currentStep' => 3,
        ]);
    }

    public function createYear(DprdMember $member, KategoriArsip $category): View
    {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');

        return view('members.archive-years-create', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => new ArchiveYear(['tahun' => (int) now()->format('Y')]),
            'stepper' => $this->stepper($member, $category),
            'currentStep' => 3,
        ]);
    }

    public function storeYear(
        ArchiveYearRequest $request,
        DprdMember $member,
        KategoriArsip $category
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');

        $archiveYear = DB::transaction(function () use ($request, $member, $category) {
            return ArchiveYear::query()->create([
                'dprd_member_id' => $member->id,
                'kategori_arsip_id' => $category->id,
                'tahun' => (int) $request->validated()['tahun'],
            ]);
        });

        return redirect()
            ->route('anggota.arsip.tahun', [$member, $category])
            ->with('success', 'Tahun anggaran '.$archiveYear->tahun.' berhasil ditambahkan.');
    }

    public function editYear(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): View {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);

        return view('members.archive-years-edit', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => $archiveYear,
            'stepper' => $this->stepper($member, $category),
            'currentStep' => 3,
        ]);
    }

    public function updateYear(
        ArchiveYearRequest $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);

        $archiveYear->update([
            'tahun' => (int) $request->validated()['tahun'],
        ]);

        return redirect()
            ->route('anggota.arsip.tahun', [$member, $category])
            ->with('success', 'Tahun anggaran berhasil diperbarui.');
    }

    public function destroyYear(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);

        if ($archiveYear->folders()->exists()) {
            return back()->with(
                'error',
                'Tahun anggaran tidak dapat dihapus karena masih memiliki folder. Hapus folder terlebih dahulu agar tidak ada dokumen yang hilang.'
            );
        }

        $tahun = $archiveYear->tahun;
        $archiveYear->delete();

        return redirect()
            ->route('anggota.arsip.tahun', [$member, $category])
            ->with('success', 'Tahun anggaran '.$tahun.' berhasil dihapus.');
    }

    /**
     * Daftar folder pada satu tahun anggaran.
     */
    public function folders(
        Request $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): View {
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $member->loadMissing('staff');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
        ];

        $baseQuery = ArchiveFolder::query()->where('archive_year_id', $archiveYear->id);
        $query = (clone $baseQuery)->withCount('documents');

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($folderQuery) use ($needle) {
                $folderQuery->whereRaw('LOWER(nama) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$needle]);
            });
        }

        $folders = $query
            ->orderByDesc('updated_at')
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();

        return view('members.archive-folders', [
            'member' => $member,
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folders' => $folders,
            'filters' => $filters,
            'totalFolders' => (clone $baseQuery)->count(),
            'filteredTotal' => (clone $query)->count(),
            'hasFilters' => $filters['q'] !== '',
            'stepper' => $this->stepper($member, $category, $archiveYear),
            'currentStep' => 4,
        ]);
    }

    public function createFolder(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): View {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');
        $this->ensureYearMatches($member, $category, $archiveYear);

        return view('members.archive-folders-create', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folder' => new ArchiveFolder(),
            'stepper' => $this->stepper($member, $category, $archiveYear),
            'currentStep' => 4,
        ]);
    }

    public function storeFolder(
        ArchiveFolderRequest $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');
        $this->ensureYearMatches($member, $category, $archiveYear);

        $folder = DB::transaction(function () use ($request, $archiveYear) {
            return ArchiveFolder::query()->create([
                'archive_year_id' => $archiveYear->id,
                'nama' => $request->validated()['nama'],
                'deskripsi' => $request->validated()['deskripsi'] ?? null,
            ]);
        });

        return redirect()
            ->route('anggota.arsip.folder', [$member, $category, $archiveYear])
            ->with('success', 'Folder '.$folder->nama.' berhasil ditambahkan.');
    }

    public function editFolder(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): View {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);

        return view('members.archive-folders-edit', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folder' => $folder,
            'stepper' => $this->stepper($member, $category, $archiveYear),
            'currentStep' => 4,
        ]);
    }

    public function updateFolder(
        ArchiveFolderRequest $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);

        $folder->update([
            'nama' => $request->validated()['nama'],
            'deskripsi' => $request->validated()['deskripsi'] ?? null,
        ]);

        return redirect()
            ->route('anggota.arsip.folder', [$member, $category, $archiveYear])
            ->with('success', 'Folder berhasil diperbarui.');
    }

    public function destroyFolder(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): RedirectResponse {
        $this->authorizeArchiveManage($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);

        if ($folder->documents()->withTrashed()->exists()) {
            return back()->with(
                'error',
                'Folder tidak dapat dihapus karena masih berisi dokumen. Pindahkan atau hapus dokumen terlebih dahulu.'
            );
        }

        $nama = $folder->nama;
        $folder->delete();

        return redirect()
            ->route('anggota.arsip.folder', [$member, $category, $archiveYear])
            ->with('success', 'Folder '.$nama.' berhasil dihapus.');
    }

    /**
     * Daftar berkas di dalam folder.
     */
    public function documents(
        Request $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): View {
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);
        $member->loadMissing('staff');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
        ];

        $baseQuery = Arsip::query()->where('archive_folder_id', $folder->id);
        $query = (clone $baseQuery)->with(['kategoriArsip', 'uploader']);

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($documentQuery) use ($needle) {
                $documentQuery->whereRaw('LOWER(nama) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(nomor) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(file_name) LIKE ?', [$needle]);
            });
        }

        $documents = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('members.archive-documents', [
            'member' => $member,
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folder' => $folder,
            'documents' => $documents,
            'filters' => $filters,
            'totalDocuments' => (clone $baseQuery)->count(),
            'filteredTotal' => (clone $query)->count(),
            'hasFilters' => $filters['q'] !== '',
            'stepper' => $this->stepper($member, $category, $archiveYear, $folder),
            'currentStep' => 5,
        ]);
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    private function stepper(
        DprdMember $member,
        ?KategoriArsip $category = null,
        ?ArchiveYear $archiveYear = null,
        ?ArchiveFolder $folder = null
    ): array {
        return [
            ['label' => 'Pilih Anggota DPRD', 'url' => route('anggota.index')],
            ['label' => 'Pilih Kategori', 'url' => route('anggota.arsip', $member)],
            [
                'label' => 'Tahun Anggaran',
                'url' => $category
                    ? route('anggota.arsip.tahun', [$member, $category])
                    : null,
            ],
            [
                'label' => 'Folder Dokumen',
                'url' => $category && $archiveYear
                    ? route('anggota.arsip.folder', [$member, $category, $archiveYear])
                    : null,
            ],
            [
                'label' => 'Berkas Arsip',
                'url' => $category && $archiveYear && $folder
                    ? route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])
                    : null,
            ],
        ];
    }
}
