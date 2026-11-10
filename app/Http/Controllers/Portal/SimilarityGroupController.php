<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityDecision;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Services\AspirationGroupingService;
use App\Support\SafeReturnUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Keputusan Super Admin atas aspirasi yang terdeteksi mirip.
 *
 * Semua tindakan berasal dari modal perbandingan di dalam daftar aspirasi.
 * Tidak ada aspirasi yang dihapus: aspirasi selain utama hanya ditandai
 * sebagai duplikat dan ditautkan ke aspirasi utama, sedangkan tindakan
 * "Bukan Duplikat" melepaskan tautan tersebut.
 */
class SimilarityGroupController extends Controller
{
    public function __construct(private AspirationGroupingService $grouping)
    {
    }

    /**
     * Simpan keputusan dari modal perbandingan.
     *
     * Aksi yang didukung:
     * - pilih_utama   : satu aspirasi menjadi utama, sisanya ditandai duplikat.
     * - arsip_terkait : aspirasi baru ditautkan sebagai arsip dari aspirasi
     *                   lama yang sudah menjadi utama.
     * - tetap_terpisah: aspirasi terpilih dilepas dan diproses terpisah.
     */
    public function decideForReport(Request $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $administrator = $request->user();

        abort_unless($administrator?->isAdministrator(), 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in([
                LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
                LaporanSimilarityDecision::ACTION_TETAP_TERPISAH,
            ])],
            'main_laporan_id' => ['nullable', 'integer'],
            'decided_report_id' => ['nullable', 'integer'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $action = $validated['action'];
        $catatan = trim((string) ($validated['catatan'] ?? '')) ?: null;
        $returnUrl = SafeReturnUrl::reportsIndex($validated['return_to'] ?? null, $request);
        $redirectMainId = null;

        DB::transaction(function () use (
            $laporan,
            $validated,
            $action,
            $catatan,
            $administrator,
            &$redirectMainId
        ): void {
            $report = LaporanMasyarakat::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($laporan->id);

            LaporanMasyarakat::markAsReadFor($administrator, $report);

            $candidates = $this->candidatesFor($report);

            if ($candidates->count() < 2) {
                throw ValidationException::withMessages([
                    'similarity' => 'Tidak ada aspirasi mirip yang dapat diputuskan untuk aspirasi ini.',
                ])->errorBag('similarity');
            }

            $candidateIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $group = $this->resolveGroup($report, $candidates);
            $mainId = (int) ($validated['main_laporan_id'] ?? 0);

            if ($action === LaporanSimilarityDecision::ACTION_PILIH_UTAMA) {
                $this->selectMain(
                    $group,
                    $report,
                    $candidates,
                    $mainId,
                    $catatan,
                    $administrator->id
                );

                $redirectMainId = $mainId;
            }

            if ($action === LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT) {
                $this->archiveAsRelated(
                    $group,
                    $report,
                    $candidates,
                    $mainId,
                    $catatan,
                    $administrator->id
                );

                $redirectMainId = $mainId;
            }

            if ($action === LaporanSimilarityDecision::ACTION_TETAP_TERPISAH) {
                $selected = $this->selectedIds($validated, $candidateIds);

                if ($selected === []) {
                    $selected = $candidateIds;
                }

                $this->separateReports(
                    $group,
                    $report,
                    $candidates,
                    $selected,
                    $catatan,
                    $administrator->id
                );
            }

            AktivitasSistem::catat(
                'laporan.kemiripan.'.$action,
                $administrator,
                $group,
                [
                    'group_kode' => $group->kode,
                    'dprd_member_id' => $group->dprd_member_id,
                    'main_laporan_id' => $group->main_laporan_id,
                    'catatan' => $catatan,
                ],
                'Keputusan kemiripan '.$group->kode.' disimpan: '
                    .(LaporanSimilarityDecision::ACTION_LABELS[$action] ?? $action).'.'
            );
        });

        if ($redirectMainId !== null) {
            return redirect()
                ->to(route('laporan.show', [
                    'laporan' => $redirectMainId,
                    'return_to' => $returnUrl,
                ]))
                ->with('success', $action === LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT
                    ? 'Aspirasi baru berhasil disimpan sebagai arsip terkait. Lanjutkan aspirasi utama melalui proses persetujuan dan penugasan seperti biasa.'
                    : 'Aspirasi utama berhasil ditetapkan. Aspirasi lain tetap tersimpan sebagai arsip terkait. Proses persetujuan dan penugasan tetap dilakukan terpisah.');
        }

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Aspirasi berhasil dipisahkan dan akan diproses sebagai aspirasi tersendiri.');
    }

    /**
     * Periksa ulang satu anggota DPRD tanpa menyentuh keputusan yang sudah
     * dibuat Super Admin.
     */
    public function recheck(Request $request, DprdMember $member): RedirectResponse
    {
        $administrator = $request->user();

        abort_unless($administrator?->isAdministrator(), 403);

        $result = $this->grouping->regroupMember($member, $administrator);

        return redirect()
            ->back()
            ->with('success', sprintf(
                'Pemeriksaan ulang selesai: %d aspirasi, %d pasangan mirip, %d kelompok.',
                $result['reports'],
                $result['pairs'],
                $result['groups']
            ));
    }

    /**
     * Seluruh aspirasi yang berada dalam kelompok yang sama dengan laporan
     * yang dibuka. Bila kelompok belum terbentuk, kandidat diambil dari hasil
     * perhitungan kemiripan tinggi yang tersimpan.
     *
     * @return Collection<int, LaporanMasyarakat>
     */
    private function candidatesFor(LaporanMasyarakat $report): Collection
    {
        $ids = collect([$report->id]);

        if ($report->similarity_group_id) {
            $memberIds = LaporanSimilarityGroupMember::query()
                ->where('laporan_similarity_group_id', $report->similarity_group_id)
                ->pluck('laporan_masyarakat_id');

            $ids = $ids->merge($memberIds);
        }

        $pairs = LaporanSimilarityResult::query()
            ->where(function ($query) use ($report) {
                $query->where('laporan_masyarakat_id', $report->id)
                    ->orWhere('compared_laporan_masyarakat_id', $report->id);
            })
            ->where('similarity_level', LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI)
            ->get(['laporan_masyarakat_id', 'compared_laporan_masyarakat_id']);

        foreach ($pairs as $pair) {
            $ids->push((int) $pair->laporan_masyarakat_id);
            $ids->push((int) $pair->compared_laporan_masyarakat_id);
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->values();

        return LaporanMasyarakat::query()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, LaporanMasyarakat>  $candidates
     */
    private function resolveGroup(LaporanMasyarakat $report, Collection $candidates): LaporanSimilarityGroup
    {
        if ($report->similarity_group_id) {
            $group = LaporanSimilarityGroup::query()->find($report->similarity_group_id);

            if ($group) {
                return $group;
            }
        }

        $existing = LaporanSimilarityGroup::query()
            ->where('dprd_member_id', $report->dprd_member_id)
            ->whereHas('members', fn ($query) => $query->whereIn(
                'laporan_masyarakat_id',
                $candidates->pluck('id')->all()
            ))
            ->first();

        if ($existing) {
            return $existing;
        }

        $sequence = 1;

        do {
            $kode = 'KLP-'.str_pad((string) $sequence++, 2, '0', STR_PAD_LEFT);
        } while (LaporanSimilarityGroup::query()
            ->where('dprd_member_id', $report->dprd_member_id)
            ->where('kode', $kode)
            ->exists());

        $group = LaporanSimilarityGroup::query()->create([
            'dprd_member_id' => $report->dprd_member_id,
            'kode' => $kode,
            'status' => LaporanSimilarityGroup::STATUS_MENUNGGU,
            'algorithm_version' => \App\Services\AspirationSimilarityService::ALGORITHM_VERSION,
            'threshold_high' => (float) config('aspiration_similarity.problem_threshold', 0.70),
            'threshold_medium' => (float) config('aspiration_similarity.problem_threshold', 0.70),
            'snapshot' => [
                'member_id' => $report->dprd_member_id,
                'report_ids' => $candidates->pluck('id')->all(),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        foreach ($candidates as $candidate) {
            $candidate->forceFill(['similarity_group_id' => $group->id])->save();

            $membership = LaporanSimilarityGroupMember::query()
                ->where('laporan_masyarakat_id', $candidate->id)
                ->first();

            if ($membership) {
                continue;
            }

            LaporanSimilarityGroupMember::query()->create([
                'laporan_similarity_group_id' => $group->id,
                'laporan_masyarakat_id' => $candidate->id,
                'peran' => LaporanSimilarityGroupMember::PERAN_ANGGOTA,
            ]);
        }

        return $group;
    }

    /**
     * @param  Collection<int, LaporanMasyarakat>  $candidates
     */
    private function selectMain(
        LaporanSimilarityGroup $group,
        LaporanMasyarakat $current,
        Collection $candidates,
        int $mainId,
        ?string $catatan,
        int $userId
    ): void {
        if (! $candidates->contains(fn (LaporanMasyarakat $report) => (int) $report->id === $mainId)) {
            throw ValidationException::withMessages([
                'main_laporan_id' => 'Pilih salah satu aspirasi yang tampil sebagai aspirasi utama.',
            ])->errorBag('similarity');
        }

        $main = $candidates->first(fn (LaporanMasyarakat $report) => (int) $report->id === $mainId);

        foreach ($candidates as $candidate) {
            $isMain = (int) $candidate->id === $mainId;
            $previous = $candidate->similarity_status;

            $scoreBase = $isMain
                ? ($current->id !== $mainId
                    ? $current
                    : $candidates->first(fn (LaporanMasyarakat $item) => (int) $item->id !== $mainId))
                : $main;

            $candidate->forceFill([
                'similarity_group_id' => $group->id,
                'duplicate_of_id' => $isMain ? null : $mainId,
                'similarity_status' => $isMain
                    ? LaporanMasyarakat::SIMILARITY_STATUS_UTAMA
                    : LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN,
                'similarity_reviewed_at' => now(),
                'similarity_reviewed_by' => $userId,
                'similarity_note' => $catatan,
            ])->save();

            LaporanSimilarityGroupMember::query()->updateOrCreate(
                [
                    'laporan_similarity_group_id' => $group->id,
                    'laporan_masyarakat_id' => $candidate->id,
                ],
                [
                    'peran' => $isMain
                        ? LaporanSimilarityGroupMember::PERAN_UTAMA
                        : LaporanSimilarityGroupMember::PERAN_DUPLIKAT,
                    'decided_by' => $userId,
                    'decided_at' => now(),
                    'catatan' => $catatan,
                ]
            );

            $this->logDecision(
                $group,
                $candidate,
                $userId,
                LaporanSimilarityDecision::ACTION_PILIH_UTAMA,
                $previous,
                $candidate->similarity_status,
                $catatan,
                [
                    'dprd_member_id' => $group->dprd_member_id,
                    'main_laporan_id' => $mainId,
                    'duplicate_laporan_ids' => $candidates
                        ->reject(fn (LaporanMasyarakat $item) => (int) $item->id === $mainId)
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'similarity_scores' => $this->scoresFor($scoreBase ?? $current, $candidate),
                    'decision_at' => now()->toIso8601String(),
                ]
            );
        }

        $group->forceFill([
            'main_laporan_id' => $mainId,
            'status' => LaporanSimilarityGroup::STATUS_DIPUTUSKAN,
            'decided_by' => $userId,
            'decided_at' => now(),
            'catatan' => $catatan,
        ])->save();
    }

    /**
     * Simpan aspirasi yang sedang dibuka sebagai arsip terkait dari aspirasi
     * lama yang dipilih Super Admin. Hanya laporan yang dibuka yang ditautkan;
     * isi, identitas pelapor, tanggal, dan lampiran tidak diubah.
     *
     * @param  Collection<int, LaporanMasyarakat>  $candidates
     */
    private function archiveAsRelated(
        LaporanSimilarityGroup $group,
        LaporanMasyarakat $current,
        Collection $candidates,
        int $mainId,
        ?string $catatan,
        int $userId
    ): void {
        $main = $candidates->first(fn (LaporanMasyarakat $report) => (int) $report->id === $mainId);

        if (! $main || (int) $main->id === (int) $current->id) {
            throw ValidationException::withMessages([
                'main_laporan_id' => 'Pilih salah satu aspirasi lama sebagai aspirasi utama untuk arsip terkait.',
            ])->errorBag('similarity');
        }

        if ($group->main_laporan_id !== null && (int) $group->main_laporan_id !== $mainId) {
            throw ValidationException::withMessages([
                'main_laporan_id' => 'Kelompok ini sudah memiliki aspirasi utama. Gunakan "Pilih sebagai Aspirasi Utama" untuk menggantinya.',
            ])->errorBag('similarity');
        }

        $previousMainStatus = $main->similarity_status;
        $previousCurrentStatus = $current->similarity_status;

        $main->forceFill([
            'similarity_group_id' => $group->id,
            'duplicate_of_id' => null,
            'similarity_status' => LaporanMasyarakat::SIMILARITY_STATUS_UTAMA,
            'similarity_reviewed_at' => now(),
            'similarity_reviewed_by' => $userId,
            'similarity_note' => $catatan,
        ])->save();

        $current->forceFill([
            'similarity_group_id' => $group->id,
            'duplicate_of_id' => $main->id,
            'similarity_status' => LaporanMasyarakat::SIMILARITY_STATUS_DIGABUNGKAN,
            'similarity_reviewed_at' => now(),
            'similarity_reviewed_by' => $userId,
            'similarity_note' => $catatan,
        ])->save();

        LaporanSimilarityGroupMember::query()->updateOrCreate(
            [
                'laporan_similarity_group_id' => $group->id,
                'laporan_masyarakat_id' => $main->id,
            ],
            [
                'peran' => LaporanSimilarityGroupMember::PERAN_UTAMA,
                'decided_by' => $userId,
                'decided_at' => now(),
                'catatan' => $catatan,
            ]
        );

        LaporanSimilarityGroupMember::query()->updateOrCreate(
            [
                'laporan_similarity_group_id' => $group->id,
                'laporan_masyarakat_id' => $current->id,
            ],
            [
                'peran' => LaporanSimilarityGroupMember::PERAN_DUPLIKAT,
                'decided_by' => $userId,
                'decided_at' => now(),
                'catatan' => $catatan,
            ]
        );

        $this->logDecision(
            $group,
            $main,
            $userId,
            LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
            $previousMainStatus,
            $main->similarity_status,
            $catatan,
            [
                'dprd_member_id' => $group->dprd_member_id,
                'main_laporan_id' => $main->id,
                'related_laporan_id' => $current->id,
                'similarity_scores' => $this->scoresFor($current, $main),
                'decision_at' => now()->toIso8601String(),
            ]
        );

        $this->logDecision(
            $group,
            $current,
            $userId,
            LaporanSimilarityDecision::ACTION_ARSIP_TERKAIT,
            $previousCurrentStatus,
            $current->similarity_status,
            $catatan,
            [
                'dprd_member_id' => $group->dprd_member_id,
                'main_laporan_id' => $main->id,
                'related_laporan_id' => $current->id,
                'similarity_scores' => $this->scoresFor($current, $main),
                'decision_at' => now()->toIso8601String(),
            ]
        );

        $group->forceFill([
            'main_laporan_id' => $main->id,
            'status' => LaporanSimilarityGroup::STATUS_DIPUTUSKAN,
            'decided_by' => $userId,
            'decided_at' => now(),
            'catatan' => $catatan,
        ])->save();
    }

    /**
     * @param  Collection<int, LaporanMasyarakat>  $candidates
     * @param  array<int, int>  $selectedIds
     */
    private function separateReports(
        LaporanSimilarityGroup $group,
        LaporanMasyarakat $current,
        Collection $candidates,
        array $selectedIds,
        ?string $catatan,
        int $userId
    ): void {
        foreach ($candidates as $candidate) {
            if (! in_array((int) $candidate->id, $selectedIds, true)) {
                continue;
            }

            $previous = $candidate->similarity_status;

            $candidate->forceFill([
                'duplicate_of_id' => null,
                'similarity_status' => LaporanMasyarakat::SIMILARITY_STATUS_DIPISAH,
                'similarity_reviewed_at' => now(),
                'similarity_reviewed_by' => $userId,
                'similarity_note' => $catatan,
            ])->save();

            LaporanSimilarityGroupMember::query()->updateOrCreate(
                [
                    'laporan_similarity_group_id' => $group->id,
                    'laporan_masyarakat_id' => $candidate->id,
                ],
                [
                    'peran' => LaporanSimilarityGroupMember::PERAN_DIPISAH,
                    'decided_by' => $userId,
                    'decided_at' => now(),
                    'catatan' => $catatan,
                ]
            );

            $scoreBase = (int) $candidate->id === (int) $current->id
                ? $candidates->first(fn (LaporanMasyarakat $item) => (int) $item->id !== (int) $current->id)
                : $current;

            $this->logDecision(
                $group,
                $candidate,
                $userId,
                LaporanSimilarityDecision::ACTION_TETAP_TERPISAH,
                $previous,
                $candidate->similarity_status,
                $catatan,
                [
                    'dprd_member_id' => $group->dprd_member_id,
                    'main_laporan_id' => $group->main_laporan_id,
                    'separated_laporan_ids' => $selectedIds,
                    'similarity_scores' => $this->scoresFor($scoreBase ?? $current, $candidate),
                    'decision_at' => now()->toIso8601String(),
                ]
            );
        }

        $remainingMain = $candidates
            ->reject(fn (LaporanMasyarakat $candidate) => in_array((int) $candidate->id, $selectedIds, true))
            ->first(fn (LaporanMasyarakat $candidate) => $candidate->similarity_status
                === LaporanMasyarakat::SIMILARITY_STATUS_UTAMA);

        if ($remainingMain) {
            $group->forceFill([
                'main_laporan_id' => $remainingMain->id,
                'status' => LaporanSimilarityGroup::STATUS_DIPUTUSKAN,
                'decided_by' => $userId,
                'decided_at' => now(),
                'catatan' => $catatan,
            ])->save();

            return;
        }

        $group->forceFill([
            'main_laporan_id' => null,
            'status' => LaporanSimilarityGroup::STATUS_DIBUBARKAN,
            'decided_by' => $userId,
            'decided_at' => now(),
            'catatan' => $catatan,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, int>  $candidateIds
     * @return array<int, int>
     */
    private function selectedIds(array $validated, array $candidateIds): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $validated['selected_ids'] ?? []),
            fn (int $id) => in_array($id, $candidateIds, true)
        )));
    }

    /**
     * @return array{location: float|null, problem: float|null, combined: float|null}
     */
    private function scoresFor(LaporanMasyarakat $left, LaporanMasyarakat $right): array
    {
        if ((int) $left->id === (int) $right->id) {
            return ['location' => null, 'problem' => null, 'combined' => null];
        }

        $result = LaporanSimilarityResult::query()
            ->where(function ($query) use ($left, $right) {
                $query->where('laporan_masyarakat_id', $left->id)
                    ->where('compared_laporan_masyarakat_id', $right->id);
            })
            ->orWhere(function ($query) use ($left, $right) {
                $query->where('laporan_masyarakat_id', $right->id)
                    ->where('compared_laporan_masyarakat_id', $left->id);
            })
            ->first();

        if (! $result) {
            return ['location' => null, 'problem' => null, 'combined' => null];
        }

        $evidence = is_array($result->evidence) ? $result->evidence : [];

        return [
            'location' => isset($evidence['location_score']) ? round((float) $evidence['location_score'], 6) : null,
            'problem' => isset($evidence['problem_score']) ? round((float) $evidence['problem_score'], 6) : null,
            'combined' => round((float) $result->similarity_score, 6),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logDecision(
        LaporanSimilarityGroup $group,
        ?LaporanMasyarakat $report,
        int $userId,
        string $action,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $catatan,
        array $payload
    ): void {
        LaporanSimilarityDecision::query()->create([
            'laporan_similarity_group_id' => $group->id,
            'laporan_masyarakat_id' => $report?->id,
            'user_id' => $userId,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'catatan' => $catatan,
            'payload' => $payload,
        ]);
    }
}
