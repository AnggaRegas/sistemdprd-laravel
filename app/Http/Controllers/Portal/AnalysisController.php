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

        /*
         * Hanya baris berlabel tinggi (dan baris lama tanpa label) yang dipakai
         * sebagai daftar kemiripan. Skor yang ditampilkan adalah cosine TF-IDF
         * yang tersimpan, bukan skor gabungan.
         */
        $pairScores = $results
            ->filter(fn (LaporanSimilarityResult $result) => in_array(
                $result->similarity_level,
                [LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI, null],
                true
            ))
            ->groupBy($otherId)
            ->map(fn (Collection $rows) => (float) $rows->max('similarity_score'));

        $comparisons = $group
            ? $this->comparisonsFromGroup($laporan, $group, $pairScores)
            : $this->comparisonsFromResults($laporan, $pairScores);

        $reviewComparisons = $this->reviewComparisonsFromResults(
            $results
                ->filter(fn (LaporanSimilarityResult $result) => $result->similarity_level === LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU)
                ->groupBy($otherId)
        );

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
     * @param  Collection<int, float>  $pairScores
     * @return Collection<int, array<string, mixed>>
     */
    private function comparisonsFromGroup(
        LaporanMasyarakat $laporan,
        $group,
        Collection $pairScores
    ): Collection {
        return $group->members
            ->filter(fn ($member) => $member->laporan !== null
                && (int) $member->laporan->id !== (int) $laporan->id)
            ->map(function ($member) use ($group, $pairScores): array {
                $other = $member->laporan;
                $score = $pairScores->get((int) $other->id);

                if ($score === null && $member->similarity_score !== null) {
                    $score = (float) $member->similarity_score;
                }

                return [
                    'id' => (int) $other->id,
                    'pelapor_nama' => $other->pelapor_nama,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => (int) $group->main_laporan_id === (int) $other->id,
                    'score' => $score,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['score'] ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, float>  $pairScores
     * @return Collection<int, array<string, mixed>>
     */
    private function comparisonsFromResults(LaporanMasyarakat $laporan, Collection $pairScores): Collection
    {
        if ($pairScores->isEmpty()) {
            return collect();
        }

        $reports = LaporanMasyarakat::query()
            ->whereIn('id', $pairScores->keys()->all())
            ->get()
            ->keyBy('id');

        return $pairScores
            ->map(function (float $score, int $id) use ($reports): ?array {
                $other = $reports->get($id);

                if (! $other) {
                    return null;
                }

                return [
                    'id' => (int) $other->id,
                    'pelapor_nama' => $other->pelapor_nama,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'is_main' => false,
                    'score' => $score,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Pasangan yang perlu ditinjau manusia: skor masalah memenuhi ambang,
     * tetapi lokasi belum dapat dikonfirmasi (alamat tidak lengkap, kecamatan
     * berbeda, atau skor lokasi di bawah ambang).
     *
     * @param  Collection<int, Collection<int, LaporanSimilarityResult>>  $grouped
     * @return Collection<int, array<string, mixed>>
     */
    private function reviewComparisonsFromResults(Collection $grouped): Collection
    {
        if ($grouped->isEmpty()) {
            return collect();
        }

        $reports = LaporanMasyarakat::query()
            ->whereIn('id', $grouped->keys()->all())
            ->get()
            ->keyBy('id');

        return $grouped
            ->map(function (Collection $rows, int $id) use ($reports): ?array {
                $other = $reports->get($id);

                if (! $other) {
                    return null;
                }

                $result = $rows->sortByDesc('similarity_score')->first();
                $evidence = is_array($result->evidence) ? $result->evidence : [];

                return [
                    'id' => (int) $other->id,
                    'nomor_tiket' => $other->nomor_tiket,
                    'pelapor_nama' => $other->pelapor_nama,
                    'ringkasan' => $other->ringkasan,
                    'isi' => $other->isi,
                    'pelapor_alamat' => $other->pelapor_alamat,
                    'kecamatan' => $other->kecamatan,
                    'score' => (float) $result->similarity_score,
                    'problem_score' => isset($evidence['problem_score']) ? (float) $evidence['problem_score'] : (float) $result->similarity_score,
                    'location_score' => isset($evidence['location_score']) ? (float) $evidence['location_score'] : null,
                    'combined_score' => isset($evidence['combined_score']) ? (float) $evidence['combined_score'] : null,
                    'location_status' => $evidence['location_status'] ?? null,
                    'reasons' => $evidence['reasons'] ?? [],
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }
}
