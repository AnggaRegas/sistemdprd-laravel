<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\AktivitasSistem;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\DprdStaff;
use App\Models\StaffAssignmentHistory;
use App\Models\User;
use App\Services\StaffAssignmentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DprdMember::class);

        $user = $request->user();

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'status' => $this->allowedFilter($request->query('status'), ['active', 'inactive']),
            'partai' => Str::limit(trim((string) $request->query('partai')), 100, ''),
            'kecamatan' => Str::limit(trim((string) $request->query('kecamatan')), 100, ''),
            'staff' => $this->allowedFilter($request->query('staff'), ['assigned', 'unassigned']),
        ];

        $baseQuery = DprdMember::query()->visibleTo($user);

        $query = (clone $baseQuery)
            ->with(['staffReference.account', 'staff', 'region'])
            ->withCount(['arsips', 'laporan']);

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(kode) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(nama_lengkap) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(jabatan) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(partai) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(kecamatan) LIKE ?', [$needle])
                    ->orWhereHas('staff', function ($staffQuery) use ($needle) {
                        $staffQuery->whereRaw('LOWER(name) LIKE ?', [$needle]);
                    })
                    ->orWhereHas('staffReference', function ($staffQuery) use ($needle) {
                        $staffQuery->whereRaw('LOWER(nama) LIKE ?', [$needle]);
                    });
            });
        }

        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $query->where('is_active', false);
        }

        if ($filters['partai'] !== '') {
            $query->where('partai', $filters['partai']);
        }

        if ($filters['kecamatan'] !== '') {
            $query->where('kecamatan', $filters['kecamatan']);
        }

        if ($filters['staff'] === 'assigned') {
            $query->where(function ($query) {
                $query->whereNotNull('staff_id')->orWhereHas('staff');
            });
        } elseif ($filters['staff'] === 'unassigned') {
            $query->whereNull('staff_id')->whereDoesntHave('staff');
        }

        $filteredTotal = (clone $query)->count();

        $members = $query
            ->orderByRaw('CASE WHEN kode IS NULL THEN 1 ELSE 0 END')
            ->orderBy('kode')
            ->orderBy('nama_lengkap')
            ->paginate(10)
            ->withQueryString();

        $parties = (clone $baseQuery)
            ->whereNotNull('partai')
            ->where('partai', '<>', '')
            ->distinct()
            ->orderBy('partai')
            ->pluck('partai')
            ->all();

        $kecamatanOptions = (clone $baseQuery)
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '<>', '')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan')
            ->all();

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'staff' => (clone $baseQuery)->where(function ($query) {
                $query->whereNotNull('staff_id')->orWhereHas('staff');
            })->count(),
            'archives' => Arsip::query()->visibleTo($user)->count(),
        ];

        return view('members.index', [
            'members' => $members,
            'filters' => $filters,
            'parties' => $parties,
            'kecamatanOptions' => $kecamatanOptions,
            'summary' => $summary,
            'filteredTotal' => $filteredTotal,
            'hasFilters' => $filters['q'] !== ''
                || $filters['status'] !== ''
                || $filters['partai'] !== ''
                || $filters['kecamatan'] !== ''
                || $filters['staff'] !== '',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DprdMember::class);

        return view('members.create', [
            'member' => new DprdMember([
                'is_active' => true,
            ]),
            'parties' => $this->partyOptions($request->user()),
            'kecamatanOptions' => $this->kecamatanOptions($request->user()),
            'regions' => $this->regionOptions(),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function store(MemberRequest $request, StaffAssignmentService $staffAssignment): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $member = DB::transaction(function () use ($validated, $request, $staffAssignment) {
                $member = DprdMember::query()->create([
                    'kode' => $validated['kode'],
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'jabatan' => $validated['jabatan'],
                    'partai' => $validated['partai'],
                    'dapil' => $validated['dapil'] ?: null,
                    'kecamatan' => $validated['kecamatan'] ?: null,
                    'dprd_region_id' => $validated['dprd_region_id'] ?: null,
                    'komisi' => $validated['komisi'] ?: null,
                    'is_active' => (bool) $validated['is_active'],
                ]);

                if (! empty($validated['staff_id'])) {
                    $staff = DprdStaff::query()->active()->findOrFail($validated['staff_id']);

                    $staffAssignment->assignToMember(
                        $member,
                        $staff,
                        $request->user(),
                        'member_form',
                        'Staf ditugaskan saat anggota dibuat.'
                    );
                }

                return $member;
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateCode($exception);
        }

        AktivitasSistem::catat(
            'anggota.created',
            $request->user(),
            $member,
            ['kode' => $member->kode, 'is_active' => $member->is_active],
            'Anggota DPRD '.$member->nama_lengkap.' ('.$member->kode.') ditambahkan.'
        );

        return redirect()
            ->route('anggota.index')
            ->with('success', 'Anggota DPRD berhasil ditambahkan.');
    }

    public function edit(Request $request, DprdMember $member): View
    {
        $this->authorize('update', $member);

        $member->load(['staffReference.account', 'staff', 'region']);

        $assignmentHistories = $member->staff_id
            ? StaffAssignmentHistory::query()
                ->with(['assignedBy', 'member', 'previousMember'])
                ->where('dprd_staff_id', $member->staff_id)
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        return view('members.edit', [
            'member' => $member,
            'parties' => $this->partyOptions($request->user()),
            'kecamatanOptions' => $this->kecamatanOptions($request->user()),
            'regions' => $this->regionOptions(),
            'staffOptions' => $this->staffOptions($member),
            'assignmentHistories' => $assignmentHistories,
        ]);
    }

    public function update(MemberRequest $request, DprdMember $member, StaffAssignmentService $staffAssignment): RedirectResponse
    {
        $this->authorize('update', $member);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($member, $validated, $request, $staffAssignment) {
                $member->update([
                    'kode' => $validated['kode'],
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'jabatan' => $validated['jabatan'],
                    'partai' => $validated['partai'],
                    'dapil' => $validated['dapil'] ?: null,
                    'kecamatan' => $validated['kecamatan'] ?: null,
                    'dprd_region_id' => $validated['dprd_region_id'] ?: null,
                    'komisi' => $validated['komisi'] ?: null,
                    'is_active' => (bool) $validated['is_active'],
                ]);

                $staff = ! empty($validated['staff_id'])
                    ? DprdStaff::query()->active()->findOrFail($validated['staff_id'])
                    : null;

                $staffAssignment->assignToMember(
                    $member,
                    $staff,
                    $request->user(),
                    'member_form',
                    'Penugasan staf diperbarui dari form anggota.'
                );
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateCode($exception);
        }

        AktivitasSistem::catat(
            'anggota.updated',
            $request->user(),
            $member,
            ['kode' => $member->kode, 'is_active' => $member->is_active],
            'Data anggota DPRD '.$member->nama_lengkap.' diperbarui.'
        );

        return redirect()
            ->route('anggota.index')
            ->with('success', 'Data anggota DPRD berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, DprdMember $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $member->update([
            'is_active' => ! $member->is_active,
        ]);

        $message = $member->is_active
            ? 'Anggota '.$member->nama_lengkap.' berhasil diaktifkan.'
            : 'Anggota '.$member->nama_lengkap.' berhasil dinonaktifkan. Riwayat arsip dan laporan tetap tersimpan.';

        AktivitasSistem::catat(
            'anggota.status.changed',
            $request->user(),
            $member,
            ['is_active' => $member->is_active],
            $message
        );

        return back()->with('success', $message);
    }

    public function destroy(Request $request, DprdMember $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $archiveCount = $member->arsips()->withTrashed()->count();
        $reportCount = $member->laporan()->withTrashed()->count();
        $staffCount = $member->staffAccounts()->withTrashed()->count();

        if ($archiveCount > 0 || $reportCount > 0 || $staffCount > 0) {
            $details = [];

            if ($archiveCount > 0) {
                $details[] = $archiveCount.' arsip';
            }

            if ($reportCount > 0) {
                $details[] = $reportCount.' laporan masyarakat';
            }

            if ($staffCount > 0) {
                $details[] = $staffCount.' akun staf';
            }

            return back()->with(
                'error',
                'Anggota tidak dapat dihapus karena masih terkait dengan '.implode(', ', $details)
                .'. Nonaktifkan anggota melalui tombol status agar seluruh riwayat tetap tersimpan.'
            );
        }

        DB::transaction(function () use ($member) {
            $member->delete();
        });

        AktivitasSistem::catat(
            'anggota.deleted',
            $request->user(),
            $member,
            ['kode' => $member->kode],
            'Anggota DPRD '.$member->nama_lengkap.' dihapus (soft delete).'
        );

        return redirect()
            ->route('anggota.index')
            ->with('success', 'Anggota DPRD berhasil dihapus. Data riwayat tetap tersimpan.');
    }

    private function allowedFilter(mixed $value, array $allowed): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * @return array<int, string>
     */
    private function partyOptions(User $user): array
    {
        return DprdMember::query()
            ->visibleTo($user)
            ->whereNotNull('partai')
            ->where('partai', '<>', '')
            ->distinct()
            ->orderBy('partai')
            ->pluck('partai')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function kecamatanOptions(User $user): array
    {
        return DprdMember::query()
            ->visibleTo($user)
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '<>', '')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan')
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, DprdRegion>
     */
    private function regionOptions()
    {
        return DprdRegion::query()
            ->active()
            ->ordered()
            ->get(['id', 'name']);
    }

    private function throwDuplicateCode(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (
            str_contains($message, 'dprd_members_active_kode_unique')
            || str_contains($message, 'dprd_members_kode_unique')
        ) {
            throw ValidationException::withMessages([
                'kode' => 'Kode anggota sudah digunakan oleh anggota lain.',
            ]);
        }

        throw $exception;
    }
}
