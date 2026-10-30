<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use Illuminate\Support\Facades\Gate;

trait ResolvesArchiveHierarchy
{
    protected function authorizeArchiveView(DprdMember $member): void
    {
        $this->authorize('viewArchives', $member);
    }

    protected function authorizeArchiveManage(DprdMember $member): void
    {
        Gate::authorize('manage-member-archives', $member);
    }

    /**
     * Kategori bersifat global, sehingga tidak ada relasi langsung ke
     * anggota. Pemeriksaan dimulai pada tahun anggaran.
     */
    protected function ensureCategoryExists(KategoriArsip $category): void
    {
        abort_unless($category->exists, 404);
    }

    protected function ensureYearMatches(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $year
    ): void {
        abort_unless(
            (int) $year->dprd_member_id === (int) $member->id
            && (int) $year->kategori_arsip_id === (int) $category->id,
            404
        );
    }

    protected function ensureFolderMatches(ArchiveYear $year, ArchiveFolder $folder): void
    {
        abort_unless((int) $folder->archive_year_id === (int) $year->id, 404);
    }

    protected function ensureDocumentMatches(ArchiveFolder $folder, Arsip $document): void
    {
        abort_unless((int) $document->archive_folder_id === (int) $folder->id, 404);
    }
}
