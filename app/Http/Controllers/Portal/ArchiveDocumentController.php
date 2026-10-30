<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesArchiveHierarchy;
use App\Http\Requests\ArchiveDocumentRequest;
use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Support\ArchiveFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveDocumentController extends Controller
{
    use ResolvesArchiveHierarchy;

    public function create(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): View {
        $this->authorize('create', Arsip::class);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);

        return view('members.archive-documents-create', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folder' => $folder,
            'document' => new Arsip([
                'jenis' => Arsip::JENIS_MASUK,
                'tanggal' => now()->toDateString(),
            ]),
            'stepper' => $this->stepper($member, $category, $archiveYear, $folder),
            'currentStep' => 5,
        ]);
    }

    public function store(
        ArchiveDocumentRequest $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): RedirectResponse {
        $this->authorize('create', Arsip::class);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        abort_unless($category->is_active, 403, 'Kategori arsip tidak aktif.');
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);

        $validated = $request->validated();
        $fileData = ArchiveFileStorage::store($request->file('file'));

        $document = DB::transaction(function () use ($request, $validated, $fileData, $member, $category, $archiveYear, $folder) {
            return Arsip::query()->create([
                'nomor' => $this->generateNomor($member, $archiveYear, $folder),
                'nama' => $validated['nama'],
                'kategori_arsip_id' => $category->id,
                'archive_folder_id' => $folder->id,
                'dprd_member_id' => $member->id,
                'jenis' => $validated['jenis'],
                'status' => Arsip::STATUS_DRAF,
                'tanggal' => $validated['tanggal'] ?? null,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'file_path' => $fileData['path'],
                'file_name' => $fileData['name'],
                'file_size' => $fileData['size'],
                'uploaded_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])
            ->with('success', 'Dokumen "'.$document->nama.'" berhasil diunggah.');
    }

    public function edit(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder,
        Arsip $document
    ): View {
        $this->authorize('update', $document);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);
        $this->ensureDocumentMatches($folder, $document);

        return view('members.archive-documents-edit', [
            'member' => $member->loadMissing('staff'),
            'category' => $category,
            'archiveYear' => $archiveYear,
            'folder' => $folder,
            'document' => $document,
            'stepper' => $this->stepper($member, $category, $archiveYear, $folder),
            'currentStep' => 5,
        ]);
    }

    public function update(
        ArchiveDocumentRequest $request,
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder,
        Arsip $document
    ): RedirectResponse {
        $this->authorize('update', $document);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);
        $this->ensureDocumentMatches($folder, $document);

        $validated = $request->validated();
        $fileData = $request->file('file')
            ? ArchiveFileStorage::store($request->file('file'))
            : null;

        DB::transaction(function () use ($document, $validated, $fileData) {
            $attributes = [
                'nama' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'tanggal' => $validated['tanggal'] ?? null,
                'deskripsi' => $validated['deskripsi'] ?? null,
            ];

            if ($fileData) {
                $attributes['file_path'] = $fileData['path'];
                $attributes['file_name'] = $fileData['name'];
                $attributes['file_size'] = $fileData['size'];
            }

            $document->update($attributes);
        });

        return redirect()
            ->route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])
            ->with('success', 'Metadata dokumen berhasil diperbarui.');
    }

    public function destroy(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder,
        Arsip $document
    ): RedirectResponse {
        $this->authorize('delete', $document);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);
        $this->ensureDocumentMatches($folder, $document);

        $nama = $document->nama;

        DB::transaction(function () use ($document) {
            $document->delete();
        });

        return redirect()
            ->route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])
            ->with('success', 'Dokumen "'.$nama.'" berhasil dihapus. Berkas fisik dan riwayat tetap tersimpan untuk audit.');
    }

    public function download(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder,
        Arsip $document
    ): StreamedResponse|RedirectResponse {
        $this->authorize('download', $document);
        $this->authorizeArchiveView($member);
        $this->ensureCategoryExists($category);
        $this->ensureYearMatches($member, $category, $archiveYear);
        $this->ensureFolderMatches($archiveYear, $folder);
        $this->ensureDocumentMatches($folder, $document);

        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            return back()->with('error', 'Berkas dokumen tidak ditemukan pada penyimpanan.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name ?: 'dokumen-'.$document->id,
            ['Content-Type' => Storage::disk('local')->mimeType($document->file_path) ?: 'application/octet-stream']
        );
    }

    private function generateNomor(
        DprdMember $member,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): string {
        $prefix = 'ARS/'.($member->kode ?: 'DWN').'/'.$archiveYear->tahun.'/'.$folder->id;
        $sequence = Arsip::withTrashed()
            ->where('archive_folder_id', $folder->id)
            ->count() + 1;

        do {
            $nomor = $prefix.'/'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Arsip::withTrashed()->where('nomor', $nomor)->exists());

        return $nomor;
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    private function stepper(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveYear $archiveYear,
        ArchiveFolder $folder
    ): array {
        return [
            ['label' => 'Pilih Anggota DPRD', 'url' => route('anggota.index')],
            ['label' => 'Pilih Kategori', 'url' => route('anggota.arsip', $member)],
            ['label' => 'Tahun Anggaran', 'url' => route('anggota.arsip.tahun', [$member, $category])],
            ['label' => 'Folder Dokumen', 'url' => route('anggota.arsip.folder', [$member, $category, $archiveYear])],
            ['label' => 'Berkas Arsip', 'url' => route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])],
        ];
    }
}
