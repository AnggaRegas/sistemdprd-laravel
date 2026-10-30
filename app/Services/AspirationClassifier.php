<?php

namespace App\Services;

use App\Models\DprdCommission;
use App\Models\LaporanMasyarakat;
use Illuminate\Support\Collection;

/**
 * Klasifikasi aspirasi berbasis aturan (rule-based weighted keywords).
 *
 * Metode ini tidak memakai AI atau machine learning. Setiap komisi memiliki
 * daftar kata kunci berbobot yang tersimpan di database. Skor dihitung dari
 * jumlah kemunculan kata/frasa (dibatasi) dikali bobotnya.
 */
class AspirationClassifier
{
    public function method(): string
    {
        return (string) config('aspiration_classification.method', 'rule_based_weighted_keywords_v1');
    }

    /**
     * @return array{
     *     status: string,
     *     komisi_id: int|null,
     *     komisi_nama: string|null,
     *     skor: float,
     *     skor_kedua: float,
     *     margin: float,
     *     keywords: array<int, array<string, mixed>>,
     *     skor_detail: array<int, array<string, mixed>>,
     *     metode: string,
     *     alasan: string,
     *     dianalisis_at: \Illuminate\Support\Carbon
     * }
     */
    public function analyze(string $text): array
    {
        $normalized = self::normalize($text);
        $commissions = DprdCommission::query()
            ->active()
            ->orderBy('id')
            ->get();

        $details = [];

        foreach ($commissions as $commission) {
            $keywords = $commission->classificationKeywords();
            $score = 0.0;
            $matches = [];

            foreach ($keywords as $keyword) {
                $occurrences = self::countOccurrences($normalized, $keyword['term']);

                if ($occurrences === 0) {
                    continue;
                }

                $subtotal = $occurrences * $keyword['weight'];
                $score += $subtotal;
                $matches[] = [
                    'term' => $keyword['term'],
                    'weight' => $keyword['weight'],
                    'occurrences' => $occurrences,
                    'subtotal' => $subtotal,
                ];
            }

            $details[] = [
                'komisi_id' => $commission->id,
                'komisi_nama' => $commission->name,
                'skor' => round($score, 2),
                'keywords' => $matches,
            ];
        }

        $ranked = collect($details)
            ->sortByDesc(static fn (array $row) => $row['skor'])
            ->values();

        $top = $ranked->first();
        $second = $ranked->get(1);

        $topScore = (float) ($top['skor'] ?? 0);
        $secondScore = (float) ($second['skor'] ?? 0);
        $margin = $topScore - $secondScore;

        $minimumScore = (float) config('aspiration_classification.minimum_score', 4);
        $minimumMargin = (float) config('aspiration_classification.minimum_margin', 2);

        $recommended = null;

        if ($top !== null && $topScore >= $minimumScore && $margin >= $minimumMargin) {
            $recommended = $top;
        }

        $status = $recommended
            ? LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN
            : LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU;

        return [
            'status' => $status,
            'komisi_id' => $recommended['komisi_id'] ?? null,
            'komisi_nama' => $recommended['komisi_nama'] ?? null,
            'skor' => round($topScore, 2),
            'skor_kedua' => round($secondScore, 2),
            'margin' => round($margin, 2),
            'keywords' => $recommended['keywords'] ?? ($top['keywords'] ?? []),
            'skor_detail' => $ranked->all(),
            'metode' => $this->method(),
            'alasan' => $this->reason($recommended, $top, $topScore, $margin, $minimumScore, $minimumMargin),
            'dianalisis_at' => now(),
        ];
    }

    /**
     * Terapkan hasil analisis ke model laporan. Keputusan final Administrator
     * (komisi_final_*) tidak pernah ditimpa di sini.
     *
     * @param  array<string, mixed>  $result
     */
    public function apply(LaporanMasyarakat $report, array $result): void
    {
        $report->fill([
            'klasifikasi_komisi_id' => $result['komisi_id'] ?? null,
            'klasifikasi_skor' => $result['skor'] ?? 0,
            'klasifikasi_skor_kedua' => $result['skor_kedua'] ?? 0,
            'klasifikasi_keywords' => $result['keywords'] ?? [],
            'klasifikasi_skor_detail' => $result['skor_detail'] ?? [],
            'klasifikasi_metode' => $result['metode'] ?? $this->method(),
            'klasifikasi_status' => $result['status'] ?? LaporanMasyarakat::KLASIFIKASI_PERLU_DITINJAU,
            'klasifikasi_alasan' => $result['alasan'] ?? null,
            'klasifikasi_dianalisis_at' => $result['dianalisis_at'] ?? now(),
        ]);
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * Hitung kemunculan kata/frasa utuh. Teks dan kata kunci sudah
     * dinormalisasi sehingga pencocokan aman dari substring (misalnya
     * "jalan" tidak dihitung dari "perjalanan").
     */
    public static function countOccurrences(string $normalizedText, string $term): int
    {
        if ($normalizedText === '' || $term === '') {
            return 0;
        }

        $needle = ' '.$term.' ';
        $haystack = ' '.$normalizedText.' ';
        $count = substr_count($haystack, $needle);

        $max = (int) config('aspiration_classification.max_occurrences', 3);

        return min($count, max(1, $max));
    }

    /**
     * @param  array<string, mixed>|null  $recommended
     * @param  array<string, mixed>|null  $top
     */
    private function reason(
        ?array $recommended,
        ?array $top,
        float $topScore,
        float $margin,
        float $minimumScore,
        float $minimumMargin
    ): string {
        if ($top === null) {
            return 'Belum ada komisi aktif dengan kata kunci klasifikasi. Administrator perlu meninjau laporan ini secara manual.';
        }

        if ($recommended !== null) {
            return sprintf(
                'Kata kunci yang cocok memberi skor %s pada %s, dengan selisih %s dari komisi berikutnya. Rekomendasi ini tetap dapat dikoreksi Administrator.',
                self::formatScore($topScore),
                $recommended['komisi_nama'],
                self::formatScore($margin)
            );
        }

        if ($topScore < $minimumScore) {
            return sprintf(
                'Skor tertinggi hanya %s (minimum %s), sehingga laporan perlu ditinjau Administrator.',
                self::formatScore($topScore),
                self::formatScore($minimumScore)
            );
        }

        return sprintf(
            'Skor tertinggi %s pada %s, tetapi selisih dengan komisi berikutnya hanya %s (minimum %s). Laporan perlu ditinjau Administrator.',
            self::formatScore($topScore),
            $top['komisi_nama'],
            self::formatScore($margin),
            self::formatScore($minimumMargin)
        );
    }

    private static function formatScore(float $score): string
    {
        return rtrim(rtrim(number_format($score, 2, ',', '.'), '0'), ',');
    }

    /**
     * Data ringkas untuk tampilan form publik.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function publicPayload(array $result): array
    {
        return [
            'status' => $result['status'],
            'status_label' => LaporanMasyarakat::KLASIFIKASI_STATUS_LABELS[$result['status']] ?? $result['status'],
            'komisi_id' => $result['komisi_id'],
            'komisi_nama' => $result['komisi_nama'],
            'skor' => $result['skor'],
            'skor_kedua' => $result['skor_kedua'],
            'margin' => $result['margin'],
            'alasan' => $result['alasan'],
            'keywords' => collect($result['keywords'] ?? [])
                ->pluck('term')
                ->take(8)
                ->values()
                ->all(),
            'metode' => $result['metode'],
        ];
    }

    /**
     * Daftar komisi aktif untuk pilihan final Administrator.
     *
     * @return Collection<int, DprdCommission>
     */
    public function activeCommissions(): Collection
    {
        return DprdCommission::query()->active()->orderBy('id')->get();
    }
}
