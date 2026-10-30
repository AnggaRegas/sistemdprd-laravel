<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportAssignmentRequest;
use App\Http\Requests\ReportCommissionRequest;
use App\Http\Requests\ReportStatusRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdCommission;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
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
            ->with(['member', 'assignee', 'klasifikasiKomisi', 'komisiFinal']);

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
            ->through(static fn (LaporanMasyarakat $report) => PortalPresenter::report($report));

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

    public function show(Request $request, LaporanMasyarakat $laporan): View
    {
        $user = $request->user();

        $report = LaporanMasyarakat::query()
            ->visibleTo($user)
            ->with([
                'member',
                'assignee',
                'klasifikasiKomisi',
                'komisiFinal',
                'komisiFinalOleh',
                'penugasans' => fn ($query) => $query
                    ->with(['user', 'assignedBy'])
                    ->latest('assigned_at')
                    ->latest('id'),
            ])
            ->findOrFail($laporan->id);

        $isAdministrator = $user->isAdministrator();
        $members = collect();
        $staffOptions = collect();
        $statusOptions = [];

        if ($isAdministrator) {
            $members = DprdMember::query()
                ->where('is_active', true)
                ->orderBy('nama_lengkap')
                ->get(['id', 'nama_lengkap', 'komisi']);

            $staffOptions = User::query()
                ->where('role', User::ROLE_STAF_PENDAMPING)
                ->where('is_active', true)
                ->whereNotNull('dprd_member_id')
                ->orderBy('name')
                ->get(['id', 'name', 'dprd_member_id'])
                ->map(static fn (User $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'member_id' => $staff->dprd_member_id,
                ])
                ->values();

            foreach ($report->availableStatuses() as $status) {
                $statusOptions[$status] = LaporanMasyarakat::STATUS_LABELS[$status] ?? $status;
            }
        }

        return view('reports.show', [
            'report' => $report,
            'isAdministrator' => $isAdministrator,
            'members' => $members,
            'staffOptions' => $staffOptions,
            'statusOptions' => $statusOptions,
            'commissions' => $isAdministrator
                ? DprdCommission::query()->active()->orderBy('id')->get(['id', 'name', 'field'])
                : collect(),
            'returnUrl' => SafeReturnUrl::reportsIndex($request->query('return_to'), $request),
        ]);
    }

    public function downloadAttachment(Request $request, LaporanMasyarakat $laporan): StreamedResponse
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

        return $disk->download($path, $this->safeDownloadName($report->lampiran_nama), [
            'Content-Type' => $report->lampiran_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updateAssignment(ReportAssignmentRequest $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $validated = $request->validated();
        $administrator = $request->user();

        DB::transaction(function () use ($validated, $administrator, $laporan): void {
            $report = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            $memberId = $validated['dprd_member_id'] ?? null;
            $staffId = $validated['assigned_to'] ?? null;
            $note = $validated['catatan'] ?? null;

            if ($staffId) {
                $staff = User::query()
                    ->whereKey($staffId)
                    ->where('role', User::ROLE_STAF_PENDAMPING)
                    ->where('is_active', true)
                    ->first();

                if (! $staff || (int) $staff->dprd_member_id !== (int) $memberId) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'Staf yang dipilih bukan pendamping aktif dari anggota DPRD tersebut.',
                    ])->errorBag('assignment');
                }
            }

            $previousStaffId = $report->assigned_to;
            $previousMemberId = $report->dprd_member_id;
            $staffChanged = (int) ($previousStaffId ?? 0) !== (int) ($staffId ?? 0);

            if ($staffChanged) {
                LaporanPenugasan::query()
                    ->where('laporan_masyarakat_id', $report->id)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            if ($staffId && $staffChanged) {
                LaporanPenugasan::query()->create([
                    'laporan_masyarakat_id' => $report->id,
                    'user_id' => $staffId,
                    'assigned_by' => $administrator->id,
                    'assigned_at' => now(),
                    'released_at' => null,
                    'catatan' => $note,
                ]);
            }

            $report->dprd_member_id = $memberId;
            $report->assigned_to = $staffId;
            $report->assigned_at = $staffId
                ? ($staffChanged || ! $report->assigned_at ? now() : $report->assigned_at)
                : null;

            if ($staffId) {
                if (in_array($report->status, [
                    LaporanMasyarakat::STATUS_BARU,
                    LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
                ], true)) {
                    $report->status = LaporanMasyarakat::STATUS_DITUGASKAN;
                }
            } elseif ($report->status === LaporanMasyarakat::STATUS_DITUGASKAN) {
                $report->status = LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN;
            }

            $report->save();

            AktivitasSistem::catat(
                'laporan.penugasan',
                $administrator,
                $report,
                [
                    'nomor_tiket' => $report->nomor_tiket,
                    'dprd_member_id' => $memberId,
                    'assigned_to' => $staffId,
                    'previous_dprd_member_id' => $previousMemberId,
                    'previous_assigned_to' => $previousStaffId,
                ],
                'Penugasan laporan '.$report->nomor_tiket.' diperbarui.'
            );
        });

        return redirect()
            ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
            ->with('success', 'Penugasan laporan berhasil diperbarui.');
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
             * sebelumnya ditugaskan dilepas. Riwayat penugasan tetap utuh.
             */
            if ($newStatus === LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN) {
                $report->assigned_to = null;
                $report->assigned_at = null;
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
     * Simpan keputusan final komisi Administrator. Hasil klasifikasi otomatis
     * (kolom klasifikasi_*) tidak ditimpa agar tetap dapat diaudit.
     */
    public function updateCommission(ReportCommissionRequest $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $validated = $request->validated();
        $administrator = $request->user();

        DB::transaction(function () use ($validated, $administrator, $laporan): void {
            $report = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            $previousCommissionId = $report->komisi_final_id;

            $report->komisi_final_id = (int) $validated['komisi_final_id'];
            $report->komisi_final_ditetapkan_oleh = $administrator->id;
            $report->komisi_final_ditetapkan_at = now();
            $report->save();

            AktivitasSistem::catat(
                'laporan.komisi',
                $administrator,
                $report,
                [
                    'nomor_tiket' => $report->nomor_tiket,
                    'komisi_final_id' => $report->komisi_final_id,
                    'previous_komisi_final_id' => $previousCommissionId,
                    'klasifikasi_komisi_id' => $report->klasifikasi_komisi_id,
                    'catatan' => $validated['catatan'] ?? null,
                ],
                'Komisi final laporan '.$report->nomor_tiket.' ditetapkan.'
            );
        });

        return redirect()
            ->to(SafeReturnUrl::reportsIndex($request->input('return_to'), $request))
            ->with('success', 'Komisi final laporan berhasil disimpan.');
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
