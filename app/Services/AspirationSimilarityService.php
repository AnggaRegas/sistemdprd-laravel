<?php

namespace App\Services;

use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TF-IDF dan Cosine Similarity untuk indikasi aspirasi serupa.
 *
 * Perhitungan hanya memakai isi aspirasi serta kandidat laporan terdahulu yang
 * memiliki anggota DPRD tujuan sama. Identitas, alamat, kontak, dan lampiran
 * dengan sengaja tidak pernah diproses di sini.
 *
 * Selain menyimpan hasil akhir, service ini menyusun snapshot perhitungan
 * (preprocessing, TF-IDF, cosine) agar proses yang benar-benar dijalankan dapat
 * ditampilkan kembali tanpa menghitung ulang memakai dokumen terbaru.
 */
class AspirationSimilarityService
{
    public const ALGORITHM_VERSION = 'tfidf-cosine-v1';

    /** Jumlah kata dengan bobot tertinggi yang disimpan per vektor pembanding. */
    private const VECTOR_TERM_LIMIT = 30;

    /** @var array<int, string> */
    private const STOPWORDS = [
        'ada', 'adalah', 'agar', 'akan', 'akhir', 'aku', 'anda', 'antar', 'antara', 'apa', 'apabila',
        'atau', 'atas', 'bahwa', 'bagi', 'bahkan', 'baru', 'bawah', 'beberapa', 'begitu', 'belum',
        'berada', 'berbagai', 'berikan', 'bersama', 'bisa', 'bukan', 'dalam', 'dan', 'dapat', 'dari',
        'daripada', 'demi', 'dengan', 'depan', 'demikian', 'di', 'dia', 'dilakukan', 'dimana', 'diri',
        'dok', 'dua', 'guna', 'hal', 'hampir', 'hanya', 'harus', 'hingga', 'ia', 'ialah', 'itu', 'jadi',
        'jangan', 'jika', 'juga', 'jumlah', 'kami', 'kamu', 'karena', 'ke', 'kecuali', 'kedua', 'kembali',
        'kemudian', 'kepada', 'ketika', 'kita', 'lalu', 'lain', 'lainnya', 'lagi', 'lebih', 'maka',
        'mampu', 'masih', 'masing', 'maupun', 'melalui', 'memang', 'mereka', 'mesti', 'meski', 'mohon',
        'namun', 'nanti', 'nya', 'oleh', 'pada', 'para', 'pasti', 'perlu', 'pula', 'pun', 'saat', 'saja',
        'saling', 'sama', 'sampai', 'sangat', 'satu', 'saya', 'sebagai', 'sebelum', 'secara', 'sedang',
        'sedikit', 'sehingga', 'selain', 'selalu', 'selama', 'seluruh', 'semua', 'sendiri', 'seperti',
        'serta', 'sesuai', 'setelah', 'setiap', 'siapa', 'sini', 'suatu', 'sudah', 'supaya', 'tak', 'tanpa',
        'tentang', 'tentu', 'terhadap', 'tersebut', 'tetap', 'tetapi', 'tiap', 'tidak', 'tiga', 'untuk',
        'walau', 'yang', 'yaitu', 'yakni', 'warga', 'kami', 'kami', 'kami', 'mohon', 'tolong',
    ];

    /**
     * Hitung ulang hasil laporan baru dan hanya simpan kandidat yang mencapai
     * ambang konfigurasi. Metode ini idempoten agar aman dipanggil ulang.
     *
     * Snapshot detail ikut disimpan pada transaksi yang sama sehingga riwayat
     * analisis dapat dibuka kembali apa adanya, meskipun dokumen bertambah.
     */
    public function analyzeAndStore(LaporanMasyarakat $report): void
    {
        DB::transaction(function () use ($report) {
            LaporanSimilarityResult::query()
                ->where('laporan_masyarakat_id', $report->id)
                ->delete();

            if (! $report->dprd_member_id || blank($report->isi)) {
                return;
            }

            $analysis = $this->analyze($report);

            foreach ($analysis['matches'] as $match) {
                LaporanSimilarityResult::query()->create([
                    'laporan_masyarakat_id' => $report->id,
                    'compared_laporan_masyarakat_id' => $match['id'],
                    'similarity_score' => $match['similarity'],
                    'checked_at' => now(),
                ]);
            }

            LaporanAnalysisRun::query()->updateOrCreate(
                ['laporan_masyarakat_id' => $report->id],
                [
                    'dprd_member_id' => $report->dprd_member_id,
                    'threshold' => $analysis['threshold'],
                    'max_results' => $analysis['max_results'],
                    'candidate_count' => $analysis['candidate_count'],
                    'highest_similarity' => $analysis['highest_similarity'],
                    'is_duplicate' => $analysis['is_duplicate'],
                    'has_detail' => true,
                    'algorithm_version' => self::ALGORITHM_VERSION,
                    'analyzed_at' => now(),
                    'snapshot' => $analysis['snapshot'],
                ]
            );
        });
    }

    /**
     * Jalankan pipeline yang sama dengan analyzeAndStore, lalu kembalikan
     * rincian setiap tahap. Hasil perhitungan tidak diubah oleh pemanggilan
     * metode ini.
     *
     * @return array<string, mixed>
     */
    public function analyze(LaporanMasyarakat $report): array
    {
        $threshold = max(0.0, min(1.0, (float) config('aspiration_similarity.threshold', 0.80)));
        $maxResults = max(1, (int) config('aspiration_similarity.max_results', 5));
        $preprocessing = $this->preprocessStages((string) $report->isi);

        $candidates = collect();

        if ($report->dprd_member_id && filled($report->isi)) {
            $candidates = LaporanMasyarakat::query()
                ->where('dprd_member_id', $report->dprd_member_id)
                ->whereKeyNot($report->id)
                ->where('id', '<', $report->id)
                ->whereNotNull('isi')
                ->where('isi', '<>', '')
                ->orderBy('id')
                ->get(['id', 'ringkasan', 'isi', 'dprd_member_id', 'created_at']);
        }

        $candidateDetails = collect();
        $newTerms = [];
        $tfIdf = null;
        $cosine = null;
        $highest = null;
        $matches = collect();

        if ($candidates->isNotEmpty()) {
            $documents = collect([$report->id => $preprocessing['final_tokens']]);
            foreach ($candidates as $candidate) {
                $documents->put($candidate->id, $this->tokens((string) $candidate->isi));
            }

            $vectors = $this->tfIdfVectors($documents);
            $newVector = $vectors[$report->id] ?? [];
            $statistics = $this->corpusStatistics($documents);
            $newMagnitude = $this->magnitude($newVector);

            $tokenCounts = array_count_values($preprocessing['final_tokens']);
            $tokenCount = count($preprocessing['final_tokens']);

            foreach ($newVector as $term => $weight) {
                $df = $statistics['document_frequency'][$term] ?? 0;
                $idf = log(($statistics['total_documents'] + 1) / ($df + 1)) + 1;
                $tf = $tokenCounts[$term] ?? 0;

                $newTerms[] = [
                    'term' => (string) $term,
                    'tf' => $tf,
                    'tf_normalized' => $tokenCount > 0 ? $tf / $tokenCount : 0.0,
                    'df' => $df,
                    'idf' => $idf,
                    'tfidf' => $weight,
                ];
            }

            usort($newTerms, static fn (array $left, array $right) => $right['tfidf'] <=> $left['tfidf']);

            $candidateDetails = $candidates
                ->map(function (LaporanMasyarakat $candidate) use ($vectors, $newVector, $newMagnitude, $threshold) {
                    $details = $this->cosineDetails($newVector, $vectors[$candidate->id] ?? []);

                    return [
                        'id' => $candidate->id,
                        'ringkasan' => (string) $candidate->ringkasan,
                        'dibuat_at' => $candidate->created_at?->toIso8601String(),
                        'similarity' => $details['similarity'],
                        'dot_product' => $details['dot_product'],
                        'magnitude_new' => $details['left_magnitude'],
                        'magnitude_candidate' => $details['right_magnitude'],
                        'memenuhi_threshold' => $details['similarity'] >= $threshold,
                        'token_count' => count($this->tokens((string) $candidate->isi)),
                        'vector' => $this->topTerms($vectors[$candidate->id] ?? []),
                    ];
                })
                ->sortByDesc('similarity')
                ->values();

            $matches = $candidateDetails
                ->filter(fn (array $candidate) => $candidate['memenuhi_threshold'])
                ->sortByDesc('similarity')
                ->take($maxResults)
                ->values();

            $highest = $candidateDetails->isEmpty()
                ? null
                : (float) $candidateDetails->max('similarity');

            $tfIdf = [
                'total_documents' => $statistics['total_documents'],
                'new_document' => [
                    'token_count' => $tokenCount,
                    'terms' => $newTerms,
                ],
            ];

            $cosine = [
                'magnitude_new' => $newMagnitude,
                'candidates' => $candidateDetails->all(),
            ];
        }

        return [
            'threshold' => $threshold,
            'max_results' => $maxResults,
            'candidate_count' => $candidates->count(),
            'highest_similarity' => $highest,
            'is_duplicate' => $matches->isNotEmpty(),
            'matches' => $matches
                ->map(fn (array $match) => [
                    'id' => $match['id'],
                    'ringkasan' => $match['ringkasan'],
                    'similarity' => $match['similarity'],
                ])
                ->all(),
            'snapshot' => [
                'algorithm_version' => self::ALGORITHM_VERSION,
                'threshold' => $threshold,
                'max_results' => $maxResults,
                'preprocessing' => $preprocessing,
                'filter' => [
                    'member_id' => $report->dprd_member_id,
                    'criteria' => 'Anggota DPRD tujuan sama, id laporan lebih kecil, isi tidak kosong, diurutkan berdasarkan id.',
                    'candidate_count' => $candidates->count(),
                    'candidates' => $candidates
                        ->map(fn (LaporanMasyarakat $candidate) => [
                            'id' => $candidate->id,
                            'ringkasan' => (string) $candidate->ringkasan,
                            'dibuat_at' => $candidate->created_at?->toIso8601String(),
                        ])
                        ->all(),
                ],
                'tfidf' => $tfIdf,
                'cosine' => $cosine,
                'result' => [
                    'highest_similarity' => $highest,
                    'threshold' => $threshold,
                    'operator' => '>=',
                    'is_duplicate' => $matches->isNotEmpty(),
                    'matches' => $matches->all(),
                ],
            ],
        ];
    }

    /**
     * Tahapan preprocessing yang benar-benar diterapkan service ini.
     *
     * @return array<string, mixed>
     */
    private function preprocessStages(string $text): array
    {
        $caseFolded = mb_strtolower($text);
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $caseFolded) ?? '';
        $parts = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $removed = [];
        $changes = [];
        $tokens = [];

        foreach ($parts as $word) {
            if (mb_strlen($word) < 3) {
                $removed[] = [
                    'word' => $word,
                    'reason' => 'Kurang dari 3 huruf',
                ];

                continue;
            }

            if (in_array($word, self::STOPWORDS, true)) {
                $removed[] = [
                    'word' => $word,
                    'reason' => 'Stopword',
                ];

                continue;
            }

            $stemmed = $this->lightStem($word);

            if ($stemmed !== $word) {
                $changes[] = [
                    'from' => $word,
                    'to' => $stemmed,
                ];
            }

            $tokens[] = $stemmed;
        }

        return [
            'raw' => $text,
            'case_folding' => $caseFolded,
            'cleaning' => $cleaned,
            'tokens' => $parts,
            'removed' => $removed,
            'stemming_changes' => $changes,
            'final_tokens' => $tokens,
        ];
    }

    /**
     * @param Collection<int|string, array<int, string>> $documents
     * @return array<int|string, array<string, float>>
     */
    private function tfIdfVectors(Collection $documents): array
    {
        $totalDocuments = max(1, $documents->count());
        $documentFrequency = [];

        foreach ($documents as $tokens) {
            foreach (array_unique($tokens) as $term) {
                $documentFrequency[$term] = ($documentFrequency[$term] ?? 0) + 1;
            }
        }

        $vectors = [];
        foreach ($documents as $id => $tokens) {
            $termFrequency = array_count_values($tokens);
            $tokenCount = count($tokens);
            $vector = [];

            if ($tokenCount > 0) {
                foreach ($termFrequency as $term => $count) {
                    // Smoothed IDF keeps a stable value for small report sets.
                    $idf = log(($totalDocuments + 1) / (($documentFrequency[$term] ?? 0) + 1)) + 1;
                    $vector[$term] = ($count / $tokenCount) * $idf;
                }
            }

            $vectors[$id] = $vector;
        }

        return $vectors;
    }

    /**
     * Statistik korpus yang dipakai pada tabel TF-IDF. Perhitungannya identik
     * dengan tfIdfVectors agar nilai yang ditampilkan sama dengan yang dipakai.
     *
     * @param Collection<int|string, array<int, string>> $documents
     * @return array{document_frequency: array<string, int>, total_documents: int, token_counts: array<int|string, int>}
     */
    private function corpusStatistics(Collection $documents): array
    {
        $documentFrequency = [];
        $tokenCounts = [];

        foreach ($documents as $id => $tokens) {
            $tokenCounts[$id] = count($tokens);

            foreach (array_unique($tokens) as $term) {
                $documentFrequency[$term] = ($documentFrequency[$term] ?? 0) + 1;
            }
        }

        return [
            'document_frequency' => $documentFrequency,
            'total_documents' => max(1, $documents->count()),
            'token_counts' => $tokenCounts,
        ];
    }

    /** @return array<int, string> */
    private function tokens(string $text): array
    {
        return $this->preprocessStages($text)['final_tokens'];
    }

    private function lightStem(string $word): string
    {
        // Conservative Indonesian suffix normalization. It avoids a third-party
        // dependency while treating simple variants such as perbaikan/perbaiki
        // consistently without modifying stored aspirasi text.
        $word = preg_replace('/(lah|kah|tah|pun|nya)$/u', '', $word) ?: $word;
        $word = preg_replace('/(kan|an|i)$/u', '', $word) ?: $word;

        return mb_strlen($word) >= 3 ? $word : $word;
    }

    /**
     * @param array<string, float> $left
     * @param array<string, float> $right
     */
    private function cosineSimilarity(array $left, array $right): float
    {
        return $this->cosineDetails($left, $right)['similarity'];
    }

    /**
     * Hitung komponen cosine similarity tanpa mengubah rumus dan urutan
     * operasi yang sudah dipakai sistem.
     *
     * @param array<string, float> $left
     * @param array<string, float> $right
     * @return array{similarity: float, dot_product: float, left_magnitude: float, right_magnitude: float}
     */
    private function cosineDetails(array $left, array $right): array
    {
        if ($left === [] || $right === []) {
            return [
                'similarity' => 0.0,
                'dot_product' => 0.0,
                'left_magnitude' => 0.0,
                'right_magnitude' => 0.0,
            ];
        }

        $dot = 0.0;
        foreach ($left as $term => $weight) {
            $dot += $weight * ($right[$term] ?? 0.0);
        }

        $leftMagnitude = $this->magnitude($left);
        $rightMagnitude = $this->magnitude($right);

        return [
            'similarity' => $leftMagnitude > 0.0 && $rightMagnitude > 0.0
                ? max(0.0, min(1.0, $dot / ($leftMagnitude * $rightMagnitude)))
                : 0.0,
            'dot_product' => $dot,
            'left_magnitude' => $leftMagnitude,
            'right_magnitude' => $rightMagnitude,
        ];
    }

    /**
     * @param array<string, float> $vector
     */
    private function magnitude(array $vector): float
    {
        return sqrt(array_sum(array_map(static fn (float $weight) => $weight ** 2, $vector)));
    }

    /**
     * @param array<string, float> $vector
     * @return array<int, array{term: string, weight: float}>
     */
    private function topTerms(array $vector, int $limit = self::VECTOR_TERM_LIMIT): array
    {
        arsort($vector);

        $terms = [];
        foreach (array_slice($vector, 0, $limit, true) as $term => $weight) {
            $terms[] = [
                'term' => (string) $term,
                'weight' => $weight,
            ];
        }

        return $terms;
    }
}
