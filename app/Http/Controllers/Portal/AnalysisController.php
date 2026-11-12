<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    /**
     * Rincian kesamaan satu laporan. Halaman ini hanya menampilkan hasil
     * ringkas: persentase kemiripan, identitas laporan pembanding, isi
     * aspirasi, dan statusnya (aspirasi utama atau serupa).
     *
     * Pasangan yang hanya mirip teks tetapi lokasinya belum terkonfirmasi
     * ditampilkan pada kartu terpisah sebagai bahan peninjauan manual. Pasangan
     * tersebut tidak pernah dihitung sebagai duplikat/kelompok.
     */
    public function show(Request $request, LaporanMasyarakat $laporan): View
    {
        /*
         * Halaman dimaksudkan untuk Superadmin. Staf pendamping melihat
         * ringkasan kemiripan melalui halaman laporan.
         */
        $administrator = $request->user();
        abort_unless($administrator?->isAdministrator(), 403);

        $laporan->load(['member', 'similarityGroup.members.laporan']);

        $analysisRun = LaporanAnalysisRun::query()
            ->where('laporan_masyarakat_id', $laporan->id)
            ->first();

        $group = $laporan->similarityGroup;

        $results = LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $laporan->id)
            ->orWhere('compared_laporan_masyarakat_id', $laporan->id)
            ->get();

        $otherId = fn (LaporanSimilarityResult $result) => (int) $result->laporan_masyarakat_id === (int) $laporan->id
            ? (int) $result->compared_laporan_masyarakat_id
            : (int) $result->laporan_masyarakat_id;

        $resultsByOther = $results->groupBy($otherId);

        $snapshotMatches = collect($analysisRun?->snapshot['matches'] ?? [])->keyBy('id');
        $snapshotReview = collect($analysisRun?->snapshot['manual_review'] ?? [])->keyBy('id');

        /*
         * Baris berlabel tinggi (dan baris lama tanpa label) sebagai daftar kemiripan utama.
         */
        $highResults = $results->filter(fn (LaporanSimilarityResult $result) => in_array(
            $result->similarity_level,
            [LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, null],
            true
        ))->groupBy($otherId);

        $comparisons = $group
            ? $this->comparisonsFromGroup($laporan, $group, $resultsByOther, $snapshotMatches)
            : $this->comparisonsFromResults($laporan, $highResults, $snapshotMatches);

        /*
         * Jika belum terkelompok dan hasil relasi kosong, periksa data snapshot tersimpan.
         */
        if ($comparisons->isEmpty() && $snapshotMatches->isNotEmpty()) {
            $comparisons = $this->comparisonsFromSnapshot($snapshotMatches);
        }

        $reviewResults = $results
            ->filter(fn (LaporanSimilarityResult $result) => $result->similarity_level === LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU)
            ->groupBy($otherId);

        $reviewComparisons = $this->reviewComparisonsFromResults(
            $reviewResults,
            $snapshotReview
        );

        if ($reviewComparisons->isEmpty() && $snapshotReview->isNotEmpty()) {
            $reviewComparisons = $this->comparisonsFromSnapshot($snapshotReview, true);
        }

        return view('analysis.show', [
            'laporan' => $laporan,
            'analysisRun' => $analysisRun,
            'group' => $group,
            'isCurrentMain' => $group !== null
                ? (int) $group->main_laporan_id === (int) $laporan->id
                : false,
            'comparisons' => $comparisons,
            'reviewComparisons' => $reviewComparisons,
        ]);
    }

    /**
     * @param  Collection<int, Collection<int, LaporanSimilarityResult>>  $resultsByOther
     * @param  Collection<int, array<string, mixed>>  $snapshotMatches
     * @return Collection<int, array<string, mixed>>
     */
    private function comparisonsFromGroup(
        LaporanMasyarakat $laporan,
        $group,
        Collection $resultsByOther,
        Collection $snapshotMatches
    ): Collection {
        return $group->members
            ->filter(fn ($member) => $member->laporan !== null
                && (int) $member->laporan->id !== (int) $laporan->id)
            ->map(function ($member) use ($group, $resultsByOther, $snapshotMatches): array {
                $other = $member->laporan;
                $otherId = (int) $other->id;

                $res = $resultsByOther->get($otherId)?->sortByDesc('similarity_score')->first();
                $snap = $snapshotMatches->get($otherId);
                $memberEvidence = is_array($member->evidence) ? $member->evidence : null;

                $metrics = $this->resolveMetrics(
                    $res,
                    $snap,
                    $memberEvidence,
                    $member->similarity_score !== null ? (float) $member->similarity_score : null
                );

                return array_merge([
                    'id' => $otherId,
                    'nomor_tiket' => $other->nomor_tiket,
                    'pelapor_nama' => $other->pelapor_nama,
                    'pelapor_alamat' => $other->pelapor_alamat,
                    'kecamatan' => $other->kecamatan,
                    'created_at' => $other->created_at,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => (int) $group->main_laporan_id === $otherId,
                    'similarity_status' => $other->similarity_status,
                    'similarity_status_label' => $other->similarity_status_label,
                    'level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                    'level_label' => 'Kemiripan Tinggi',
                    'level_tone' => 'success',
                ], $metrics);
            })
            ->sortByDesc(fn (array $row) => $row['score'] ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, Collection<int, LaporanSimilarityResult>>  $grouped
     * @param  Collection<int, array<string, mixed>>  $snapshotMatches
     * @return Collection<int, array<string, mixed>>
     */
    private function comparisonsFromResults(
        LaporanMasyarakat $laporan,
        Collection $grouped,
        Collection $snapshotMatches
    ): Collection {
        if ($grouped->isEmpty()) {
            return collect();
        }

        $reports = LaporanMasyarakat::query()
            ->whereIn('id', $grouped->keys()->all())
            ->get()
            ->keyBy('id');

        return $grouped
            ->map(function (Collection $rows, int $id) use ($reports, $snapshotMatches): ?array {
                $other = $reports->get($id);
                if (! $other) {
                    return null;
                }

                $res = $rows->sortByDesc('similarity_score')->first();
                $snap = $snapshotMatches->get($id);

                $metrics = $this->resolveMetrics($res, $snap, null, (float) $res->similarity_score);

                return array_merge([
                    'id' => (int) $other->id,
                    'nomor_tiket' => $other->nomor_tiket,
                    'pelapor_nama' => $other->pelapor_nama,
                    'pelapor_alamat' => $other->pelapor_alamat,
                    'kecamatan' => $other->kecamatan,
                    'created_at' => $other->created_at,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => false,
                    'similarity_status' => $other->similarity_status,
                    'similarity_status_label' => $other->similarity_status_label,
                    'level' => LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                    'level_label' => 'Kemiripan Tinggi',
                    'level_tone' => 'success',
                ], $metrics);
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * @param  Collection<int, Collection<int, LaporanSimilarityResult>>  $grouped
     * @param  Collection<int, array<string, mixed>>  $snapshotReview
     * @return Collection<int, array<string, mixed>>
     */
    private function reviewComparisonsFromResults(Collection $grouped, Collection $snapshotReview): Collection
    {
        if ($grouped->isEmpty()) {
            return collect();
        }

        $reports = LaporanMasyarakat::query()
            ->whereIn('id', $grouped->keys()->all())
            ->get()
            ->keyBy('id');

        return $grouped
            ->map(function (Collection $rows, int $id) use ($reports, $snapshotReview): ?array {
                $other = $reports->get($id);
                if (! $other) {
                    return null;
                }

                $res = $rows->sortByDesc('similarity_score')->first();
                $snap = $snapshotReview->get($id);

                $metrics = $this->resolveMetrics($res, $snap, null, (float) $res->similarity_score);

                return array_merge([
                    'id' => (int) $other->id,
                    'nomor_tiket' => $other->nomor_tiket,
                    'pelapor_nama' => $other->pelapor_nama,
                    'pelapor_alamat' => $other->pelapor_alamat,
                    'kecamatan' => $other->kecamatan,
                    'created_at' => $other->created_at,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => false,
                    'similarity_status' => $other->similarity_status,
                    'similarity_status_label' => $other->similarity_status_label,
                    'level' => LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU,
                    'level_label' => 'Perlu Tinjauan Manual',
                    'level_tone' => 'warning',
                ], $metrics);
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $snapshotItems
     * @return Collection<int, array<string, mixed>>
     */
    private function comparisonsFromSnapshot(Collection $snapshotItems, bool $isReview = false): Collection
    {
        if ($snapshotItems->isEmpty()) {
            return collect();
        }

        $reports = LaporanMasyarakat::query()
            ->whereIn('id', $snapshotItems->keys()->all())
            ->get()
            ->keyBy('id');

        return $snapshotItems
            ->map(function (array $item, int $id) use ($reports, $isReview): ?array {
                $other = $reports->get($id);
                if (! $other) {
                    return null;
                }

                $metrics = $this->resolveMetrics(
                    null,
                    $item,
                    null,
                    (float) ($item['problem_score'] ?? $item['similarity'] ?? 0)
                );

                return array_merge([
                    'id' => (int) $other->id,
                    'nomor_tiket' => $other->nomor_tiket,
                    'pelapor_nama' => $other->pelapor_nama,
                    'pelapor_alamat' => $other->pelapor_alamat,
                    'kecamatan' => $other->kecamatan,
                    'created_at' => $other->created_at,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => false,
                    'similarity_status' => $other->similarity_status,
                    'similarity_status_label' => $other->similarity_status_label,
                    'level' => $isReview ? LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU : LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI,
                    'level_label' => $isReview ? 'Perlu Tinjauan Manual' : 'Kemiripan Tinggi',
                    'level_tone' => $isReview ? 'warning' : 'success',
                ], $metrics);
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Ekstrak metrik kemiripan dari hasil tersimpan di database tanpa menghitung ulang.
     *
     * @param  LaporanSimilarityResult|null  $result
     * @param  array<string, mixed>|null  $snapshotData
     * @param  array<string, mixed>|null  $memberEvidence
     * @return array<string, mixed>
     */
    private function resolveMetrics(
        ?LaporanSimilarityResult $result,
        ?array $snapshotData,
        ?array $memberEvidence,
        ?float $fallbackScore
    ): array {
        $evidence = [];
        if ($result && is_array($result->evidence) && ! empty($result->evidence)) {
            $evidence = $result->evidence;
        } elseif ($snapshotData && isset($snapshotData['evidence']) && is_array($snapshotData['evidence'])) {
            $evidence = $snapshotData['evidence'];
        } elseif ($memberEvidence && is_array($memberEvidence) && ! empty($memberEvidence)) {
            $evidence = $memberEvidence;
        }

        $cosine = $evidence['cosine_similarity']
            ?? ($result ? $result->similarity_score : null)
            ?? ($snapshotData['problem_score'] ?? $snapshotData['similarity'] ?? null)
            ?? $fallbackScore;

        $problem = $evidence['problem_score']
            ?? ($snapshotData['problem_score'] ?? null)
            ?? $cosine;

        $location = $evidence['location_score']
            ?? ($snapshotData['location_score'] ?? null);

        $combined = $evidence['combined_score']
            ?? ($snapshotData['combined_score'] ?? null);

        $sharedTerms = $evidence['shared_terms']
            ?? ($snapshotData['evidence']['shared_terms'] ?? []);

        $reasons = $evidence['reasons']
            ?? ($snapshotData['evidence']['reasons'] ?? []);

        $locationStatus = $evidence['location_status']
            ?? ($snapshotData['location_status'] ?? null);

        return [
            'score' => $cosine !== null ? (float) $cosine : null,
            'cosine_similarity' => $cosine !== null ? (float) $cosine : null,
            'problem_score' => $problem !== null ? (float) $problem : null,
            'location_score' => $location !== null ? (float) $location : null,
            'combined_score' => $combined !== null ? (float) $combined : null,
            'shared_terms' => is_array($sharedTerms) ? $sharedTerms : [],
            'reasons' => is_array($reasons) ? $reasons : [],
            'location_status' => $locationStatus,
        ];
    }
}
