<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArchiveRequest;
use App\Models\AktivitasSistem;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Models\User;
use App\Support\ArchiveFileStorage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Arsip::class);

        $user = $request->user();

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'kategori' => (string) $request->query('kategori'),
            'status' => $this->allowedFilter(
                $request->query('status'),
                array_keys(Arsip::STATUS_LABELS)
            ),
            'tanggal' => $this->validDate($request->query('tanggal')),
            'member' => (string) $request->query('member'),
        ];

        $baseQuery = Arsip::query()->visibleTo($user);

        $query = (clone $baseQuery)
            ->with(['kategoriArsip', 'member']);

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(nomor) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(nama) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$needle])
                    ->orWhereHas('member', function ($memberQuery) use ($needle) {
                        $memberQuery->whereRaw('LOWER(nama_lengkap) LIKE ?', [$needle]);
                    });
            });
        }

        if ($filters['kategori'] !== '' && ctype_digit($filters['kategori'])) {
            $query->where('kategori_arsip_id', (int) $filters['kategori']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['tanggal'] !== '') {
            $query->whereDate('tanggal', $filters['tanggal']);
        }

        if ($filters['member'] !== '' && ctype_digit($filters['member'])) {
            $query->where('dprd_member_id', (int) $filters['member']);
        }

        $filteredTotal = (clone $query)->count();

        $archives = $query
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('archives.index', [
            'archives' => $archives,
            'filters' => $filters,
            'categories' => $this->categories(),
            'statuses' => Arsip::STATUS_LABELS,
            'members' => $this->memberOptions($user),
            'totalArchives' => (clone $baseQuery)->count(),
            'filteredTotal' => $filteredTotal,
            'hasFilters' => $filters['q'] !== ''
                || $filters['kategori'] !== ''
                || $filters['status'] !== ''
                || $filters['tanggal'] !== ''
                || $filters['member'] !== '',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Arsip::class);

        $selectedMemberId = null;

        if ($request->filled('member')) {
            $selectedMember = DprdMember::query()
                ->visibleTo($request->user())
                ->findOrFail($request->query('member'));

            $selectedMemberId = $selectedMember->id;
        }

        return view('archives.create', [
            'archive' => new Arsip([
                'tanggal' => now()->toDateString(),
                'jenis' => Arsip::JENIS_MASUK,
                'status' => Arsip::STATUS_DRAF,
            ]),
            'categories' => $this->categories(),
            'members' => $this->memberOptions($request->user()),
            'selectedMemberId' => $selectedMemberId,
        ]);
    }

    public function store(ArchiveRequest $request): RedirectResponse
    {
        $this->authorize('create', Arsip::class);

        $validated = $request->validated();
        $member = $this->resolveMember($request, $validated['dprd_member_id'] ?? null);
        $fileData = $this->storeUploadedFile($request);

        $archive = DB::transaction(function () use ($validated, $member, $fileData, $request) {
            return Arsip::query()->create([
                'nomor' => $validated['nomor'],
                'nama' => $validated['nama'],
                'kategori_arsip_id' => $validated['kategori_arsip_id'],
                'dprd_member_id' => $member?->id,
                'jenis' => $validated['jenis'],
                'status' => $validated['status'],
                'tanggal' => $validated['tanggal'],
                'deskripsi' => $validated['deskripsi'] ?: null,
                'file_path' => $fileData['path'] ?? null,
                'file_name' => $fileData['name'] ?? null,
                'file_size' => $fileData['size'] ?? null,
                'uploaded_by' => $request->user()->id,
            ]);
        });

        AktivitasSistem::catat(
            'arsip.created',
            $request->user(),
            $archive,
            [
                'dprd_member_id' => $archive->dprd_member_id,
                'kategori_arsip_id' => $archive->kategori_arsip_id,
            ],
            'Arsip '.$archive->nomor.' ditambahkan.'
        );

        return redirect()
            ->route('arsip.index')
            ->with('success', 'Arsip berhasil ditambahkan.');
    }

    public function edit(Request $request, Arsip $archive): View
    {
        $this->authorize('update', $archive);

        return view('archives.edit', [
            'archive' => $archive->load(['kategoriArsip', 'member']),
            'categories' => $this->categories(),
            'members' => $this->memberOptions($request->user()),
            'selectedMemberId' => $archive->dprd_member_id,
        ]);
    }

    public function update(ArchiveRequest $request, Arsip $archive): RedirectResponse
    {
        $this->authorize('update', $archive);

        $validated = $request->validated();
        $member = $this->resolveMember($request, $validated['dprd_member_id'] ?? null);
        $fileData = $this->storeUploadedFile($request);

        DB::transaction(function () use ($archive, $validated, $member, $fileData) {
            $attributes = [
                'nomor' => $validated['nomor'],
                'nama' => $validated['nama'],
                'kategori_arsip_id' => $validated['kategori_arsip_id'],
                'dprd_member_id' => $member?->id,
                'jenis' => $validated['jenis'],
                'status' => $validated['status'],
                'tanggal' => $validated['tanggal'],
                'deskripsi' => $validated['deskripsi'] ?: null,
            ];

            if ($fileData) {
                $attributes['file_path'] = $fileData['path'];
                $attributes['file_name'] = $fileData['name'];
                $attributes['file_size'] = $fileData['size'];
            }

            $archive->update($attributes);
        });

        AktivitasSistem::catat(
            'arsip.updated',
            $request->user(),
            $archive,
            [
                'dprd_member_id' => $archive->dprd_member_id,
                'kategori_arsip_id' => $archive->kategori_arsip_id,
            ],
            'Arsip '.$archive->nomor.' diperbarui.'
        );

        return redirect()
            ->route('arsip.index')
            ->with('success', 'Arsip berhasil diperbarui.');
    }

    public function destroy(Request $request, Arsip $archive): RedirectResponse
    {
        $this->authorize('delete', $archive);

        $nomor = $archive->nomor;

        DB::transaction(function () use ($archive) {
            $archive->delete();
        });

        AktivitasSistem::catat(
            'arsip.deleted',
            $request->user(),
            $archive,
            ['dprd_member_id' => $archive->dprd_member_id],
            'Arsip '.$nomor.' dihapus (soft delete).'
        );

        return redirect()
            ->route('arsip.index')
            ->with('success', 'Arsip berhasil dihapus. Berkas fisik dan riwayat tetap tersimpan untuk audit.');
    }

    public function download(Request $request, Arsip $archive): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $archive);

        if (! $archive->file_path || ! Storage::disk('local')->exists($archive->file_path)) {
            return back()->with('error', 'Berkas arsip tidak ditemukan pada penyimpanan.');
        }

        return Storage::disk('local')->download(
            $archive->file_path,
            $archive->file_name ?: 'arsip-'.$archive->id,
            ['Content-Type' => Storage::disk('local')->mimeType($archive->file_path) ?: 'application/octet-stream']
        );
    }

    private function resolveMember(Request $request, mixed $memberId): ?DprdMember
    {
        $user = $request->user();

        if ($user->isStaff()) {
            if (! $user->dprd_member_id) {
                abort(403, 'Akun staf belum memiliki anggota DPRD yang ditugaskan.');
            }

            $member = DprdMember::query()->findOrFail($user->dprd_member_id);

            if ($memberId !== null && (int) $memberId !== (int) $member->id) {
                abort(403, 'Staf hanya dapat mengelola arsip anggota yang ditugaskan kepadanya.');
            }

            return $member;
        }

        if ($memberId === null || $memberId === '') {
            return null;
        }

        return DprdMember::query()->findOrFail($memberId);
    }

    /**
     * @return array{path: string, name: string, size: int}|null
     */
    private function storeUploadedFile(ArchiveRequest $request): ?array
    {
        $file = $request->file('file');

        if (! $file) {
            return null;
        }

        return ArchiveFileStorage::store($file);
    }

    private function allowedFilter(mixed $value, array $allowed): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    private function validDate(mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : '';
    }

    /**
     * @return Collection<int, KategoriArsip>
     */
    private function categories(): Collection
    {
        return KategoriArsip::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return Collection<int, DprdMember>
     */
    private function memberOptions(User $user): Collection
    {
        return DprdMember::query()
            ->visibleTo($user)
            ->orderBy('nama_lengkap')
            ->get();
    }
}
