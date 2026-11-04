<?php

namespace App\Services;

use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityResult;
use Illuminate\Support\Collection;

/**
 * TF-IDF dan Cosine Similarity untuk indikasi aspirasi serupa.
 *
 * Perhitungan hanya memakai isi aspirasi serta kandidat laporan terdahulu yang
 * memiliki anggota DPRD tujuan sama. Identitas, alamat, kontak, dan lampiran
 * dengan sengaja tidak pernah diproses di sini.
 */
class AspirationSimilarityService
{
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
     */
    public function analyzeAndStore(LaporanMasyarakat $report): void
    {
        LaporanSimilarityResult::query()
            ->where('laporan_masyarakat_id', $report->id)
            ->delete();

        if (! $report->dprd_member_id || blank($report->isi)) {
            return;
        }

        $candidates = LaporanMasyarakat::query()
            ->where('dprd_member_id', $report->dprd_member_id)
            ->whereKeyNot($report->id)
            ->where('id', '<', $report->id)
            ->whereNotNull('isi')
            ->where('isi', '<>', '')
            ->orderBy('id')
            ->get(['id', 'isi']);

        if ($candidates->isEmpty()) {
            return;
        }

        $documents = collect([$report->id => $this->tokens((string) $report->isi)]);
        foreach ($candidates as $candidate) {
            $documents->put($candidate->id, $this->tokens((string) $candidate->isi));
        }

        $vectors = $this->tfIdfVectors($documents);
        $newVector = $vectors[$report->id] ?? [];
        $threshold = max(0.0, min(1.0, (float) config('aspiration_similarity.threshold', 0.80)));
        $maxResults = max(1, (int) config('aspiration_similarity.max_results', 5));

        $matches = $candidates
            ->map(function (LaporanMasyarakat $candidate) use ($vectors, $newVector) {
                return [
                    'compared_laporan_masyarakat_id' => $candidate->id,
                    'similarity_score' => $this->cosineSimilarity($newVector, $vectors[$candidate->id] ?? []),
                ];
            })
            ->filter(fn (array $match) => $match['similarity_score'] >= $threshold)
            ->sortByDesc('similarity_score')
            ->take($maxResults)
            ->values();

        foreach ($matches as $match) {
            LaporanSimilarityResult::query()->create([
                'laporan_masyarakat_id' => $report->id,
                'compared_laporan_masyarakat_id' => $match['compared_laporan_masyarakat_id'],
                'similarity_score' => $match['similarity_score'],
                'checked_at' => now(),
            ]);
        }
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

    /** @return array<int, string> */
    private function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map(function (string $word) {
            if (mb_strlen($word) < 3 || in_array($word, self::STOPWORDS, true)) {
                return null;
            }

            return $this->lightStem($word);
        }, $parts)));
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
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $dot = 0.0;
        foreach ($left as $term => $weight) {
            $dot += $weight * ($right[$term] ?? 0.0);
        }

        $leftMagnitude = sqrt(array_sum(array_map(static fn (float $weight) => $weight ** 2, $left)));
        $rightMagnitude = sqrt(array_sum(array_map(static fn (float $weight) => $weight ** 2, $right)));

        return $leftMagnitude > 0.0 && $rightMagnitude > 0.0
            ? max(0.0, min(1.0, $dot / ($leftMagnitude * $rightMagnitude)))
            : 0.0;
    }
}
