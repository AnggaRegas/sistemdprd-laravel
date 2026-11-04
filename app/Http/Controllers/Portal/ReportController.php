<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStatusRequest;
use App\Http\Requests\StaffReportStatusRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use App\Support\PortalPresenter;
use App\Support\SafeReturnUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        /*
         * Superadmin melihat folder laporan per anggota DPRD. Folder diambil
         * dari data anggota aktif di database, bukan nama yang ditulis manual.
         */
        if ($user->isAdministrator()) {
            return $this->administratorIndex($request);
        }

        $categories = LaporanMasyarakat::query()
            ->visibleTo($user)
            ->whereNotNull('kategori')
            ->where('kategori', '<>', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->all();

        $categories = array_values(array_unique(array_merge(
            LaporanMasyarakat::KATEGORI_OPTIONS,
            $categories
        )));
        sort($categories);

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'status' => $this->statusFilter($request->query('status')),
            'kategori' => $this->allowedValue($request->query('kategori'), $categories),
            'kecamatan' => $this->allowedValue($request->query('kecamatan'), LaporanMasyarakat::KECAMATAN_OPTIONS),
            'tanggal_dari' => $this->dateFilter($request->query('tanggal_dari')),
            'tanggal_sampai' => $this->dateFilter($request->query('tanggal_sampai')),
        ];

        $query = LaporanMasyarakat::query()
            ->visibleTo($user)
            ->with(['member', 'assignee']);

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(nomor_tiket) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(pelapor_nama) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(ringkasan) LIKE ?', [$needle])
                    ->orWhereHas('member', function ($query) use ($needle) {
                        $query->whereRaw('LOWER(nama_lengkap) LIKE ?', [$needle]);
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['kategori'] !== '') {
            $query->where('kategori', $filters['kategori']);
        }

        if ($filters['kecamatan'] !== '') {
            $query->where('kecamatan', $filters['kecamatan']);
        }

        if ($filters['tanggal_dari'] !== '') {
            $query->whereDate('tanggal_laporan', '>=', $filters['tanggal_dari']);
        }

        if ($filters['tanggal_sampai'] !== '') {
            $query->whereDate('tanggal_laporan', '<=', $filters['tanggal_sampai']);
        }

        $statusCounts = LaporanMasyarakat::statusLabelCountsFor($user);
        $totalReports = array_sum($statusCounts);
        $filteredTotal = (clone $query)->count();

        $reports = $query
            ->latest('tanggal_laporan')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(static fn (LaporanMasyarakat $report) => PortalPresenter::report($report, duplicates: true));

        return view('reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'statuses' => LaporanMasyarakat::STATUS_LABELS,
            'categories' => $categories,
            'kecamatans' => LaporanMasyarakat::KECAMATAN_OPTIONS,
            'statusCounts' => $statusCounts,
            'totalReports' => $totalReports,
            'filteredTotal' => $filteredTotal,
            'returnUrl' => $request->fullUrl(),
            'hasFilters' => $filters['q'] !== ''
                || $filters['status'] !== ''
                || $filters['kategori'] !== ''
                || $filters['kecamatan'] !== ''
                || $filters['tanggal_dari'] !== ''
                || $filters['tanggal_sampai'] !== '',
        ]);
    }

    public function administratorIndex(Request $request): View
    {
        $selectedMemberId = (int) $request->query('anggota');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
        ];

        $membersQuery = DprdMember::query()
            ->where('is_active', true)
            ->withCount(['laporan' => fn ($query) => $query->withTrashed()])
            ->orderBy('nama_lengkap');

        if ($selectedMemberId === 0 && $filters['q'] !== '') {
            $membersQuery->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%'.Str::lower($filters['q']).'%']);
        }

        $members = $membersQuery->get();

        $selectedMember = null;
        $reports = null;
        $filteredTotal = 0;
        $duplicateIds = collect();
        $returnUrl = $request->fullUrl();

        if ($selectedMemberId > 0) {
            $selectedMember = DprdMember::query()
                ->where('is_active', true)
                ->whereKey($selectedMemberId)
                ->firstOrFail();

            $query = $selectedMember->laporan()
                ->withTrashed()
                ->with(['member']);

            if ($filters['q'] !== '') {
                $needle = '%'.Str::lower($filters['q']).'%';

                $query->where(function ($query) use ($needle) {
                    $query->whereRaw('LOWER(nomor_tiket) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(pelapor_nama) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(ringkasan) LIKE ?', [$needle]);
                });
            }

            $filteredTotal = (clone $query)->count();

            $reports = $query
                ->latest('tanggal_laporan')
                ->latest('id')
                ->paginate(10, ['*'], 'page')
                ->withQueryString();

            $duplicateIds = LaporanSimilarityResult::query()
                ->whereIn('laporan_masyarakat_id', $reports->getCollection()->pluck('id'))
                ->diAtasAmbang()
                ->pluck('laporan_masyarakat_id')
                ->unique();
        }

        return view('reports.admin_index', [
            'members' => $members,
            'selectedMember' => $selectedMember,
            'reports' => $reports,
            'filteredTotal' => $filteredTotal,
            'duplicateIds' => $duplicateIds,
            'searchQuery' => $filters['q'],
            'hasFolderQuery' => $selectedMemberId > 0 || $filters['q'] !== '',
            'returnUrl' => $returnUrl,
        ]);
    }

    public function show(Request $request, LaporanMasyarakat $laporan): View
    {
        $user = $request->user();

        $report = LaporanMasyarakat::query()
            ->visibleTo($user)
            ->with([
                'member',
                'assignee',
                'approvedBy',
                'penugasans' => fn ($query) => $query
                    ->with(['user', 'assignedBy'])
                    ->latest('assigned_at')
                    ->latest('id'),
            ])
            ->findOrFail($laporan->id);

        $isAdministrator = $user->isAdministrator();
        $autoStaff = null;
        $statusOptions = [];

        if ($isAdministrator) {
            /*
             * Disposisi bersifat otomatis: staf ditentukan dari relasi akun
             * aktif yang terhubung ke anggota DPRD tujuan, bukan dari pilihan
             * manual administrator.
             */
            if ($report->dprd_member_id) {
                $autoStaff = User::query()
                    ->where('role', User::ROLE_STAF_PENDAMPING)
                    ->where('is_active', true)
                    ->where('dprd_member_id', $report->dprd_member_id)
                    ->orderBy('id')
                    ->first(['id', 'name', 'dprd_member_id']);
            }

            foreach ($report->availableStatuses() as $status) {
                $statusOptions[$status] = LaporanMasyarakat::STATUS_LABELS[$status] ?? $status;
            }
        }

        if ($user->isStaff()) {
            foreach ([
                LaporanMasyarakat::STATUS_DITUGASKAN,
                LaporanMasyarakat::STATUS_DIPROSES,
                LaporanMasyarakat::STATUS_SELESAI,
            ] as $status) {
                if ($report->canTransitionTo($status)) {
                    $statusOptions[$status] = LaporanMasyarakat::STATUS_LABELS[$status] ?? $status;
                }
            }
        }

        $similarities = $isAdministrator
            ? LaporanSimilarityResult::query()
                ->where('laporan_masyarakat_id', $report->id)
                ->diAtasAmbang()
                ->with([
                    'comparedLaporan',
                    'comparedLaporan.member',
                ])
                ->get()
            : collect();

        return view('reports.show', [
            'report' => $report,
            'isAdministrator' => $isAdministrator,
            'autoStaff' => $autoStaff,
            'statusOptions' => $statusOptions,
            'similarities' => $similarities,
            'isStaff' => $user->isStaff(),
            'canStaffUpdateStatus' => $user->isStaff() && $statusOptions !== [],
            'returnUrl' => SafeReturnUrl::reportsIndex($request->query('return_to'), $request),
        ]);
    }

    /**
     * Persetujuan administrator sekaligus disposisi otomatis kepada staf
     * pendamping aktif dari anggota DPRD yang dipilih masyarakat.
     */
    public function approve(Request $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $administrator = $request->user();
        abort_unless($administrator?->isAdministrator(), 403);

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($laporan, $administrator, $validated): array {
            $report = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            if (in_array($report->status, [
                LaporanMasyarakat::STATUS_SELESAI,
                LaporanMasyarakat::STATUS_DITOLAK,
            ], true)) {
                throw ValidationException::withMessages([
                    'approval' => 'Laporan yang sudah ditutup tidak dapat disetujui lagi.',
                ])->errorBag('approval');
            }

            if (! $report->dprd_member_id) {
                throw ValidationException::withMessages([
                    'approval' => 'Laporan belum memiliki anggota DPRD tujuan sehingga tidak dapat didisposisikan.',
                ])->errorBag('approval');
            }

            /*
             * Staf diambil dari relasi akun aktif yang tersimpan, bukan dari
             * input administrator. Bila tidak ada, laporan tetap di antrean
             * dan tidak dipaksakan ke staf lain.
             */
            $staff = User::query()
                ->where('role', User::ROLE_STAF_PENDAMPING)
                ->where('is_active', true)
                ->where('dprd_member_id', $report->dprd_member_id)
                ->orderBy('id')
                ->first();

            if (! $staff) {
                return ['report' => $report, 'staff' => null];
            }

            if ((int) $report->assigned_to !== (int) $staff->id) {
                LaporanPenugasan::query()
                    ->where('laporan_masyarakat_id', $report->id)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now(),
                        'updated_at' => now(),
                    ]);

                LaporanPenugasan::query()->create([
                    'laporan_masyarakat_id' => $report->id,
                    'user_id' => $staff->id,
                    'assigned_by' => $administrator->id,
                    'assigned_at' => now(),
                    'released_at' => null,
                    'catatan' => $validated['catatan'] ?? 'Disposisi otomatis setelah persetujuan.',
                ]);
            }

            $previousStatus = $report->status;

            $report->assigned_to = $staff->id;
            $report->assigned_at = $report->assigned_at ?: now();
            $report->disetujui_oleh = $administrator->id;
            $report->disetujui_at = now();
            $report->status = LaporanMasyarakat::STATUS_DITUGASKAN;
            $report->ditutup_at = null;

            if (! empty($validated['catatan'])) {
                $report->catatan_penyelesaian = $validated['catatan'];
            }

            $report->save();

            AktivitasSistem::catat(
                'laporan.disetujui',
                $administrator,
                $report,
                [
                    'nomor_tiket' => $report->nomor_tiket,
                    'dprd_member_id' => $report->dprd_member_id,
                    'assigned_to' => $staff->id,
                    'previous_status' => $previousStatus,
                ],
                'Laporan '.$report->nomor_tiket.' disetujui dan didisposisikan kepada staf pendamping.'
            );

            return ['report' => $report, 'staff' => $staff];
        });

        if (! $result['staff']) {
            return redirect()
                ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
                ->with('warning', 'Anggota DPRD tujuan belum memiliki staf pendamping aktif. Laporan belum didisposisikan.');
        }

        return redirect()
            ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
            ->with('success', 'Laporan disetujui dan didisposisikan kepada '.$result['staff']->name.'.');
    }

    public function downloadAttachment(Request $request, LaporanMasyarakat $laporan): StreamedResponse
    {
        return $this->attachmentResponse($request, $laporan, inline: false);
    }

    /**
     * Tampilkan lampiran privat di tab baru tanpa mengunduhnya. Berkas
     * dikirim inline dengan header keamanan yang sama seperti unduhan.
     */
    public function viewAttachment(Request $request, LaporanMasyarakat $laporan): StreamedResponse
    {
        return $this->attachmentResponse($request, $laporan, inline: true);
    }

    private function attachmentResponse(Request $request, LaporanMasyarakat $laporan, bool $inline): StreamedResponse
    {
        $report = LaporanMasyarakat::query()
            ->visibleTo($request->user())
            ->findOrFail($laporan->id);

        abort_unless($report->hasLampiran(), 404, 'Lampiran tidak tersedia.');

        $path = (string) $report->lampiran_path;

        /*
         * Lampiran hanya boleh dibaca dari folder yang dibuat aplikasi.
         * Pemeriksaan ini mencegah path aneh jika data database dimanipulasi.
         */
        abort_unless(
            Str::startsWith($path, 'laporan-masyarakat/')
                && ! Str::contains($path, ['..', '\\']),
            404,
            'Lampiran tidak tersedia.'
        );

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'Berkas lampiran tidak ditemukan.');

        $name = $this->safeDownloadName($report->lampiran_nama);

        if ($inline) {
            return $disk->response($path, $name, [
                'Content-Type' => $report->lampiran_mime ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $disk->download($path, $name, [
            'Content-Type' => $report->lampiran_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Staf pendamping hanya dapat memperbarui status tindak lanjut laporan
     * yang didisposisikan kepadanya. Persetujuan tetap milik administrator.
     */
    public function updateStatusForStaff(StaffReportStatusRequest $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $validated = $request->validated();
        $staff = $request->user();

        DB::transaction(function () use ($validated, $staff, $laporan): void {
            $report = LaporanMasyarakat::query()
                ->visibleTo($staff)
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            $newStatus = $validated['status'];

            if (! in_array($newStatus, [
                LaporanMasyarakat::STATUS_DIPROSES,
                LaporanMasyarakat::STATUS_SELESAI,
            ], true) || ! $report->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => 'Status tindak lanjut yang dipilih tidak diizinkan untuk staf pendamping.',
                ])->errorBag('status');
            }

            $previousStatus = $report->status;
            $report->status = $newStatus;

            if ($newStatus === LaporanMasyarakat::STATUS_SELESAI) {
                $report->ditutup_at = now();
            }

            if (! empty($validated['catatan'])) {
                $report->catatan_penyelesaian = $validated['catatan'];
            }

            $report->save();

            AktivitasSistem::catat(
                'laporan.tindak_lanjut',
                $staff,
                $report,
                [
                    'nomor_tiket' => $report->nomor_tiket,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                ],
                'Tindak lanjut laporan '.$report->nomor_tiket.' diperbarui oleh staf pendamping.'
            );
        });

        return redirect()
            ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
            ->with('success', 'Tindak lanjut laporan berhasil diperbarui.');
    }

    public function updateStatus(ReportStatusRequest $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $validated = $request->validated();
        $administrator = $request->user();

        DB::transaction(function () use ($validated, $administrator, $laporan): void {
            $report = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            $newStatus = $validated['status'];

            if (! $report->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => 'Perubahan status dari '.$report->status_label.' ke '
                        .(LaporanMasyarakat::STATUS_LABELS[$newStatus] ?? $newStatus).' tidak diizinkan.',
                ])->errorBag('status');
            }

            $previousStatus = $report->status;
            $report->status = $newStatus;

            if (in_array($newStatus, [
                LaporanMasyarakat::STATUS_SELESAI,
                LaporanMasyarakat::STATUS_DITOLAK,
            ], true)) {
                $report->ditutup_at = $report->ditutup_at ?: now();
            } else {
                $report->ditutup_at = null;
            }

            if (! empty($validated['catatan'])) {
                $report->catatan_penyelesaian = $validated['catatan'];
            }

            /*
             * Mengembalikan laporan ke antrean penugasan berarti staf yang
             * sebelumnya ditugaskan dilepas dan persetujuan dibatalkan.
             * Riwayat penugasan tetap utuh.
             */
            if ($newStatus === LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN) {
                $report->assigned_to = null;
                $report->assigned_at = null;
                $report->disetujui_oleh = null;
                $report->disetujui_at = null;
            }

            if ($newStatus === LaporanMasyarakat::STATUS_DITOLAK) {
                $report->disetujui_oleh = null;
                $report->disetujui_at = null;
            }

            $report->save();

            if (in_array($newStatus, [
                LaporanMasyarakat::STATUS_SELESAI,
                LaporanMasyarakat::STATUS_DITOLAK,
                LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
            ], true)) {
                LaporanPenugasan::query()
                    ->where('laporan_masyarakat_id', $report->id)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            AktivitasSistem::catat(
                'laporan.status',
                $administrator,
                $report,
                [
                    'nomor_tiket' => $report->nomor_tiket,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                ],
                'Status laporan '.$report->nomor_tiket.' diubah menjadi '.$report->status_label.'.'
            );
        });

        return redirect()
            ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
            ->with('success', 'Status laporan berhasil diperbarui.');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedValue(mixed $value, array $allowed): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function statusFilter(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (array_key_exists($value, LaporanMasyarakat::STATUS_LABELS)) {
            return $value;
        }

        return PortalPresenter::statusValue($value, LaporanMasyarakat::STATUS_LABELS) ?? '';
    }

    private function dateFilter(mixed $value): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function safeDownloadName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) ($name ?: 'lampiran')));
        $name = preg_replace('/[^\pL\pN ._\-()]+/u', '_', $name) ?: 'lampiran';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'lampiran', 0, 255);
    }
}
