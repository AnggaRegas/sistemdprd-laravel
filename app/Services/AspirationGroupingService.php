<?php

namespace App\Services;

use App\Models\DprdMember;
use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityGroup;
use App\Models\LaporanSimilarityGroupMember;
use App\Models\LaporanSimilarityResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pengelompokan aspirasi mirip untuk satu anggota DPRD.
 *
 * Service ini memakai perhitungan TF-IDF + cosine yang sudah ada pada
 * AspirationSimilarityService. Pengelompokan memakai complete linkage:
 * sebuah aspirasi hanya masuk ke kelompok bila ia mencapai ambang kemiripan
 * dengan SEMUA anggota kelompok. Aturan ini mencegah pengelompokan berantai
 * (A mirip B, B mirip C, tetapi A tidak mirip C).
 *
 * Keputusan Super Admin tidak pernah ditimpa otomatis. Saat pemeriksaan
 * ulang dijalankan, hanya kelompok yang belum diputuskan yang disusun ulang.
 */
class AspirationGroupingService
{
    public function __construct(private AspirationSimilarityService $similarity)
    {
    }

    /**
     * Susun ulang kelompok kemiripan untuk satu anggota DPRD.
     *
     * Seluruh proses berjalan dalam satu transaksi dan mengunci baris anggota
     * DPRD. Kunci ini menyerialkan pemeriksaan ulang untuk anggota yang sama
     * sehingga dua proses yang berjalan bersamaan tidak dapat menyisipkan
     * keanggotaan atau pasangan yang sama dua kali.
     *
     * @return array<string, int|float>
     */
    public function regroupMember(DprdMember $member, ?User $actor = null): array
    {
        return DB::transaction(function () use ($member, $actor) {
            DprdMember::query()
                ->whereKey($member->id)
                ->lockForUpdate()
                ->firstOrFail();

            $reports = LaporanMasyarakat::query()
                ->where('dprd_member_id', $member->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($reports->isEmpty()) {
                return [
                    'reports' => 0,
                    'pairs' => 0,
                    'groups' => 0,
                    'threshold_high' => (float) config('aspiration_similarity.problem_threshold', 0.70),
                    'threshold_medium' => (float) config('aspiration_similarity.problem_threshold', 0.70),
                ];
            }

            $analysis = $this->similarity->analyzeCorpus($reports);

            $this->persistPairs($reports, $analysis);
            $this->persistAnalysisRuns($member, $reports, $analysis);
            $groupCount = $this->persistGroups($member, $reports, $analysis, $actor);

            return [
                'reports' => $reports->count(),
                'pairs' => count($analysis['pairs']),
                'groups' => $groupCount,
                'threshold_high' => $analysis['threshold_high'],
                'threshold_medium' => $analysis['threshold_medium'],
            ];
        });
    }

    /**
     * Jalankan analisis untuk satu laporan lalu susun ulang kelompok anggota.
     */
    public function regroupForReport(LaporanMasyarakat $report, ?User $actor = null): void
    {
        if (! $report->dprd_member_id) {
            return;
        }

        $member = $report->member;

        if ($member) {
            $this->regroupMember($member, $actor);
        }
    }

    /**
     * Simpan hasil pasangan. Konvensi arah tetap sama seperti sistem lama:
     * laporan dengan id lebih besar menyimpan hasil terhadap laporan yang
     * lebih lama. Tampilan membaca kedua arah agar semua pihak terlihat.
     *
     * @param  Collection<int, LaporanMasyarakat>  $reports
     * @param  array<string, mixed>  $analysis
     */
    private function persistPairs(Collection $reports, array $analysis): void
    {
        $ids = $reports->pluck('id')->all();

        LaporanSimilarityResult::query()
            ->whereIn('laporan_masyarakat_id', $ids)
            ->delete();

        foreach ($analysis['pairs'] as $pair) {
            $this->storePair($pair);
        }

        /*
         * Pasangan yang hanya mirip teks tetapi lokasinya belum terkonfirmasi
         * tetap disimpan sebagai bahan peninjauan manual. Baris ini tidak
         * pernah dihitung sebagai duplikat/kelompok karena levelnya berbeda.
         */
        foreach ($analysis['review_pairs'] ?? [] as $pair) {
            $this->storePair($pair);
        }
    }

    /**
     * @param  array<string, mixed>  $pair
     */
    private function storePair(array $pair): void
    {
        $left = (int) $pair['left_id'];
        $right = (int) $pair['right_id'];
        $newer = max($left, $right);
        $older = min($left, $right);

        LaporanSimilarityResult::query()->create([
            'laporan_masyarakat_id' => $newer,
            'compared_laporan_masyarakat_id' => $older,
            // Skor tersimpan adalah cosine TF-IDF murni.
            'similarity_score' => $pair['problem_score'] ?? $pair['score'],
            'similarity_level' => $pair['level'],
            'evidence' => $pair['evidence'],
            'checked_at' => now(),
        ]);
    }

    /**
     * Perbarui riwayat analisis tiap laporan tanpa menghapus snapshot lama.
     * Snapshot disusun dari hasil perhitungan yang benar-benar dijalankan.
     *
     * @param  Collection<int, LaporanMasyarakat>  $reports
     * @param  array<string, mixed>  $analysis
     */
    private function persistAnalysisRuns(DprdMember $member, Collection $reports, array $analysis): void
    {
        $pairsByReport = [];
        $reviewByReport = [];

        foreach ($analysis['pairs'] as $pair) {
            $pairsByReport[$pair['left_id']][] = $pair;
            $pairsByReport[$pair['right_id']][] = $pair;
        }

        foreach ($analysis['review_pairs'] ?? [] as $pair) {
            $reviewByReport[$pair['left_id']][] = $pair;
            $reviewByReport[$pair['right_id']][] = $pair;
        }

        foreach ($reports as $report) {
            $involved = $pairsByReport[$report->id] ?? [];
            $reviewInvolved = $reviewByReport[$report->id] ?? [];
            $level = $analysis['reports'][$report->id]['level']
                ?? LaporanMasyarakat::SIMILARITY_LEVEL_TIDAK_ADA;
            $highest = $analysis['reports'][$report->id]['highest_similarity'] ?? null;

            $snapshotPairs = $this->snapshotPairs($involved, $report->id);
            $snapshotReview = $this->snapshotPairs($reviewInvolved, $report->id);

            LaporanAnalysisRun::query()->updateOrCreate(
                ['laporan_masyarakat_id' => $report->id],
                [
                    'dprd_member_id' => $member->id,
                    'threshold' => $analysis['threshold_problem'] ?? $analysis['threshold_high'],
                    'max_results' => count($involved),
                    'candidate_count' => max(0, $reports->count() - 1),
                    'highest_similarity' => $highest,
                    'is_duplicate' => $level === LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                    'has_detail' => true,
                    'algorithm_version' => $analysis['algorithm_version'],
                    'analyzed_at' => now(),
                    'snapshot' => [
                        'algorithm_version' => $analysis['algorithm_version'],
                        'threshold_high' => $analysis['threshold_high'],
                        'threshold_problem' => $analysis['threshold_problem'] ?? $analysis['threshold_high'],
                        'threshold_location' => $analysis['threshold_location'] ?? null,
                        'threshold_medium' => $analysis['threshold_medium'],
                        'formulas' => $analysis['formulas'] ?? [],
                        'decision_rule' => $analysis['decision_rule'] ?? null,
                        'member_id' => $member->id,
                        'report' => [
                            'id' => $report->id,
                            'nomor_tiket' => $report->nomor_tiket,
                            'ringkasan' => $report->ringkasan,
                            'pelapor_alamat' => $report->pelapor_alamat,
                            'kecamatan' => $report->kecamatan,
                            'kategori' => $report->kategori,
                        ],
                        'preprocessing' => $analysis['preprocessing'][$report->id] ?? null,
                        'corpus_report_count' => $reports->count(),
                        'pairs' => $snapshotPairs,
                        'manual_review' => $snapshotReview,
                        'result' => [
                            'highest_similarity' => $highest,
                            'highest_problem_score' => $analysis['reports'][$report->id]['highest_problem_score'] ?? null,
                            'highest_combined_score' => $analysis['reports'][$report->id]['highest_combined_score'] ?? null,
                            'level' => $level,
                            'is_duplicate' => $level === LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                            'manual_review_required' => $reviewInvolved !== [],
                        ],
                        'generated_at' => now()->toIso8601String(),
                    ],
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $pairs
     * @return array<int, array<string, mixed>>
     */
    private function snapshotPairs(array $pairs, int $reportId): array
    {
        return array_map(function (array $pair) use ($reportId) {
            $otherId = (int) $pair['left_id'] === $reportId
                ? (int) $pair['right_id']
                : (int) $pair['left_id'];

            $other = LaporanMasyarakat::query()->find($otherId);

            return [
                'compared_laporan_id' => $otherId,
                'compared_nomor_tiket' => $other?->nomor_tiket,
                'compared_ringkasan' => $other?->ringkasan,
                'score' => $pair['score'],
                'problem_score' => $pair['problem_score'] ?? null,
                'location_score' => $pair['location_score'] ?? null,
                'combined_score' => $pair['combined_score'] ?? null,
                'cosine' => $pair['cosine'] ?? null,
                'level' => $pair['level'],
                'evidence' => $pair['evidence'],
            ];
        }, $pairs);
    }

    /**
     * @param  Collection<int, LaporanMasyarakat>  $reports
     * @param  array<string, mixed>  $analysis
     */
    private function persistGroups(
        DprdMember $member,
        Collection $reports,
        array $analysis,
        ?User $actor
    ): int {
        /*
         * Kelompok yang sudah diputuskan tidak boleh diubah otomatis.
         * Kelompok tanpa keputusan disusun ulang agar selalu mengikuti
         * hasil perhitungan terbaru.
         */
        LaporanSimilarityGroup::query()
            ->where('dprd_member_id', $member->id)
            ->whereNull('decided_at')
            ->delete();

        $lockedIds = $reports
            ->filter(fn (LaporanMasyarakat $report) => $report->similarity_status !== null
                || $report->duplicate_of_id !== null
                || $report->similarity_reviewed_at !== null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        /*
         * Seluruh anggota kelompok yang sudah diputuskan ikut dikunci.
         * Dengan begitu pemeriksaan ulang tidak memindahkan aspirasi ke
         * kelompok lain dan tidak menimpa keputusan Super Admin.
         */
        $decidedMemberIds = LaporanSimilarityGroupMember::query()
            ->whereHas('group', fn ($query) => $query
                ->where('dprd_member_id', $member->id)
                ->whereNotNull('decided_at'))
            ->pluck('laporan_masyarakat_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $lockedIds = array_values(array_unique(array_merge($lockedIds, $decidedMemberIds)));

        $unlocked = $reports->reject(fn (LaporanMasyarakat $report) => in_array((int) $report->id, $lockedIds, true));
        $unlockedIds = $unlocked->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        /*
         * Unique index laporan_masyarakat_id adalah sumber kebenaran: satu
         * aspirasi hanya boleh memiliki satu baris keanggotaan. Baris sisa
         * milik aspirasi yang belum diputuskan dibersihkan lebih dahulu agar
         * pemeriksaan ulang tetap idempoten.
         */
        if ($unlockedIds !== []) {
            LaporanSimilarityGroupMember::query()
                ->whereIn('laporan_masyarakat_id', $unlockedIds)
                ->delete();
        }

        $matrix = [];
        $edges = [];

        foreach ($analysis['pairs'] as $pair) {
            $left = (int) $pair['left_id'];
            $right = (int) $pair['right_id'];
            $matrix[$left][$right] = $pair['score'];
            $matrix[$right][$left] = $pair['score'];

            if (! in_array($left, $lockedIds, true) && ! in_array($right, $lockedIds, true)) {
                $edges[] = [
                    'left_id' => $left,
                    'right_id' => $right,
                    'score' => $pair['score'],
                ];
            }
        }

        $clusters = $this->completeLinkage(
            $unlockedIds,
            $edges,
            $matrix,
            (float) $analysis['threshold_medium']
        );

        $sequence = $this->nextSequence($member);
        $assignedIds = [];
        $created = 0;

        foreach ($clusters as $cluster) {
            $cluster = array_values(array_unique(array_map('intval', $cluster)));
            $cluster = array_values(array_diff($cluster, $assignedIds));

            if (count($cluster) < 2) {
                continue;
            }

            /*
             * Aspirasi utama ditentukan otomatis dari waktu masuk laporan,
             * bukan dari ID. Laporan yang paling dahulu masuk menjadi
             * aspirasi utama; ID hanya dipakai sebagai penentu bila waktu
             * masuknya sama.
             */
            $clusterReports = collect($cluster)
                ->map(fn (int $reportId) => $reports->firstWhere('id', $reportId))
                ->filter();
            $mainReport = $clusterReports->sort(function (LaporanMasyarakat $left, LaporanMasyarakat $right) {
                $leftTime = $left->created_at?->getTimestamp() ?? PHP_INT_MAX;
                $rightTime = $right->created_at?->getTimestamp() ?? PHP_INT_MAX;

                return $leftTime === $rightTime
                    ? (int) $left->id <=> (int) $right->id
                    : $leftTime <=> $rightTime;
            })->first();
            $mainId = (int) ($mainReport?->id ?? $cluster[0]);

            $kode = 'KLP-'.str_pad((string) $sequence++, 2, '0', STR_PAD_LEFT);

            while (LaporanSimilarityGroup::query()
                ->where('dprd_member_id', $member->id)
                ->where('kode', $kode)
                ->exists()) {
                $kode = 'KLP-'.str_pad((string) $sequence++, 2, '0', STR_PAD_LEFT);
            }

            $clusterPairs = array_values(array_filter(
                $analysis['pairs'],
                fn (array $pair) => in_array((int) $pair['left_id'], $cluster, true)
                    && in_array((int) $pair['right_id'], $cluster, true)
            ));

            $group = LaporanSimilarityGroup::query()->create([
                'dprd_member_id' => $member->id,
                'kode' => $kode,
                'status' => LaporanSimilarityGroup::STATUS_MENUNGGU,
                'main_laporan_id' => $mainId,
                'algorithm_version' => $analysis['algorithm_version'],
                'threshold_high' => $analysis['threshold_high'],
                'threshold_medium' => $analysis['threshold_medium'],
                'snapshot' => [
                    'algorithm_version' => $analysis['algorithm_version'],
                    'threshold_high' => $analysis['threshold_high'],
                    'threshold_problem' => $analysis['threshold_problem'] ?? $analysis['threshold_high'],
                    'threshold_location' => $analysis['threshold_location'] ?? null,
                    'threshold_medium' => $analysis['threshold_medium'],
                    'formulas' => $analysis['formulas'] ?? [],
                    'decision_rule' => $analysis['decision_rule'] ?? null,
                    'member_id' => $member->id,
                    'report_ids' => $cluster,
                    'pairs' => $clusterPairs,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);

            foreach ($cluster as $reportId) {
                $assignedIds[] = $reportId;
                $report = $reports->firstWhere('id', $reportId);

                if (! $report) {
                    continue;
                }

                $bestPair = null;
                $bestScore = 0.0;

                foreach ($cluster as $otherId) {
                    if ($otherId === $reportId) {
                        continue;
                    }

                    $score = (float) ($matrix[$reportId][$otherId] ?? 0.0);

                    if ($bestPair === null || $score > $bestScore) {
                        $bestScore = $score;
                        $bestPair = collect($clusterPairs)->first(fn (array $pair) => (
                            ((int) $pair['left_id'] === $reportId && (int) $pair['right_id'] === $otherId)
                            || ((int) $pair['right_id'] === $reportId && (int) $pair['left_id'] === $otherId)
                        ));
                    }
                }

                $this->syncMembership($group, $reportId, [
                    'peran' => $reportId === $mainId
                        ? LaporanSimilarityGroupMember::PERAN_UTAMA
                        : LaporanSimilarityGroupMember::PERAN_ANGGOTA,
                    'similarity_level' => $analysis['reports'][$reportId]['level'] ?? null,
                    'similarity_score' => $bestScore > 0 ? $bestScore : null,
                    'evidence' => $bestPair['evidence'] ?? null,
                ]);

                $report->forceFill([
                    'similarity_group_id' => $group->id,
                    'similarity_level' => $analysis['reports'][$reportId]['level'] ?? null,
                ])->save();
            }

            $created++;
        }

        /*
         * Aspirasi yang tidak masuk kelompok tetap diperbarui tingkat
         * kemiripannya, tanpa diberi kelompok.
         */
        foreach ($unlocked as $report) {
            if (in_array((int) $report->id, $assignedIds, true)) {
                continue;
            }

            $report->forceFill([
                'similarity_group_id' => null,
                'similarity_level' => $analysis['reports'][$report->id]['level']
                    ?? LaporanMasyarakat::SIMILARITY_LEVEL_TIDAK_ADA,
            ])->save();
        }

        /*
         * Laporan yang sudah diputuskan tetap menyimpan kelompok dan status
         * keputusannya; hanya tingkat kemiripan otomatis yang diperbarui.
         */
        foreach ($reports as $report) {
            if (! in_array((int) $report->id, $lockedIds, true)) {
                continue;
            }

            $report->forceFill([
                'similarity_level' => $analysis['reports'][$report->id]['level']
                    ?? $report->similarity_level,
            ])->save();
        }

        return $created;
    }

    /**
     * Pastikan satu aspirasi memiliki tepat satu baris keanggotaan kelompok.
     *
     * Unique index laporan_masyarakat_id pada tabel
     * laporan_similarity_group_members menjamin satu aspirasi tidak dapat
     * berada pada dua kelompok. Karena itu kunci pencarian selalu memakai
     * laporan_masyarakat_id, bukan pasangan (kelompok, laporan). Cara ini
     * membuat penyimpanan tetap benar meski data lama sempat tidak sinkron.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function syncMembership(
        LaporanSimilarityGroup $group,
        int $reportId,
        array $attributes = []
    ): LaporanSimilarityGroupMember {
        $membership = LaporanSimilarityGroupMember::query()->updateOrCreate(
            ['laporan_masyarakat_id' => $reportId],
            array_merge($attributes, ['laporan_similarity_group_id' => $group->id])
        );

        LaporanMasyarakat::withTrashed()
            ->whereKey($reportId)
            ->update(['similarity_group_id' => $group->id]);

        return $membership;
    }

    /**
     * Tambahkan aspirasi ke kelompok bila belum menjadi anggota kelompok mana
     * pun. Bila sudah menjadi anggota, baris yang ada dipertahankan agar
     * keputusan Super Admin tidak tertimpa.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function ensureMembership(
        LaporanSimilarityGroup $group,
        int $reportId,
        array $attributes = []
    ): LaporanSimilarityGroupMember {
        $membership = LaporanSimilarityGroupMember::query()->firstOrCreate(
            ['laporan_masyarakat_id' => $reportId],
            array_merge($attributes, ['laporan_similarity_group_id' => $group->id])
        );

        if ((int) $membership->laporan_similarity_group_id !== (int) $group->id) {
            $membership = $this->syncMembership($group, $reportId, $attributes);
        }

        LaporanMasyarakat::withTrashed()
            ->whereKey($reportId)
            ->update(['similarity_group_id' => $group->id]);

        return $membership;
    }

    /**
     * Gabungkan kelompok sumber ke kelompok tujuan.
     *
     * Keanggotaan dipindahkan, bukan disalin, sehingga unique index
     * laporan_masyarakat_id tetap terjaga. Kelompok sumber ditandai
     * dibubarkan dan riwayat keputusannya tidak dihapus.
     */
    public function mergeGroups(
        LaporanSimilarityGroup $target,
        LaporanSimilarityGroup $source,
        ?User $actor = null
    ): void {
        if ((int) $target->id === (int) $source->id) {
            return;
        }

        LaporanSimilarityGroupMember::query()
            ->where('laporan_similarity_group_id', $source->id)
            ->update(['laporan_similarity_group_id' => $target->id]);

        LaporanMasyarakat::withTrashed()
            ->where('similarity_group_id', $source->id)
            ->update(['similarity_group_id' => $target->id]);

        $wasDecided = $target->decided_at !== null || $source->decided_at !== null;

        /*
         * Bila belum ada keputusan, aspirasi utama mengikuti waktu masuk
         * paling awal di antara seluruh anggota kelompok gabungan. Bila
         * sudah diputuskan, pilihan Super Admin dipertahankan.
         */
        $mainId = $wasDecided
            ? ($target->main_laporan_id ?? $source->main_laporan_id)
            : null;

        if ($mainId === null) {
            $mainId = LaporanMasyarakat::query()
                ->whereIn(
                    'id',
                    LaporanSimilarityGroupMember::query()
                        ->where('laporan_similarity_group_id', $target->id)
                        ->pluck('laporan_masyarakat_id')
                )
                ->orderBy('created_at')
                ->orderBy('id')
                ->value('id');
        }

        $target->forceFill([
            'main_laporan_id' => $mainId,
            'status' => $wasDecided
                ? LaporanSimilarityGroup::STATUS_DIPUTUSKAN
                : $target->status,
            'decided_by' => $target->decided_by ?? $source->decided_by,
            'decided_at' => $target->decided_at ?? $source->decided_at,
            'catatan' => trim((string) $target->catatan."\nKelompok ".$source->kode.' digabungkan ke kelompok ini.')
                ?: null,
        ])->save();

        if ($mainId !== null) {
            LaporanSimilarityGroupMember::query()
                ->where('laporan_similarity_group_id', $target->id)
                ->where('laporan_masyarakat_id', $mainId)
                ->update(['peran' => LaporanSimilarityGroupMember::PERAN_UTAMA]);

            LaporanSimilarityGroupMember::query()
                ->where('laporan_similarity_group_id', $target->id)
                ->where('laporan_masyarakat_id', '<>', $mainId)
                ->where('peran', LaporanSimilarityGroupMember::PERAN_UTAMA)
                ->update(['peran' => LaporanSimilarityGroupMember::PERAN_ANGGOTA]);
        }

        $source->forceFill([
            'status' => LaporanSimilarityGroup::STATUS_DIBUBARKAN,
            'main_laporan_id' => null,
            'decided_by' => $source->decided_by ?? $actor?->id,
            'decided_at' => $source->decided_at ?? now(),
            'catatan' => trim((string) $source->catatan."\nKelompok ini digabungkan ke kelompok ".$target->kode.'.')
                ?: null,
        ])->save();
    }

    /**
     * Complete linkage: setiap anggota baru harus mencapai ambang terhadap
     * seluruh anggota kelompok yang sudah ada.
     *
     * @param  array<int, int>  $availableIds
     * @param  array<int, array{left_id: int, right_id: int, score: float}>  $edges
     * @param  array<int, array<int, float>>  $matrix
     * @return array<int, array<int, int>>
     */
    private function completeLinkage(array $availableIds, array $edges, array $matrix, float $minimumScore): array
    {
        $clusters = [];
        $available = array_flip($availableIds);

        usort($edges, static fn (array $left, array $right) => $right['score'] <=> $left['score']);

        foreach ($edges as $edge) {
            $left = (int) $edge['left_id'];
            $right = (int) $edge['right_id'];

            if (! isset($available[$left], $available[$right])) {
                continue;
            }

            $leftCluster = $this->findCluster($clusters, $left);
            $rightCluster = $this->findCluster($clusters, $right);

            if ($leftCluster === null && $rightCluster === null) {
                $clusters[] = [$left, $right];

                continue;
            }

            if ($leftCluster !== null && $rightCluster === null) {
                if ($this->isCompatibleWithCluster($right, $clusters[$leftCluster], $matrix, $minimumScore)) {
                    $clusters[$leftCluster][] = $right;
                }

                continue;
            }

            if ($leftCluster === null && $rightCluster !== null) {
                if ($this->isCompatibleWithCluster($left, $clusters[$rightCluster], $matrix, $minimumScore)) {
                    $clusters[$rightCluster][] = $left;
                }

                continue;
            }

            if ($leftCluster !== $rightCluster && $this->clustersAreCompatible(
                $clusters[$leftCluster],
                $clusters[$rightCluster],
                $matrix,
                $minimumScore
            )) {
                $clusters[$leftCluster] = array_values(array_unique(array_merge(
                    $clusters[$leftCluster],
                    $clusters[$rightCluster]
                )));
                unset($clusters[$rightCluster]);
                $clusters = array_values($clusters);
            }
        }

        return array_values(array_filter($clusters, static fn (array $cluster) => count($cluster) >= 2));
    }

    /**
     * @param  array<int, array<int, int>>  $clusters
     */
    private function findCluster(array $clusters, int $id): ?int
    {
        foreach ($clusters as $index => $cluster) {
            if (in_array($id, $cluster, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $cluster
     * @param  array<int, array<int, float>>  $matrix
     */
    private function isCompatibleWithCluster(int $candidate, array $cluster, array $matrix, float $minimumScore): bool
    {
        foreach ($cluster as $memberId) {
            if ((float) ($matrix[$candidate][$memberId] ?? 0.0) < $minimumScore) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, int>  $leftCluster
     * @param  array<int, int>  $rightCluster
     * @param  array<int, array<int, float>>  $matrix
     */
    private function clustersAreCompatible(
        array $leftCluster,
        array $rightCluster,
        array $matrix,
        float $minimumScore
    ): bool {
        foreach ($leftCluster as $leftId) {
            foreach ($rightCluster as $rightId) {
                if ((float) ($matrix[$leftId][$rightId] ?? 0.0) < $minimumScore) {
                    return false;
                }
            }
        }

        return true;
    }

    private function nextSequence(DprdMember $member): int
    {
        $count = LaporanSimilarityGroup::query()
            ->where('dprd_member_id', $member->id)
            ->count();

        return $count + 1;
    }
}
