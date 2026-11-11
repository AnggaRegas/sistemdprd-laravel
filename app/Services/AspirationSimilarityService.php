<?php

namespace App\Services;

use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use App\Models\LaporanSimilarityResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Deteksi aspirasi mirip memakai dua skor yang harus sama-sama lulus:
 *
 * 1. Skor lokasi  : kemiripan alamat pelapor dan kecamatan tujuan.
 * 2. Skor masalah : TF-IDF + cosine similarity atas ringkasan dan isi aspirasi.
 *
 * Perhitungan hanya membandingkan aspirasi yang ditujukan kepada anggota DPRD
 * yang sama. Identitas pelapor, kontak, dan lampiran tidak dipakai untuk
 * menilai kemiripan; alamat hanya dipakai sebagai faktor lokasi.
 *
 * Selain menyimpan hasil akhir, service ini menyusun snapshot perhitungan
 * (preprocessing, TF-IDF, cosine, dan skor lokasi) agar proses yang benar-benar
 * dijalankan dapat ditampilkan kembali tanpa menghitung ulang.
 */
class AspirationSimilarityService
{
    public const ALGORITHM_VERSION = 'tfidf-cosine-dual-gate-v4';

    /** Jumlah kata dengan bobot tertinggi yang disimpan per vektor pembanding. */
    private const VECTOR_TERM_LIMIT = 30;

    /**
     * Kata negasi yang tidak boleh dibuang pada tahap stopword karena dapat
     * membalik makna aspirasi (mis. "jalan tidak berlubang" vs "jalan
     * berlubang").
     *
     * @var array<int, string>
     */
    private const NEGATION_WORDS = ['tidak', 'tak', 'bukan', 'belum', 'tanpa', 'jangan'];

    /** @var object|null */
    private ?object $stemmer = null;

    /** Cache stemming per proses agar preprocessing tetap cepat. */
    private static array $stemCache = [];

    /**
     * Metadata algoritma yang dipakai bersama oleh service dan pengendali.
     * Nilainya berasal dari fungsi yang benar-benar dipakai perhitungan.
     *
     * @return array<string, mixed>
     */
    public function algorithmMeta(): array
    {
        return [
            'algorithm_version' => self::ALGORITHM_VERSION,
            'threshold_problem' => $this->problemThreshold(),
            'threshold_location' => $this->locationThreshold(),
            'formulas' => $this->formulas(),
            'decision_rule' => $this->decisionRule(),
        ];
    }

    /** Singkatan alamat yang diseragamkan sebelum dibandingkan. */
    private const ADDRESS_REPLACEMENTS = [
        'jl' => 'jalan',
        'jln' => 'jalan',
        'gg' => 'gang',
        'gng' => 'gang',
        'kp' => 'kampung',
        'kmp' => 'kampung',
        'kel' => 'kelurahan',
        'kel.' => 'kelurahan',
        'kec' => 'kecamatan',
        'kec.' => 'kecamatan',
        'no' => 'nomor',
        'nmr' => 'nomor',
        'rt' => 'rt',
        'rw' => 'rw',
        'ds' => 'dusun',
        'dsn' => 'dusun',
        'perum' => 'perumahan',
        'komp' => 'kompleks',
    ];

    /**
     * Kata umum pada alamat. Kata ini tidak dianggap sebagai penanda lokasi
     * spesifik sehingga dua alamat berbeda tidak dianggap mirip hanya karena
     * sama-sama memakai kata "jalan" atau "kelurahan".
     *
     * @var array<int, string>
     */
    private const ADDRESS_GENERIC_WORDS = [
        'jalan', 'gang', 'kelurahan', 'kecamatan', 'desa', 'kampung', 'dusun',
        'lingkungan', 'nomor', 'blok', 'rt', 'rw', 'perumahan', 'kompleks',
        'depan', 'belakang', 'samping', 'sebelah', 'dekat', 'patokan', 'arah',
        'kota', 'kabupaten', 'provinsi', 'indonesia', 'kode', 'pos',
    ];

    /** @var array<int, string> */
    private const STOPWORDS = [
        'ada', 'adalah', 'agar', 'akan', 'akhir', 'aku', 'amat', 'anda', 'antar', 'antara', 'apa', 'apabila',
        'atau', 'atas', 'bahwa', 'bagi', 'bahkan', 'banget', 'baru', 'bawah', 'beberapa', 'begitu', 'belakang',
        'berada', 'berbagai', 'berikan', 'bersama', 'bisa', 'dalam', 'dan', 'dapat', 'dari',
        'daripada', 'dekat', 'demi', 'dengan', 'depan', 'demikian', 'di', 'dia', 'dilakukan', 'dimana', 'diri',
        'dok', 'dua', 'guna', 'hal', 'hampir', 'hanya', 'harus', 'hingga', 'ia', 'ialah', 'itu', 'jadi',
        'jika', 'juga', 'jumlah', 'kami', 'kamu', 'karena', 'ke', 'kecuali', 'kedua', 'kembali',
        'kemudian', 'kepada', 'ketika', 'kita', 'lalu', 'lain', 'lainnya', 'lagi', 'lebih', 'maka',
        'mampu', 'masih', 'masing', 'maupun', 'melalui', 'memang', 'mereka', 'mesti', 'meski', 'mohon',
        'namun', 'nanti', 'nya', 'oleh', 'pada', 'para', 'parah', 'pasti', 'perlu', 'pula', 'pun', 'saat', 'saja',
        'saling', 'sama', 'samping', 'sampai', 'sangat', 'satu', 'saya', 'sebagai', 'sebelah', 'sebelum', 'secara', 'sedang',
        'sedikit', 'sehingga', 'sekali', 'sekitar', 'selain', 'selalu', 'selama', 'seluruh', 'semua', 'sendiri', 'seperti',
        'seputar', 'serta', 'sesuai', 'setelah', 'setiap', 'siapa', 'sini', 'suatu', 'sudah', 'supaya',
        'tepatnya', 'tentang', 'tentu', 'terhadap', 'tersebut', 'tetap', 'tetapi', 'tiap', 'tiga', 'untuk',
        'walau', 'yang', 'yaitu', 'yakni', 'warga', 'tolong',
        'bro', 'gan', 'min', 'kak', 'bang', 'pak', 'bu', 'dong', 'sih', 'nih', 'tuh', 'deh', 'halo',
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
            /*
             * Hapus hasil lama pada kedua arah. Saat laporan diedit, baris
             * lama bisa tersimpan dengan laporan ini sebagai pihak pembanding
             * (id lebih kecil). Kedua arah harus dibersihkan agar tidak ada
             * skor basi yang tertinggal.
             */
            LaporanSimilarityResult::query()
                ->where('laporan_masyarakat_id', $report->id)
                ->orWhere('compared_laporan_masyarakat_id', $report->id)
                ->delete();

            if (! $report->dprd_member_id || blank($this->problemText($report))) {
                return;
            }

            $analysis = $this->analyze($report);

            $this->storePairRows($report->id, $analysis['matches'], LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI);
            $this->storePairRows($report->id, $analysis['manual_review'], LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU);

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
     * Simpan satu arah hasil pasangan mengikuti konvensi lama: laporan dengan
     * id lebih besar menjadi pemilik baris. Skor yang disimpan adalah cosine
     * TF-IDF murni (problem_score), bukan skor gabungan.
     *
     * @param  array<int, array<string, mixed>>  $matches
     */
    private function storePairRows(int $reportId, array $matches, string $level): void
    {
        foreach ($matches as $match) {
            $otherId = (int) $match['id'];
            $newer = max($reportId, $otherId);
            $older = min($reportId, $otherId);

            LaporanSimilarityResult::query()->updateOrCreate(
                [
                    'laporan_masyarakat_id' => $newer,
                    'compared_laporan_masyarakat_id' => $older,
                ],
                [
                    'similarity_score' => (float) ($match['problem_score'] ?? $match['similarity']),
                    'similarity_level' => $level,
                    'evidence' => $match['evidence'] ?? null,
                    'checked_at' => now(),
                ]
            );
        }
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
        $locationThreshold = $this->locationThreshold();
        $problemThreshold = $this->problemThreshold();
        $maxResults = max(1, (int) config('aspiration_similarity.max_results', 10));
        $problemText = $this->problemText($report);
        $preprocessing = $this->preprocessStages($problemText);
        $preprocessing['problem_text_source'] = $this->problemTextSource($report);
        $newTokens = $preprocessing['final_tokens'];

        $candidates = collect();

        if ($report->dprd_member_id && filled($problemText)) {
            /*
             * Seluruh laporan relevan milik anggota yang sama dibandingkan,
             * bukan hanya laporan dengan id lebih kecil. Dengan begitu hasil
             * analisis ulang laporan yang diedit tetap mempertimbangkan
             * laporan yang lebih baru.
             */
            $candidates = LaporanMasyarakat::query()
                ->where('dprd_member_id', $report->dprd_member_id)
                ->whereKeyNot($report->id)
                ->orderBy('id')
                ->get([
                    'id',
                    'nomor_tiket',
                    'ringkasan',
                    'isi',
                    'pelapor_alamat',
                    'kecamatan',
                    'dprd_member_id',
                    'tanggal_laporan',
                    'created_at',
                ]);
        }

        $candidateDetails = collect();
        $newTerms = [];
        $tfIdf = null;
        $cosine = null;
        $highest = null;
        $highestProblem = null;
        $matches = collect();
        $manualReview = collect();

        if ($candidates->isNotEmpty()) {
            $documents = collect([$report->id => $newTokens]);
            foreach ($candidates as $candidate) {
                $documents->put($candidate->id, $this->problemTokens($candidate));
            }

            $vectors = $this->tfIdfVectors($documents);
            $newVector = $vectors[$report->id] ?? [];
            $statistics = $this->corpusStatistics($documents);
            $newMagnitude = $this->magnitude($newVector);
            $tokenCounts = array_count_values($newTokens);
            $tokenCount = count($newTokens);

            $newTerms = $this->termBreakdown($newVector, $tokenCounts, $tokenCount, $statistics);

            $candidateDetails = $candidates
                ->map(function (LaporanMasyarakat $candidate) use (
                    $documents,
                    $vectors,
                    $newVector,
                    $report,
                    $problemThreshold,
                    $locationThreshold
                ) {
                    $candidateVector = $vectors[$candidate->id] ?? [];
                    $details = $this->cosineDetails($newVector, $candidateVector);
                    $problemScore = $details['similarity'];
                    $location = $this->locationAnalysis($report, $candidate);
                    $combined = $this->combinedScore($problemScore, $location['score']);
                    $passes = $this->passesGate($problemScore, $location, $problemThreshold, $locationThreshold);
                    $needsReview = $this->needsManualReview($problemScore, $passes, $problemThreshold);
                    $shared = $this->sharedTerms($newVector, $candidateVector);

                    return [
                        'id' => $candidate->id,
                        'nomor_tiket' => $candidate->nomor_tiket,
                        'ringkasan' => (string) $candidate->ringkasan,
                        'dibuat_at' => $candidate->created_at?->toIso8601String(),
                        // Nilai similarity adalah cosine TF-IDF murni.
                        'similarity' => $problemScore,
                        'problem_score' => $problemScore,
                        'location_score' => $location['score'],
                        'combined_score' => $combined,
                        'location_status' => $location['status'],
                        'similarity_status' => $passes
                            ? LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI
                            : LaporanMasyarakat::SIMILARITY_LEVEL_TIDAK_ADA,
                        'manual_review_required' => $needsReview,
                        'dot_product' => $details['dot_product'],
                        'magnitude_new' => $details['left_magnitude'],
                        'magnitude_candidate' => $details['right_magnitude'],
                        'memenuhi_threshold' => $passes,
                        'token_count' => count($documents[$candidate->id] ?? []),
                        'vector' => $this->topTerms($candidateVector),
                        'evidence' => $this->buildEvidence(
                            $report,
                            $candidate,
                            $location,
                            $problemScore,
                            $combined,
                            $shared,
                            $needsReview
                        ),
                    ];
                })
                ->sortByDesc('problem_score')
                ->values();

            $matches = $candidateDetails
                ->filter(fn (array $candidate) => $candidate['memenuhi_threshold'])
                ->sortByDesc('problem_score')
                ->take($maxResults)
                ->values();

            $manualReview = $candidateDetails
                ->filter(fn (array $candidate) => $candidate['manual_review_required'])
                ->sortByDesc('problem_score')
                ->values();

            $highest = $matches->isEmpty() ? null : (float) $matches->max('problem_score');
            $highestProblem = $candidateDetails->isEmpty()
                ? null
                : (float) $candidateDetails->max('problem_score');

            $tfIdf = [
                'total_documents' => $statistics['total_documents'],
                'formula' => $this->formulas()['tfidf'],
                'new_document' => [
                    'token_count' => $tokenCount,
                    'terms' => $newTerms,
                ],
            ];

            $cosine = [
                'formula' => $this->formulas()['cosine'],
                'magnitude_new' => $newMagnitude,
                'candidates' => $candidateDetails->all(),
            ];
        }

        $snapshot = [
            'algorithm_version' => self::ALGORITHM_VERSION,
            'threshold' => $problemThreshold,
            'threshold_problem' => $problemThreshold,
            'threshold_location' => $locationThreshold,
            'max_results' => $maxResults,
            'formulas' => $this->formulas(),
            'decision_rule' => $this->decisionRule(),
            'preprocessing' => $preprocessing,
            'filter' => [
                'member_id' => $report->dprd_member_id,
                'criteria' => 'Anggota DPRD tujuan sama, seluruh laporan relevan (kecuali laporan ini), diurutkan berdasarkan id.',
                'candidate_count' => $candidates->count(),
                'candidates' => $candidates
                    ->map(fn (LaporanMasyarakat $candidate) => [
                        'id' => $candidate->id,
                        'nomor_tiket' => $candidate->nomor_tiket,
                        'ringkasan' => (string) $candidate->ringkasan,
                        'kecamatan' => $candidate->kecamatan,
                        'dibuat_at' => $candidate->created_at?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'tfidf' => $tfIdf,
            'cosine' => $cosine,
            'result' => [
                'highest_similarity' => $highest,
                'highest_problem_score' => $highestProblem,
                'threshold' => $problemThreshold,
                'threshold_problem' => $problemThreshold,
                'threshold_location' => $locationThreshold,
                'operator' => 'AND',
                'decision_rule' => $this->decisionRule(),
                'is_duplicate' => $matches->isNotEmpty(),
                'matches' => $matches->all(),
                'manual_review' => $manualReview->all(),
            ],
        ];

        return [
            'threshold' => $problemThreshold,
            'problem_threshold' => $problemThreshold,
            'location_threshold' => $locationThreshold,
            'max_results' => $maxResults,
            'candidate_count' => $candidates->count(),
            'highest_similarity' => $highest,
            'highest_problem_score' => $highestProblem,
            'is_duplicate' => $matches->isNotEmpty(),
            'matches' => $matches
                ->map(fn (array $match) => [
                    'id' => $match['id'],
                    'ringkasan' => $match['ringkasan'],
                    'similarity' => $match['problem_score'],
                    'problem_score' => $match['problem_score'],
                    'location_score' => $match['location_score'],
                    'combined_score' => $match['combined_score'],
                    'location_status' => $match['location_status'],
                    'manual_review_required' => false,
                    'evidence' => $match['evidence'],
                ])
                ->all(),
            'manual_review' => $manualReview
                ->map(fn (array $match) => [
                    'id' => $match['id'],
                    'ringkasan' => $match['ringkasan'],
                    'similarity' => $match['problem_score'],
                    'problem_score' => $match['problem_score'],
                    'location_score' => $match['location_score'],
                    'combined_score' => $match['combined_score'],
                    'location_status' => $match['location_status'],
                    'manual_review_required' => true,
                    'evidence' => $match['evidence'],
                ])
                ->all(),
            'snapshot' => $snapshot,
        ];
    }


    /**
     * Hitung pasangan kemiripan untuk seluruh aspirasi satu anggota DPRD.
     *
     * Setiap pasangan dihitung memakai dua skor terpisah:
     * - skor lokasi dari alamat pelapor dan kecamatan tujuan;
     * - skor masalah dari TF-IDF + cosine atas ringkasan dan isi aspirasi.
     *
     * Hanya pasangan yang lulus KEDUA ambang yang disimpan dan diberi label
     * kemiripan tinggi. Pasangan lain tidak ditampilkan sebagai duplikat.
     *
     * @param  Collection<int, LaporanMasyarakat>  $reports
     * @return array<string, mixed>
     */
    public function analyzeCorpus(Collection $reports): array
    {
        $locationThreshold = $this->locationThreshold();
        $problemThreshold = $this->problemThreshold();

        /*
         * Satu korpus hanya berisi laporan milik satu anggota DPRD. Bila
         * pemanggil mengirim koleksi campuran, laporan anggota pertama dipakai
         * sebagai acuan agar tidak pernah ada perbandingan lintas anggota.
         */
        $reports = $reports
            ->filter(fn ($report) => $report instanceof LaporanMasyarakat && $report->dprd_member_id !== null)
            ->unique('id')
            ->values();

        $memberId = $reports->first()?->dprd_member_id;

        if ($memberId !== null) {
            $reports = $reports
                ->filter(fn (LaporanMasyarakat $report) => (int) $report->dprd_member_id === (int) $memberId)
                ->values();
        }

        $documents = [];
        $preprocessed = [];
        $excerpts = [];

        foreach ($reports as $report) {
            $text = $this->problemText($report);
            $stage = $this->preprocessStages($text);
            $stage['problem_text_source'] = $this->problemTextSource($report);
            $documents[$report->id] = $stage['final_tokens'];
            $preprocessed[$report->id] = $stage;
            $excerpts[$report->id] = mb_substr(preg_replace('/\s+/u', ' ', $text) ?: $text, 0, 320);
        }

        $reportLevels = [];

        foreach ($reports as $report) {
            $reportLevels[$report->id] = [
                'id' => $report->id,
                'token_count' => count($documents[$report->id] ?? []),
                'highest_similarity' => null,
                'highest_problem_score' => null,
                'highest_combined_score' => null,
                'level' => LaporanMasyarakat::SIMILARITY_LEVEL_TIDAK_ADA,
                'manual_review_required' => false,
                'manual_review' => [],
            ];
        }

        if ($reports->count() < 2) {
            return [
                'algorithm_version' => self::ALGORITHM_VERSION,
                'threshold_high' => $problemThreshold,
                'threshold_problem' => $problemThreshold,
                'threshold_location' => $locationThreshold,
                'threshold_medium' => $problemThreshold,
                'formulas' => $this->formulas(),
                'decision_rule' => $this->decisionRule(),
                'reports' => $reportLevels,
                'pairs' => [],
                'review_pairs' => [],
                'preprocessing' => $preprocessed,
            ];
        }

        $vectors = $this->tfIdfVectors(collect($documents));
        $ids = array_keys($documents);
        $pairs = [];
        $reviewPairs = [];

        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $leftId = $ids[$i];
                $rightId = $ids[$j];
                $leftReport = $reports->firstWhere('id', $leftId);
                $rightReport = $reports->firstWhere('id', $rightId);

                if (! $leftReport || ! $rightReport) {
                    continue;
                }

                $leftVector = $vectors[$leftId] ?? [];
                $rightVector = $vectors[$rightId] ?? [];
                $cosine = $this->cosineDetails($leftVector, $rightVector);
                $problemScore = $cosine['similarity'];
                $location = $this->locationAnalysis($leftReport, $rightReport);
                $combined = $this->combinedScore($problemScore, $location['score']);
                $passes = $this->passesGate($problemScore, $location, $problemThreshold, $locationThreshold);
                $needsReview = $this->needsManualReview($problemScore, $passes, $problemThreshold);

                /*
                 * Skor cosine tertinggi disimpan terpisah dari skor gabungan
                 * agar tampilan "kemiripan" selalu berasal dari TF-IDF.
                 */
                foreach ([$leftId, $rightId] as $reportId) {
                    $level = &$reportLevels[$reportId];

                    if ($level['highest_problem_score'] === null
                        || $problemScore > $level['highest_problem_score']) {
                        $level['highest_problem_score'] = $problemScore;
                    }

                    if ($level['highest_combined_score'] === null
                        || $combined > $level['highest_combined_score']) {
                        $level['highest_combined_score'] = $combined;
                    }

                    if ($passes && ($level['highest_similarity'] === null
                        || $problemScore > $level['highest_similarity'])) {
                        $level['highest_similarity'] = $problemScore;
                    }

                    unset($level);
                }

                if (! $passes && ! $needsReview) {
                    continue;
                }

                $shared = $this->sharedTerms($leftVector, $rightVector);
                $evidence = $this->buildEvidence(
                    $leftReport,
                    $rightReport,
                    $location,
                    $problemScore,
                    $combined,
                    $shared,
                    $needsReview,
                    $excerpts[$leftId] ?? '',
                    $excerpts[$rightId] ?? ''
                );

                $pair = [
                    'left_id' => $leftId,
                    'right_id' => $rightId,
                    // score adalah cosine TF-IDF, bukan skor gabungan.
                    'score' => round($problemScore, 6),
                    'problem_score' => round($problemScore, 6),
                    'location_score' => round($location['score'], 6),
                    'combined_score' => round($combined, 6),
                    'cosine' => round($problemScore, 6),
                    'level' => $passes
                        ? LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI
                        : LaporanMasyarakat::SIMILARITY_LEVEL_PERLU_TINJAU,
                    'manual_review_required' => ! $passes,
                    'evidence' => $evidence,
                ];

                if ($passes) {
                    $pairs[] = $pair;
                } else {
                    $reviewPairs[] = $pair;

                    foreach ([$leftId, $rightId] as $reportId) {
                        $otherId = (int) $reportId === (int) $leftId ? $rightId : $leftId;

                        $reportLevels[$reportId]['manual_review_required'] = true;
                        $reportLevels[$reportId]['manual_review'][] = [
                            'compared_laporan_id' => $otherId,
                            'problem_score' => round($problemScore, 6),
                            'location_score' => round($location['score'], 6),
                            'combined_score' => round($combined, 6),
                            'location_status' => $location['status'],
                            'reasons' => $evidence['reasons'],
                        ];
                    }
                }
            }
        }

        foreach ($reportLevels as $id => $data) {
            /*
             * Tingkat "tinggi" hanya diberikan bila aspirasi memiliki
             * setidaknya satu pasangan yang lulus kedua ambang. Pasangan yang
             * hanya mirip teks tetapi lokasinya belum terkonfirmasi tetap
             * ditandai untuk peninjauan manual, bukan sebagai duplikat.
             */
            $reportLevels[$id]['level'] = $data['highest_similarity'] !== null
                ? LaporanMasyarakat::SIMILARITY_LEVEL_TINGGI
                : LaporanMasyarakat::SIMILARITY_LEVEL_TIDAK_ADA;
        }

        usort($pairs, static fn (array $left, array $right) => $right['score'] <=> $left['score']);
        usort($reviewPairs, static fn (array $left, array $right) => $right['problem_score'] <=> $left['problem_score']);

        return [
            'algorithm_version' => self::ALGORITHM_VERSION,
            'threshold_high' => $problemThreshold,
            'threshold_problem' => $problemThreshold,
            'threshold_location' => $locationThreshold,
            'threshold_medium' => $problemThreshold,
            'formulas' => $this->formulas(),
            'decision_rule' => $this->decisionRule(),
            'reports' => $reportLevels,
            'pairs' => $pairs,
            'review_pairs' => $reviewPairs,
            'preprocessing' => $preprocessed,
        ];
    }

    /**
     * Teks yang dibandingkan untuk inti permasalahan.
     *
     * Aturan yang dipakai konsisten: isi aspirasi adalah sumber utama karena
     * memuat uraian lengkap. Ringkasan hanya dipakai sebagai cadangan bila isi
     * kosong. Dengan begitu ringkasan dan isi tidak dihitung dua kali, sehingga
     * frekuensi term tetap mencerminkan dokumen yang sebenarnya.
     */
    private function problemText(LaporanMasyarakat $report): string
    {
        $isi = trim((string) $report->isi);

        if ($isi !== '') {
            return $isi;
        }

        return trim((string) $report->ringkasan);
    }

    /**
     * Sumber teks yang benar-benar dipakai pada problemText().
     */
    private function problemTextSource(LaporanMasyarakat $report): string
    {
        return trim((string) $report->isi) !== '' ? 'isi' : 'ringkasan';
    }

    /**
     * Token inti permasalahan diekstrak murni dari teks aspirasi masyarakat
     * tanpa disaring oleh alamat pelapor, sehingga representasi TF-IDF konsisten
     * antarpelapor berbeda.
     *
     * @param  array<string, mixed>|null  $stage
     * @return array<int, string>
     */
    private function problemTokens(LaporanMasyarakat $report, ?array $stage = null): array
    {
        $stage ??= $this->preprocessStages($this->problemText($report));

        return $stage['final_tokens'];
    }

    /**
     * Bandingkan lokasi dua aspirasi.
     *
     * Skor hanya dianggap sah bila alamat cukup jelas dan kecamatan tujuan
     * sama. Alamat yang kosong, terlalu pendek, atau berupa placeholder
     * menghasilkan status "insufficient" sehingga tidak pernah memicu label
     * kemiripan tinggi.
     *
     * @return array<string, mixed>
     */
    private function locationAnalysis(LaporanMasyarakat $left, LaporanMasyarakat $right): array
    {
        $leftRaw = trim((string) $left->pelapor_alamat);
        $rightRaw = trim((string) $right->pelapor_alamat);
        $leftKecamatan = $this->normalizeAddress((string) $left->kecamatan);
        $rightKecamatan = $this->normalizeAddress((string) $right->kecamatan);

        $base = [
            'score' => 0.0,
            'status' => 'ok',
            'left_address' => $leftRaw !== '' ? $leftRaw : null,
            'right_address' => $rightRaw !== '' ? $rightRaw : null,
            'left_kecamatan' => $leftKecamatan !== '' ? $leftKecamatan : null,
            'right_kecamatan' => $rightKecamatan !== '' ? $rightKecamatan : null,
            'left_tokens' => [],
            'right_tokens' => [],
            'shared_tokens' => [],
            'reasons' => [],
        ];

        if ($this->isUnusableAddress($leftRaw) || $this->isUnusableAddress($rightRaw)) {
            $base['status'] = 'insufficient';
            $base['reasons'][] = 'Informasi alamat salah satu aspirasi belum cukup untuk dibandingkan.';

            return $base;
        }

        if ($leftKecamatan === '' || $rightKecamatan === '') {
            $base['status'] = 'insufficient';
            $base['reasons'][] = 'Kecamatan tujuan salah satu aspirasi belum terisi.';

            return $base;
        }

        if ($leftKecamatan !== $rightKecamatan) {
            $base['status'] = 'different_kecamatan';
            $base['reasons'][] = 'Kecamatan berbeda ('.$left->kecamatan.' dan '.$right->kecamatan.').';

            return $base;
        }

        $base['reasons'][] = 'Kecamatan sama ('.$left->kecamatan.').';

        $leftTokens = $this->addressTokens($leftRaw);
        $rightTokens = $this->addressTokens($rightRaw);

        $base['left_tokens'] = $leftTokens;
        $base['right_tokens'] = $rightTokens;

        if ($leftTokens === [] || $rightTokens === []) {
            $base['status'] = 'insufficient';
            $base['reasons'][] = 'Alamat tidak memuat nama lokasi yang dapat dibandingkan.';

            return $base;
        }

        $shared = array_values(array_intersect($leftTokens, $rightTokens));
        $base['shared_tokens'] = $shared;

        $leftNormalized = $this->normalizeAddress($leftRaw);
        $rightNormalized = $this->normalizeAddress($rightRaw);

        if ($leftNormalized === $rightNormalized) {
            $base['score'] = 1.0;
            $base['reasons'][] = 'Alamat pelapor sama setelah dinormalisasi.';

            return $base;
        }

        $leftCount = count($leftTokens);
        $rightCount = count($rightTokens);
        $sharedCount = count($shared);

        $jaccard = $sharedCount / max(1, count(array_unique(array_merge($leftTokens, $rightTokens))));
        $dice = (2 * $sharedCount) / max(1, $leftCount + $rightCount);
        $containment = $sharedCount / max(1, min($leftCount, $rightCount));

        /*
         * Bila seluruh penanda lokasi pada alamat yang lebih pendek juga
         * muncul pada alamat pasangannya, alamat tersebut masih menunjuk ke
         * lokasi yang sama meskipun salah satu pelapor menambahkan detail.
         * Kasus ini tidak boleh dianggap sama dengan alamat yang benar-benar
         * berbeda (misalnya nama jalan lain pada kelurahan yang sama).
         */
        $score = max(
            $jaccard,
            $dice * 0.95,
            $containment >= 1.0 ? 0.90 : 0.0
        );

        $base['score'] = max(0.0, min(1.0, $score));
        $base['reasons'][] = sprintf(
            'Alamat pelapor memiliki %d penanda lokasi yang sama dari %d dan %d penanda.',
            $sharedCount,
            $leftCount,
            $rightCount
        );

        return $base;
    }

    /**
     * Alamat dianggap tidak dapat dipakai bila kosong, terlalu pendek, atau
     * hanya berisi placeholder.
     */
    private function isUnusableAddress(string $address): bool
    {
        $normalized = $this->normalizeAddress($address);

        if (mb_strlen($normalized) < 4) {
            return true;
        }

        return in_array($normalized, [
            'tidak ada',
            'tidak tahu',
            'belum diisi',
            'tanpa alamat',
            'alamat',
            'lainnya',
            'lain lain',
        ], true);
    }

    /**
     * Normalisasi alamat: huruf kecil, tanda baca dibersihkan, singkatan
     * diseragamkan, dan spasi berlebih dirapikan.
     */
    private function normalizeAddress(string $address): string
    {
        $value = mb_strtolower(trim($address));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        $words = $value === '' ? [] : explode(' ', $value);
        $words = array_map(
            static fn (string $word) => self::ADDRESS_REPLACEMENTS[$word] ?? $word,
            $words
        );

        return trim(implode(' ', $words));
    }

    /**
     * Penanda lokasi spesifik pada alamat. Kata umum seperti "jalan" atau
     * "kelurahan" dan angka rumah dibuang agar tidak menaikkan skor secara
     * palsu.
     *
     * @return array<int, string>
     */
    private function addressTokens(string $address): array
    {
        $normalized = $this->normalizeAddress($address);

        if ($normalized === '') {
            return [];
        }

        $tokens = [];

        foreach (explode(' ', $normalized) as $word) {
            if (mb_strlen($word) < 3) {
                continue;
            }

            if (ctype_digit($word)) {
                continue;
            }

            if (in_array($word, self::ADDRESS_GENERIC_WORDS, true)) {
                continue;
            }

            $tokens[$word] = true;
        }

        return array_keys($tokens);
    }

    /**
     * Skor gabungan hanya indikator ringkasan, bukan dasar keputusan.
     * Nilainya adalah rata-rata berbobot problem_score dan location_score
     * tanpa menaikkan salah satu komponen di atas nilai aslinya.
     */
    private function combinedScore(float $problemScore, float $locationScore): float
    {
        $problemWeight = max(0.0, (float) config('aspiration_similarity.weights.problem', 0.60));
        $locationWeight = max(0.0, (float) config('aspiration_similarity.weights.location', 0.40));
        $total = $problemWeight + $locationWeight;

        if ($total <= 0.0) {
            return $problemScore;
        }

        $blended = (($problemScore * $problemWeight) + ($locationScore * $locationWeight)) / $total;

        return max(0.0, min(1.0, $blended));
    }

    /**
     * Gerbang keputusan tunggal yang dipakai analyze() dan analyzeCorpus():
     * skor masalah, status lokasi, dan skor lokasi harus sama-sama lulus.
     *
     * @param  array<string, mixed>  $location
     */
    private function passesGate(
        float $problemScore,
        array $location,
        float $problemThreshold,
        float $locationThreshold
    ): bool {
        return $location['status'] === 'ok'
            && $problemScore >= $problemThreshold
            && (float) $location['score'] >= $locationThreshold;
    }

    /**
     * Teks sudah mirip, tetapi lokasi belum dapat dikonfirmasi. Kasus ini
     * tidak boleh otomatis diberi label kemiripan tinggi.
     */
    private function needsManualReview(
        float $problemScore,
        bool $passes,
        float $problemThreshold
    ): bool {
        return ! $passes && $problemScore >= $problemThreshold;
    }

    /**
     * Rumus yang benar-benar dipakai perhitungan. String ini disimpan pada
     * snapshot agar audit akademis tidak bergantung pada dokumentasi terpisah.
     *
     * @return array<string, string>
     */
    private function formulas(): array
    {
        return [
            'tf' => 'TF(t,d) = jumlah kemunculan term t pada dokumen d / jumlah seluruh token dokumen d',
            'idf' => 'IDF(t) = ln((N + 1) / (DF(t) + 1)) + 1',
            'tfidf' => 'TF-IDF(t,d) = TF(t,d) x IDF(t)',
            'cosine' => 'cos(A,B) = (A . B) / (||A|| x ||B||)',
            'combined' => 'combined_score = (problem_score x 0.60 + location_score x 0.40) / (0.60 + 0.40); indikator saja',
        ];
    }

    private function decisionRule(): string
    {
        return 'problem_score >= threshold_problem DAN location_status = ok DAN location_score >= threshold_location';
    }

    private function locationThreshold(): float
    {
        return max(0.0, min(1.0, (float) config('aspiration_similarity.location_threshold', 0.70)));
    }

    private function problemThreshold(): float
    {
        return max(0.0, min(1.0, (float) config('aspiration_similarity.problem_threshold', 0.70)));
    }

    /**
     * Bukti perhitungan yang disimpan agar keputusan dapat diaudit tanpa
     * menghitung ulang.
     *
     * @param  array<string, mixed>  $location
     * @param  array<int, array<string, mixed>>  $sharedTerms
     * @return array<string, mixed>
     */
    private function buildEvidence(
        LaporanMasyarakat $left,
        LaporanMasyarakat $right,
        array $location,
        float $problemScore,
        float $combinedScore,
        array $sharedTerms,
        bool $manualReviewRequired = false,
        ?string $leftExcerpt = null,
        ?string $rightExcerpt = null
    ): array {
        $reasons = $location['reasons'];
        $reasons[] = sprintf(
            'Skor inti permasalahan %s%%.',
            number_format($problemScore * 100, 1, ',', '.')
        );
        $reasons[] = sprintf(
            'Skor lokasi %s%%.',
            number_format($location['score'] * 100, 1, ',', '.')
        );
        $reasons[] = sprintf(
            'Ambang masalah %s%% dan ambang lokasi %s%%.',
            number_format($this->problemThreshold() * 100, 1, ',', '.'),
            number_format($this->locationThreshold() * 100, 1, ',', '.')
        );

        if ($manualReviewRequired) {
            $reasons[] = match ($location['status']) {
                'insufficient' => 'Skor masalah memenuhi ambang, tetapi informasi alamat belum cukup. Kasus ini memerlukan peninjauan manual.',
                'different_kecamatan' => 'Skor masalah memenuhi ambang, tetapi kecamatan berbeda. Kasus ini memerlukan peninjauan manual.',
                default => 'Skor masalah memenuhi ambang, tetapi skor lokasi belum mencapai ambang. Kasus ini memerlukan peninjauan manual.',
            };
        }

        return [
            'algorithm_version' => self::ALGORITHM_VERSION,
            'method' => 'tfidf-cosine-dual-gate',
            'threshold_location' => $this->locationThreshold(),
            'threshold_problem' => $this->problemThreshold(),
            'decision_rule' => $this->decisionRule(),
            'formulas' => $this->formulas(),
            'location_score' => round($location['score'], 6),
            'problem_score' => round($problemScore, 6),
            'combined_score' => round($combinedScore, 6),
            'location_status' => $location['status'],
            'cosine_similarity' => round($problemScore, 6),
            // Skor teks yang benar-benar dipakai untuk keputusan.
            'final_score' => round($problemScore, 6),
            'manual_review_required' => $manualReviewRequired,
            'location' => $location,
            'shared_terms' => $sharedTerms,
            'shared_term_count' => count($sharedTerms),
            'reasons' => $reasons,
            'left_excerpt' => $leftExcerpt ?? mb_substr(
                preg_replace('/\s+/u', ' ', $this->problemText($left)) ?: '',
                0,
                320
            ),
            'right_excerpt' => $rightExcerpt ?? mb_substr(
                preg_replace('/\s+/u', ' ', $this->problemText($right)) ?: '',
                0,
                320
            ),
        ];
    }

    /**
     * Istilah yang memberi kontribusi terbesar pada dot product kedua vektor.
     *
     * @param  array<string, float>  $left
     * @param  array<string, float>  $right
     * @return array<int, array{term: string, weight_left: float, weight_right: float, contribution: float}>
     */
    private function sharedTerms(array $left, array $right, int $limit = 10): array
    {
        $shared = [];

        foreach ($left as $term => $weight) {
            $other = (float) ($right[$term] ?? 0.0);

            if ($other <= 0.0) {
                continue;
            }

            $shared[] = [
                'term' => (string) $term,
                'weight_left' => round((float) $weight, 6),
                'weight_right' => round($other, 6),
                'contribution' => round((float) $weight * $other, 6),
            ];
        }

        usort($shared, static fn (array $first, array $second) => $second['contribution'] <=> $first['contribution']);

        return array_slice($shared, 0, $limit);
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

            $stemmed = $this->stem($word);

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
            'stemmer' => $this->stemmerName(),
            'negation_words_preserved' => self::NEGATION_WORDS,
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
     * Rincian TF, DF, IDF, dan TF-IDF untuk term pada vektor dokumen baru.
     * Nilainya dihitung dari statistik korpus yang sama dengan tfIdfVectors().
     *
     * @param  array<string, float>  $vector
     * @param  array<string, int>  $tokenCounts
     * @param  array<string, mixed>  $statistics
     * @return array<int, array<string, mixed>>
     */
    private function termBreakdown(
        array $vector,
        array $tokenCounts,
        int $tokenCount,
        array $statistics
    ): array {
        $terms = [];

        foreach ($vector as $term => $weight) {
            $df = (int) ($statistics['document_frequency'][$term] ?? 0);
            $idf = log(($statistics['total_documents'] + 1) / ($df + 1)) + 1;
            $tf = (int) ($tokenCounts[$term] ?? 0);

            $terms[] = [
                'term' => (string) $term,
                'tf' => $tf,
                'tf_normalized' => $tokenCount > 0 ? $tf / $tokenCount : 0.0,
                'df' => $df,
                'idf' => $idf,
                'tfidf' => $weight,
            ];
        }

        usort($terms, static fn (array $left, array $right) => $right['tfidf'] <=> $left['tfidf']);

        return $terms;
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

    /**
     * Stemming Bahasa Indonesia memakai Sastrawi bila tersedia. Cache statis
     * mencegah kata yang sama di-stem berulang kali pada satu proses.
     */
    private function stem(string $word): string
    {
        if (isset(self::$stemCache[$word])) {
            return self::$stemCache[$word];
        }

        $stemmed = $word;

        try {
            if (class_exists(\Sastrawi\Stemmer\StemmerFactory::class)) {
                if ($this->stemmer === null) {
                    $this->stemmer = (new \Sastrawi\Stemmer\StemmerFactory())->createStemmer();
                }

                $stemmed = (string) $this->stemmer->stem($word);
            } else {
                $stemmed = $this->lightStem($word);
            }
        } catch (\Throwable) {
            $stemmed = $this->lightStem($word);
        }

        if ($stemmed === '') {
            $stemmed = $word;
        }

        return self::$stemCache[$word] = $stemmed;
    }

    private function stemmerName(): string
    {
        return class_exists(\Sastrawi\Stemmer\StemmerFactory::class)
            ? 'Sastrawi'
            : 'lightStem fallback';
    }

    /**
     * Fallback konservatif bila Sastrawi tidak dapat dimuat. Ini bukan jalur
     * utama; jalur utama memakai kamus Sastrawi.
     */
    private function lightStem(string $word): string
    {
        // Kata dasar berakhiran -an atau bentuk kata penting yang tidak boleh terpotong
        if (in_array($word, [
            'jalan', 'hujan', 'hutan', 'taman', 'lahan', 'papan', 'hewan',
            'korban', 'rawan', 'beban', 'pesan', 'badan', 'makan', 'depan',
            'pasar', 'pohon',
        ], true)) {
            return $word;
        }

        if ($word === 'jalanan') {
            return 'jalan';
        }

        // Conservative Indonesian suffix normalization. It avoids a third-party
        // dependency while treating simple variants such as perbaikan/perbaiki
        // consistently without modifying stored aspirasi text.
        $word = preg_replace('/(lah|kah|tah|pun|nya)$/u', '', $word) ?: $word;
        $word = preg_replace('/(kan|an|i)$/u', '', $word) ?: $word;

        return mb_strlen($word) >= 3 ? $word : $word;
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

        $similarity = 0.0;
        if ($leftMagnitude > 0.0 && $rightMagnitude > 0.0) {
            $raw = $dot / ($leftMagnitude * $rightMagnitude);
            if ($raw >= 0.999999) {
                $similarity = 1.0;
            } else {
                $similarity = max(0.0, min(1.0, $raw));
            }
        }

        return [
            'similarity' => $similarity,
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
