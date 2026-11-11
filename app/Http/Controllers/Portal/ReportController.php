<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStatusRequest;
use App\Http\Requests\StaffReportStatusRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanPenugasan;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Models\KamusAcuanVersi;
use App\Models\LaporanKamusHasil;
use App\Models\LaporanKamusVerifikasi;
use App\Models\User;
use App\Support\AdminNotification;
use App\Support\PortalPresenter;
use App\Support\SafeReturnUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        $user = $request->user();
        $rawAnggota = $request->query('anggota');
        $selectedMemberParam = is_string($rawAnggota) || is_numeric($rawAnggota) ? trim((string) $rawAnggota) : '';
        $selectedMemberId = ctype_digit($selectedMemberParam) ? (int) $selectedMemberParam : 0;
        $showAllMembers = $selectedMemberParam === 'semua' || $selectedMemberParam === 'all';

        $rawKemiripan = $request->query('kemiripan');
        $kemiripanFilter = in_array($rawKemiripan, ['memiliki_kemiripan', 'tidak_memiliki_kemiripan'], true)
            ? (string) $rawKemiripan
            : '';

        $rawStatusKamus = $request->query('status_kamus') ?? $request->query('kamus_status');
        $statusKamusFilter = in_array($rawStatusKamus, ['sesuai', 'tidak_ditemukan'], true)
            ? (string) $rawStatusKamus
            : ($this->kamusStatusFilter($rawStatusKamus));

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'anggota' => $selectedMemberParam,
            'kemiripan' => $kemiripanFilter,
            'status_kamus' => $statusKamusFilter,
            'kamus_status' => $statusKamusFilter,
            'kamus_tahun' => $this->kamusTahunFilter($request->query('kamus_tahun')),
        ];

        $memberUnreadCounts = LaporanMasyarakat::unreadNewCountsByMemberFor($user);
        $unreadReportLookup = [];

        $shouldShowTable = $selectedMemberId > 0
            || $showAllMembers
            || $kemiripanFilter !== ''
            || $statusKamusFilter !== ''
            || $request->has('page');

        $membersQuery = DprdMember::query()
            ->where('is_active', true)
            ->withCount('laporan')
            ->orderBy('nama_lengkap');

        if (! $shouldShowTable && $filters['q'] !== '') {
            $membersQuery->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%'.Str::lower($filters['q']).'%']);
        }

        $members = $membersQuery->get();

        $selectedMember = null;
        $reports = null;
        $filteredTotal = 0;
        $returnUrl = $request->fullUrl();

        if ($shouldShowTable) {
            $query = LaporanMasyarakat::query()
                ->with(['member', 'kamusEntri']);

            if ($selectedMemberId > 0) {
                $selectedMember = DprdMember::query()
                    ->where('is_active', true)
                    ->whereKey($selectedMemberId)
                    ->firstOrFail();

                $query->where('dprd_member_id', $selectedMemberId);
            }

            if ($kemiripanFilter === 'memiliki_kemiripan') {
                $query->where(function ($q) {
                    $q->whereNotNull('similarity_group_id')
                        ->orWhereNotNull('duplicate_of_id')
                        ->orWhere('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI)
                        ->orWhereHas('similarityResults', function ($sub) {
                            $sub->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
                        })
                        ->orWhereHas('comparedSimilarityResults', function ($sub) {
                            $sub->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
                        });
                });
            } elseif ($kemiripanFilter === 'tidak_memiliki_kemiripan') {
                $query->whereNull('similarity_group_id')
                    ->whereNull('duplicate_of_id')
                    ->where(function ($q) {
                        $q->whereNull('similarity_level')
                            ->orWhere('similarity_level', '<>', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
                    })
                    ->whereDoesntHave('similarityResults', function ($sub) {
                        $sub->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
                    })
                    ->whereDoesntHave('comparedSimilarityResults', function ($sub) {
                        $sub->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
                    });
            }

            if ($statusKamusFilter === 'sesuai') {
                $query->where('kamus_status', LaporanKamusHasil::STATUS_SESUAI);
            } elseif ($statusKamusFilter === 'tidak_ditemukan') {
                $query->where(function ($q) {
                    $q->whereNull('kamus_status')
                        ->orWhere('kamus_status', '<>', LaporanKamusHasil::STATUS_SESUAI);
                });
            } elseif ($statusKamusFilter !== '') {
                if ($statusKamusFilter === LaporanKamusHasil::STATUS_BELUM_DIPERIKSA) {
                    $query->where(function ($q) {
                        $q->whereNull('kamus_status')
                            ->orWhere('kamus_status', LaporanKamusHasil::STATUS_BELUM_DIPERIKSA);
                    });
                } else {
                    $query->where('kamus_status', $statusKamusFilter);
                }
            }

            if ($filters['kamus_tahun'] !== '') {
                $query->where('kamus_tahun', (int) $filters['kamus_tahun']);
            }

            if ($filters['q'] !== '') {
                $needle = '%'.Str::lower($filters['q']).'%';

                $query->where(function ($q) use ($needle) {
                    $q->whereRaw('LOWER(nomor_tiket) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(pelapor_nama) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(ringkasan) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(isi) LIKE ?', [$needle])
                        ->orWhereHas('member', function ($mq) use ($needle) {
                            $mq->whereRaw('LOWER(nama_lengkap) LIKE ?', [$needle]);
                        });
                });
            }

            $filteredTotal = (clone $query)->count();

            $reports = $query
                ->latest('tanggal_laporan')
                ->latest('id')
                ->paginate(10, ['*'], 'page')
                ->withQueryString();

            $reports->getCollection()->load([
                'member:id,nama_lengkap,jabatan',
                'kamusEntri:id,kode,klasifikasi',
                'similarityGroup:id,kode,status,main_laporan_id',
                'similarityGroup.members:id,laporan_similarity_group_id,laporan_masyarakat_id,peran',
                'duplicateOf:id,nomor_tiket,ringkasan',
                'similarityResults.comparedLaporan',
                'comparedSimilarityResults.laporan',
            ]);

            $similarityMatches = $this->similarityMatchesFor($reports->getCollection());
            $similarityPanels = $reports->getCollection()
                ->mapWithKeys(fn (LaporanMasyarakat $report) => [
                    $report->id => $this->similarityPanelFor($report, $similarityMatches->get($report->id, [])),
                ]);

            $unreadReportIds = LaporanMasyarakat::unreadReportIdsFor(
                $user,
                $reports->getCollection()->pluck('id')->all()
            );
            $unreadReportLookup = array_flip($unreadReportIds);
        }

        $hasActiveFilters = $selectedMemberParam !== ''
            || $kemiripanFilter !== ''
            || $statusKamusFilter !== ''
            || $filters['kamus_tahun'] !== ''
            || $filters['q'] !== '';

        return view('reports.admin_index', [
            'members' => $members,
            'memberUnreadCounts' => $memberUnreadCounts,
            'unreadReportLookup' => $unreadReportLookup,
            'selectedMember' => $selectedMember,
            'selectedMemberParam' => $selectedMemberParam,
            'reports' => $reports,
            'filteredTotal' => $filteredTotal,
            'similarityMatches' => $similarityMatches ?? collect(),
            'similarityPanels' => $similarityPanels ?? collect(),
            'searchQuery' => $filters['q'],
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
            'kamusStatuses' => LaporanKamusHasil::STATUS_LABELS,
            'kamusTahunOptions' => $this->kamusTahunOptions(),
            'hasKamusFilter' => $statusKamusFilter !== ''
                || $filters['kamus_tahun'] !== '',
            'hasFolderQuery' => $hasActiveFilters,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function show(Request $request, LaporanMasyarakat $laporan): View
    {
        $user = $request->user();

        if ($user) {
            LaporanMasyarakat::markAsReadFor($user, $laporan);

            if ($user->isStaff() && (int) $laporan->assigned_to === (int) $user->id) {
                $assignedStamp = $laporan->assigned_at?->getTimestamp() ?? $laporan->id;
                AdminNotification::markRead($user, 'laporan-ditugaskan-'.$laporan->id.'-'.$assignedStamp);
            }
        }

        $report = LaporanMasyarakat::query()
            ->visibleTo($user)
            ->with([
                'member',
                'assignee',
                'approvedBy',
                'similarityGroup.members.laporan',
                'similarityGroup.decisions.user',
                'duplicateOf',
                'duplicates',
                'similarityResults.comparedLaporan.similarityGroup',
                'comparedSimilarityResults.laporan.similarityGroup',
                'similarityReviewedBy',
                'kamusHasil.versi',
                'kamusHasil.entriUtama',
                'kamusHasil.pemeriksa',
                'kamusEntri',
                'kamusVersi',
                'kamusDiverifikasiOleh',
                'kamusHasilList' => fn ($query) => $query
                    ->with(['versi', 'entriUtama', 'pemeriksa'])
                    ->latest('id')
                    ->limit(10),
                'kamusVerifikasi' => fn ($query) => $query
                    ->with(['verifikator', 'entri', 'hasil'])
                    ->latest('id')
                    ->limit(20),
                'penugasans' => fn ($query) => $query
                    ->with(['user', 'assignedBy'])
                    ->latest('assigned_at')
                    ->latest('id'),
            ])
            ->findOrFail($laporan->id);

        $isAdministrator = $user->isAdministrator();
        $autoStaff = null;
        $statusOptions = [];
        $kamusVersion = KamusAcuanVersi::aktif();
        $kamusEntries = $kamusVersion
            ? $kamusVersion->entri()->active()->orderBy('kode')->get(['id', 'kode', 'klasifikasi', 'kamus_acuan_versi_id'])
            : collect();

        /*
         * Keputusan manual lama bisa menunjuk entri dari versi sebelumnya.
         * Sertakan entri tersebut agar pilihan yang tersimpan tidak hilang
         * ketika versi aktif berganti.
         */
        if ($report->kamusEntri && ! $kamusEntries->contains('id', $report->kamusEntri->id)) {
            $kamusEntries->push($report->kamusEntri);
        }

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

        return view('reports.show', [
            'report' => $report,
            'isAdministrator' => $isAdministrator,
            'autoStaff' => $autoStaff,
            'statusOptions' => $statusOptions,
            'isStaff' => $user->isStaff(),
            'canStaffUpdateStatus' => $user->isStaff() && $statusOptions !== [],
            'kamusVersion' => $kamusVersion,
            'kamusEntries' => $kamusEntries,
            'kamusStatusLabels' => LaporanKamusHasil::STATUS_LABELS,
            'kamusVerificationLabels' => LaporanKamusVerifikasi::KEPUTUSAN_LABELS,
            'similarityMatches' => $this->similarityMatchesFor(collect([$report]))->get($report->id, []),
            'returnUrl' => SafeReturnUrl::reportsIndex($request->query('return_to'), $request),
        ]);
    }

    /**
     * Hapus satu aspirasi (soft delete).
     *
     * Penghapusan menjaga relasi tetap konsisten:
     * - keanggotaan kelompok kemiripan dibersihkan;
     * - bila aspirasi utama dihapus, anggota lain dipromosikan sebagai
     *   aspirasi utama pengganti;
     * - bila anggota kelompok tersisa kurang dari dua, kelompok dibubarkan;
     * - hasil perhitungan kemiripan yang melibatkan aspirasi ini dihapus.
     *
     * Data pelapor, isi aspirasi, dan lampiran tetap tersimpan pada baris
     * soft delete sehingga masih dapat ditelusuri melalui aktivitas sistem.
     */
    public function destroy(Request $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isAdministrator(), 403);

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $returnUrl = SafeReturnUrl::reportsIndex($validated['return_to'] ?? null, $request);
        $ticket = $laporan->nomor_tiket;
        $groupKode = null;

        try {
            DB::transaction(function () use ($laporan, $user, $validated, &$groupKode): void {
                $report = LaporanMasyarakat::query()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($laporan->id);

                if ($report->trashed()) {
                    throw ValidationException::withMessages([
                        'laporan' => 'Aspirasi ini sudah dihapus sebelumnya.',
                    ]);
                }

                $group = $report->similarity_group_id
                    ? LaporanSimilarityGroup::query()
                        ->lockForUpdate()
                        ->find($report->similarity_group_id)
                    : null;

                $replacementId = null;

                if ($group) {
                    $groupKode = $group->kode;

                    $activeMemberIds = LaporanSimilarityGroupMember::query()
                        ->where('laporan_similarity_group_id', $group->id)
                        ->where('laporan_masyarakat_id', '<>', $report->id)
                        ->orderBy('id')
                        ->pluck('laporan_masyarakat_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $activeMemberIds = LaporanMasyarakat::query()
                        ->whereIn('id', $activeMemberIds)
                        ->orderBy('id')
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $isMain = (int) $group->main_laporan_id === (int) $report->id
                        || $report->similarity_status === LaporanMasyarakat::SIMILARITY_STATUS_UTAMA;

                    LaporanSimilarityGroupMember::query()
                        ->where('laporan_masyarakat_id', $report->id)
                        ->delete();

                    if ($activeMemberIds === []) {
                        $this->dissolveSimilarityGroup($group, $user);
                    } elseif ($isMain) {
                        $replacementId = $activeMemberIds[0];

                        $group->forceFill([
                            'main_laporan_id' => $replacementId,
                            'decided_by' => $user->id,
                            'decided_at' => now(),
                            'catatan' => $this->appendGroupNote(
                                $group->catatan,
                                'Aspirasi utama '.$report->nomor_tiket.' dihapus; '
                                    .'aspirasi #'.$replacementId.' dipromosikan sebagai aspirasi utama pengganti.'
                            ),
                        ])->save();

                        LaporanSimilarityGroupMember::query()
                            ->where('laporan_similarity_group_id', $group->id)
                            ->where('laporan_masyarakat_id', $replacementId)
                            ->update([
                                'peran' => LaporanSimilarityGroupMember::PERAN_UTAMA,
                                'decided_by' => $user->id,
                                'decided_at' => now(),
                            ]);

                        LaporanMasyarakat::withTrashed()
                            ->whereKey($replacementId)
                            ->update([
                                'similarity_status' => LaporanMasyarakat::SIMILARITY_STATUS_UTAMA,
                                'similarity_reviewed_by' => $user->id,
                                'similarity_reviewed_at' => now(),
                            ]);
                    } elseif (count($activeMemberIds) < 2) {
                        $this->dissolveSimilarityGroup($group, $user);
                    }
                }

                LaporanSimilarityResult::query()
                    ->where(function ($query) use ($report) {
                        $query->where('laporan_masyarakat_id', $report->id)
                            ->orWhere('compared_laporan_masyarakat_id', $report->id);
                    })
                    ->delete();

                $duplicates = LaporanMasyarakat::query()
                    ->withTrashed()
                    ->where('duplicate_of_id', $report->id);

                if ($replacementId !== null) {
                    $duplicates->update(['duplicate_of_id' => $replacementId]);
                } else {
                    $duplicates->update([
                        'duplicate_of_id' => null,
                        'similarity_status' => null,
                        'similarity_level' => null,
                        'similarity_reviewed_by' => null,
                        'similarity_reviewed_at' => null,
                    ]);
                }

                $report->forceFill([
                    'similarity_group_id' => null,
                    'duplicate_of_id' => null,
                ])->save();

                AktivitasSistem::catat(
                    'laporan.dihapus',
                    $user,
                    $report,
                    [
                        'nomor_tiket' => $report->nomor_tiket,
                        'pelapor_nama' => $report->pelapor_nama,
                        'dprd_member_id' => $report->dprd_member_id,
                        'similarity_group' => $groupKode,
                        'aspirasi_utama_pengganti' => $replacementId,
                        'alasan' => $validated['alasan'] ?? null,
                    ],
                    'Aspirasi '.$report->nomor_tiket.' dihapus oleh '.$user->name.'.'
                );

                $report->delete();
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->to($returnUrl)
                ->with('error', 'Aspirasi '.$ticket.' gagal dihapus. Silakan coba lagi.');
        }

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Aspirasi '.$ticket.' berhasil dihapus.');
    }

    /**
     * Bubarkan kelompok kemiripan karena anggota aktifnya kurang dari dua.
     */
    private function dissolveSimilarityGroup(LaporanSimilarityGroup $group, ?User $user = null): void
    {
        LaporanSimilarityGroupMember::query()
            ->where('laporan_similarity_group_id', $group->id)
            ->delete();

        LaporanMasyarakat::withTrashed()
            ->where('similarity_group_id', $group->id)
            ->update([
                'similarity_group_id' => null,
                'duplicate_of_id' => null,
                'similarity_status' => null,
                'similarity_level' => null,
                'similarity_reviewed_by' => null,
                'similarity_reviewed_at' => null,
            ]);

        $group->forceFill([
            'status' => LaporanSimilarityGroup::STATUS_DIBUBARKAN,
            'main_laporan_id' => null,
            'decided_by' => $user?->id,
            'decided_at' => now(),
            'catatan' => $this->appendGroupNote(
                $group->catatan,
                'Kelompok dibubarkan otomatis karena anggota aktif tersisa kurang dari dua.'
            ),
        ])->save();
    }

    private function appendGroupNote(?string $existing, string $note): string
    {
        $existing = trim((string) $existing);

        return $existing === '' ? $note : $existing."\n".$note;
    }

    /**
     * Hitungan aspirasi baru yang belum dibaca per anggota dan total untuk Super Admin.
     * Digunakan oleh polling pembaruan otomatis di latar belakang.
     */
    public function unreadCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isAdministrator(), 403);

        return response()->json([
            'total' => LaporanMasyarakat::badgeCountFor($user),
            'members' => LaporanMasyarakat::unreadNewCountsByMemberFor($user)->mapWithKeys(
                fn ($count, $memberId) => [(string) $memberId => (int) $count]
            )->all(),
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

            if ($report->is_duplicate_merged) {
                throw ValidationException::withMessages([
                    'approval' => 'Laporan ini sudah digabungkan ke aspirasi utama dan tidak perlu ditugaskan kembali. '
                        .'Proses aspirasi utama melalui laporan utama.',
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
            LaporanMasyarakat::markAsReadFor($administrator, $report);

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

            /*
             * Saat staf memindahkan status laporan ke Diproses, penugasan aktif
             * miliknya ikut tercatat sedang ditindaklanjuti. Status penugasan
             * tetap terpisah dan tidak memengaruhi status aspirasi.
             */
            if ($newStatus === LaporanMasyarakat::STATUS_DIPROSES) {
                LaporanPenugasan::query()
                    ->where('laporan_masyarakat_id', $report->id)
                    ->where('user_id', $staff->id)
                    ->whereNull('released_at')
                    ->where('status', LaporanPenugasan::STATUS_DITUGASKAN)
                    ->update([
                        'status' => LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI,
                        'started_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

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
            LaporanMasyarakat::markAsReadFor($administrator, $report);

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

    /**
     * Susun daftar aspirasi mirip untuk ditampilkan pada tabel dan modal.
     *
     * Hanya pasangan dengan kemiripan tinggi (lolos ambang lokasi DAN ambang
     * permasalahan) yang dikembalikan. Pasangan di bawah ambang tidak pernah
     * menghasilkan label di daftar aspirasi.
     *
     * @param  \Illuminate\Support\Collection<int, LaporanMasyarakat>  $reports
     * @return \Illuminate\Support\Collection<int, array<int, array<string, mixed>>>
     */
    private function similarityMatchesFor($reports)
    {
        return $reports->mapWithKeys(function (LaporanMasyarakat $report) {
            $matches = collect();

            foreach ($report->similarityResults as $result) {
                if (! $result->comparedLaporan) {
                    continue;
                }

                $row = $this->similarityMatchRow($result, $result->comparedLaporan);

                if ($row !== null) {
                    $matches->push($row);
                }
            }

            foreach ($report->comparedSimilarityResults as $result) {
                if (! $result->laporan) {
                    continue;
                }

                $row = $this->similarityMatchRow($result, $result->laporan);

                if ($row !== null) {
                    $matches->push($row);
                }
            }

            $unique = $matches
                ->groupBy('id')
                ->map(fn ($group) => $group->sortByDesc('score')->first())
                ->sortByDesc('score')
                ->values()
                ->all();

            return [$report->id => $unique];
        });
    }

    /**
     * Satu baris perbandingan. Mengembalikan null bila pasangan tidak
     * memenuhi kriteria kemiripan tinggi.
     *
     * @return array<string, mixed>|null
     */
    private function similarityMatchRow(LaporanSimilarityResult $result, LaporanMasyarakat $other): ?array
    {
        $evidence = is_array($result->evidence) ? $result->evidence : [];
        $locationScore = $evidence['location_score'] ?? null;
        $problemScore = $evidence['problem_score'] ?? null;

        $level = $result->similarity_level;

        if ($level === null) {
            $level = $this->passesHighSimilarity(
                $locationScore,
                $problemScore,
                $evidence['location_status'] ?? null,
                $evidence['cosine_similarity'] ?? $evidence['final_score'] ?? null
            )
                ? LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI
                : null;
        }

        if ($level !== LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI) {
            return null;
        }

        return [
            'id' => $other->id,
            'nomor_tiket' => $other->nomor_tiket,
            'ringkasan' => $other->ringkasan,
            'isi' => $other->isi,
            'pelapor_nama' => $other->pelapor_nama,
            'pelapor_alamat' => $other->pelapor_alamat,
            'kecamatan' => $other->kecamatan,
            'kategori' => $other->kategori,
            'tanggal' => $other->tanggal_laporan?->toDateString(),
            'status_label' => $other->status_label,
            'similarity_status' => $other->similarity_status,
            'similarity_status_label' => $other->similarity_status_label,
            'score' => round((float) $result->similarity_score, 6),
            'location_score' => $locationScore !== null ? round((float) $locationScore, 6) : null,
            'problem_score' => $problemScore !== null ? round((float) $problemScore, 6) : null,
            'level' => $level,
            'level_label' => LaporanMasyarakat::SIMILARITY_LEVEL_LABELS[$level] ?? 'Kemiripan Tinggi',
            'evidence' => $result->evidence,
            'reasons' => $evidence['reasons'] ?? [],
            'group_kode' => $other->similarityGroup?->kode,
        ];
    }

    /**
     * Gerbang keputusan yang sama dengan service: skor masalah, status lokasi,
     * dan skor lokasi harus sama-sama lulus. Baris lama yang belum menyimpan
     * skor ganda tetap dibaca memakai skor cosine agar tidak hilang.
     */
    private function passesHighSimilarity(
        mixed $locationScore,
        mixed $problemScore,
        mixed $locationStatus = null,
        mixed $cosineScore = null
    ): bool {
        $probThreshold = (float) config('aspiration_similarity.problem_threshold', 0.60);
        $locThreshold = (float) config('aspiration_similarity.location_threshold', 0.70);

        /*
         * Fallback data lama: evidence hanya menyimpan cosine_similarity atau
         * final_score. Tidak ada informasi lokasi sehingga keputusan mengikuti
         * skor teks seperti perilaku versi sebelumnya.
         */
        if ($problemScore === null) {
            return $cosineScore !== null && (float) $cosineScore >= $probThreshold;
        }

        if ($locationScore === null) {
            return false;
        }

        if ($locationStatus !== null && $locationStatus !== 'ok') {
            return false;
        }

        return (float) $problemScore >= $probThreshold
            && (float) $locationScore >= $locThreshold;
    }

    /**
     * Data lengkap untuk modal perbandingan satu aspirasi. Modal selalu
     * memuat aspirasi yang sedang dibuka dan seluruh kandidat mirip sehingga
     * Super Admin dapat memilih aspirasi utama tanpa berpindah halaman.
     *
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<string, mixed>
     */
    private function similarityPanelFor(LaporanMasyarakat $report, array $matches): array
    {
        $current = $this->similarityPanelReport($report, null, true);
        $previous = collect($matches)
            ->map(fn (array $match) => $this->similarityPanelReport($report, $match, false))
            ->sortByDesc('score')
            ->values()
            ->all();

        $highestScore = (float) collect($previous)->max('score');
        $matchIds = collect($matches)->pluck('id')->map(fn ($id) => (int) $id);

        return [
            'report_id' => $report->id,
            'group_kode' => $report->similarityGroup?->kode,
            'group_status' => $report->similarityGroup?->status,
            'main_laporan_id' => $report->similarityGroup?->main_laporan_id,
            'is_decided' => $report->similarityGroup?->decided_at !== null,
            'current' => $current,
            'matches' => $previous,
            'total' => count($previous),
            'highest_score' => $highestScore,
            'current_is_newest' => $matchIds->isEmpty()
                || (int) $report->id > (int) $matchIds->max(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $match
     * @return array<string, mixed>
     */
    private function similarityPanelReport(LaporanMasyarakat $current, ?array $match, bool $isCurrent): array
    {
        return [
            'id' => $isCurrent ? $current->id : $match['id'],
            'nomor_tiket' => $isCurrent ? $current->nomor_tiket : $match['nomor_tiket'],
            'ringkasan' => $isCurrent ? $current->ringkasan : $match['ringkasan'],
            'isi' => $isCurrent ? $current->isi : $match['isi'],
            'pelapor_nama' => $isCurrent ? $current->pelapor_nama : $match['pelapor_nama'],
            'pelapor_alamat' => $isCurrent ? $current->pelapor_alamat : $match['pelapor_alamat'],
            'kecamatan' => $isCurrent ? $current->kecamatan : $match['kecamatan'],
            'kategori' => $isCurrent ? $current->kategori : ($match['kategori'] ?? null),
            'kategori_label' => $isCurrent
                ? (LaporanMasyarakat::KATEGORI_OPTIONS[$current->kategori] ?? $current->kategori)
                : ($match['kategori']
                    ? (LaporanMasyarakat::KATEGORI_OPTIONS[$match['kategori']] ?? $match['kategori'])
                    : null),
            'status_label' => $isCurrent ? $current->status_label : ($match['status_label'] ?? null),
            'tanggal' => $isCurrent
                ? $current->tanggal_laporan?->toDateString()
                : $match['tanggal'],
            'is_current' => $isCurrent,
            'is_main' => $isCurrent
                ? $current->similarity_status === LaporanMasyarakat::SIMILARITY_STATUS_UTAMA
                : ($match['similarity_status'] ?? null) === LaporanMasyarakat::SIMILARITY_STATUS_UTAMA,
            'similarity_status' => $isCurrent ? $current->similarity_status : ($match['similarity_status'] ?? null),
            'similarity_status_label' => $isCurrent ? $current->similarity_status_label : $match['similarity_status_label'],
            'score' => $isCurrent ? null : $match['score'],
            'location_score' => $isCurrent ? null : $match['location_score'],
            'problem_score' => $isCurrent ? null : $match['problem_score'],
            'reasons' => $isCurrent ? [] : $match['reasons'],
            'shared_terms' => $isCurrent ? [] : collect($match['evidence']['shared_terms'] ?? [])->take(6)->all(),
        ];
    }

    private function kamusStatusFilter(mixed $value): string    {
        $value = trim((string) $value);

        return array_key_exists($value, LaporanKamusHasil::STATUS_LABELS) ? $value : '';
    }

    private function kamusTahunFilter(mixed $value): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{4}$/', $value)) {
            return '';
        }

        $tahun = (int) $value;

        return $tahun >= 2000 && $tahun <= 2100 ? (string) $tahun : '';
    }

    /**
     * @return array<int, int>
     */
    private function kamusTahunOptions(): array
    {
        $years = KamusAcuanVersi::query()->pluck('tahun')->all();
        $reportYears = LaporanMasyarakat::query()
            ->whereNotNull('kamus_tahun')
            ->distinct()
            ->pluck('kamus_tahun')
            ->all();

        $years = array_values(array_unique(array_map('intval', array_merge($years, $reportYears))));
        rsort($years);

        return $years;
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
