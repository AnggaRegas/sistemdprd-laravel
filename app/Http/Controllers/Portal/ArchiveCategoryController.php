<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArchiveCategoryRequest;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArchiveCategoryController extends Controller
{
    public function create(DprdMember $member): View
    {
        $this->authorize('create', KategoriArsip::class);

        return view('members.archive-categories-create', [
            'member' => $member,
            'category' => new KategoriArsip(['is_active' => true]),
        ]);
    }

    public function store(ArchiveCategoryRequest $request, DprdMember $member): RedirectResponse
    {
        $this->authorize('create', KategoriArsip::class);

        $validated = $request->validated();

        $category = DB::transaction(function () use ($validated) {
            return KategoriArsip::query()->create([
                'nama' => $validated['nama'],
                'kode' => $validated['kode'] ?? null,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        });

        return redirect()
            ->route('anggota.arsip', $member)
            ->with('success', 'Kategori '.$category->nama.' berhasil ditambahkan.');
    }

    public function edit(DprdMember $member, KategoriArsip $category): View
    {
        $this->authorize('update', $category);

        return view('members.archive-categories-edit', [
            'member' => $member,
            'category' => $category,
        ]);
    }

    public function update(
        ArchiveCategoryRequest $request,
        DprdMember $member,
        KategoriArsip $category
    ): RedirectResponse {
        $this->authorize('update', $category);

        $validated = $request->validated();

        $category->update([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('anggota.arsip', $member)
            ->with('success', 'Kategori '.$category->nama.' berhasil diperbarui.');
    }

    public function destroy(DprdMember $member, KategoriArsip $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $yearCount = $category->archiveYears()->count();
        $archiveCount = $category->arsips()->withTrashed()->count();

        if ($yearCount > 0 || $archiveCount > 0) {
            $details = [];

            if ($yearCount > 0) {
                $details[] = $yearCount.' tahun anggaran';
            }

            if ($archiveCount > 0) {
                $details[] = $archiveCount.' dokumen';
            }

            return back()->with(
                'error',
                'Kategori '.$category->nama.' tidak dapat dihapus karena masih digunakan oleh '
                .implode(' dan ', $details)
                .'. Nonaktifkan kategori melalui tombol Edit agar arsip lama tetap aman.'
            );
        }

        $nama = $category->nama;
        $category->delete();

        return redirect()
            ->route('anggota.arsip', $member)
            ->with('success', 'Kategori '.$nama.' berhasil dihapus.');
    }
}
