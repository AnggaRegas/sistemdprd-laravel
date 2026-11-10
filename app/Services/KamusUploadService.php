<?php

namespace App\Services;

use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Alur unggah Kamus Acuan: baca berkas -> petakan kolom -> pratinjau ->
 * terapkan secara all-or-nothing.
 *
 * Tidak ada data lama yang dihapus atau diubah sebelum seluruh baris baru
 * lolos validasi. Penerapan dilakukan dalam satu transaksi; kegagalan di
 * tengah proses membatalkan seluruh perubahan.
 */
class KamusUploadService
{
    public function __construct(private readonly KamusFileParser $parser)
    {
    }

    public function sessionKey(int $versionId): string
    {
        return 'kamus_upload_preview.'.$versionId;
    }

    /**
     * Batas unggah yang benar-benar berlaku. Ditampilkan transparan agar
     * pengguna tidak mengira unggahan tanpa batas.
     *
     * @return array<string, string|int>
     */
    public function limits(): array
    {
        $appKb = max(1, (int) config('kamus_acuan.upload_max_kb', 20480));
        $uploadMax = (string) ini_get('upload_max_filesize');
        $postMax = (string) ini_get('post_max_size');
        $memory = (string) ini_get('memory_limit');
        $maxExecution = (string) ini_get('max_execution_time');

        $phpUploadBytes = $this->parseBytes($uploadMax);
        $phpPostBytes = $this->parseBytes($postMax);
        $appBytes = $appKb * 1024;
        $effective = min(array_filter([$appBytes, $phpUploadBytes ?: null, $phpPostBytes ?: null]));

        return [
            'app_max_kb' => $appKb,
            'app_max_label' => $this->formatBytes($appBytes),
            'php_upload_max_filesize' => $uploadMax !== '' ? $uploadMax : 'tidak diketahui',
            'php_post_max_size' => $postMax !== '' ? $postMax : 'tidak diketahui',
            'php_memory_limit' => $memory !== '' ? $memory : 'tidak diketahui',
            'php_max_execution_time' => $maxExecution !== '' ? $maxExecution.' detik' : 'tidak diketahui',
            'effective_max_label' => $this->formatBytes((int) $effective),
            'max_rows' => max(1, (int) config('kamus_acuan.upload_max_rows', 10000)),
        ];
    }

    private function parseBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024 * 1024), 1, ',', '.'), '0'), ',').' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1, ',', '.'), '0'), ',').' MB';
        }

        if ($bytes >= 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1, ',', '.'), '0'), ',').' KB';
        }

        return $bytes.' byte';
    }

    public function previewToken(int $versionId, array $rows): string
    {
        return hash('sha256', $versionId.'|'.json_encode($rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(UploadedFile $file, KamusAcuanVersi $version): array
    {
        $parsed = $this->parser->parse($file);
        $mapped = $this->mapRows($parsed['rows'], $version);
        $rows = $mapped['rows'];
        $errors = $mapped['errors'];
        $warnings = array_merge($parsed['warnings'], $mapped['warnings']);

        $token = $this->previewToken($version->id, $rows);

        return [
            'token' => $token,
            'version_id' => $version->id,
            'file_name' => $file->getClientOriginalName(),
            'format' => $parsed['format'],
            'format_label' => $parsed['label'],
            'sheet' => $parsed['sheet'],
            'columns' => $mapped['columns'],
            'header' => $mapped['header'],
            'rows' => $rows,
            'errors' => $errors,
            'warnings' => $warnings,
            'total' => count($rows),
            'baru' => count(array_filter($rows, fn (array $row) => $row['status'] === 'baru')),
            'diperbarui' => count(array_filter($rows, fn (array $row) => $row['status'] === 'diperbarui')),
            'valid' => $errors === [] && $rows !== [],
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array{dibuat: int, diperbarui: int, dinonaktifkan: int, errors: array<int, string>}
     */
    public function apply(KamusAcuanVersi $version, array $preview, User $user, string $mode = 'gabung'): array
    {
        $rows = $preview['rows'] ?? [];

        if (! is_array($rows) || $rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Pratinjau kosong atau sudah kedaluwarsa. Unggah ulang berkas Kamus Acuan.',
            ]);
        }

        if (! empty($preview['errors'])) {
            throw ValidationException::withMessages([
                'file' => 'Pratinjau masih memiliki kesalahan pembacaan. Perbaiki berkas lalu unggah ulang; tidak ada data yang diubah.',
            ]);
        }

        $invalid = array_filter($rows, fn ($row) => ! empty($row['errors'] ?? []) || trim((string) ($row['kode'] ?? '')) === '');

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'file' => 'Masih ada baris yang tidak valid. Perbaiki berkas lalu unggah ulang; tidak ada data yang diubah.',
            ]);
        }

        $kodeList = array_map(fn ($row) => (string) $row['kode'], $rows);
        $duplicates = array_keys(array_filter(array_count_values(array_map('mb_strtoupper', $kodeList)), fn ($count) => $count > 1));

        if ($duplicates !== []) {
            throw ValidationException::withMessages([
                'file' => 'Kode ganda di dalam berkas: '.implode(', ', $duplicates).'. Tidak ada data yang diubah.',
            ]);
        }

        try {
            return DB::transaction(function () use ($version, $rows, $user, $mode, $kodeList) {
                $created = 0;
                $updated = 0;
                $deactivated = 0;

                foreach ($rows as $row) {
                    $kode = trim((string) $row['kode']);
                    $existing = KamusAcuanEntri::withTrashed()
                        ->where('kamus_acuan_versi_id', $version->id)
                        ->where('kode', $kode)
                        ->first();

                    $attributes = [
                        'dprd_commission_id' => $row['dprd_commission_id'] ?? null,
                        'kode' => $kode,
                        'klasifikasi' => $row['klasifikasi'],
                        'uraian' => $row['uraian'] ?: null,
                        'kata_kunci' => $row['kata_kunci'],
                        'sinonim' => $row['sinonim'],
                        'kriteria' => $row['kriteria'] ?: null,
                        'prioritas' => $row['prioritas'] ?: null,
                        'tindak_lanjut' => $row['tindak_lanjut'] ?: null,
                        'is_active' => (bool) $row['is_active'],
                    ];

                    if ($existing) {
                        $before = $existing->trashed() ? null : $existing->toSnapshot();

                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        $existing->update($attributes);
                        $this->log($version->id, $existing->id, 'entri_diubah', $before, $existing->fresh(['versi', 'commission'])?->toSnapshot(), $user, 'Entri diperbarui melalui unggah berkas '.($preview['file_name'] ?? ''));
                        $updated++;

                        continue;
                    }

                    $entry = KamusAcuanEntri::query()->create([
                        'kamus_acuan_versi_id' => $version->id,
                    ] + $attributes);

                    $this->log($version->id, $entry->id, 'entri_dibuat', null, $entry->fresh(['versi', 'commission'])?->toSnapshot(), $user, 'Entri ditambahkan melalui unggah berkas '.($preview['file_name'] ?? ''));
                    $created++;
                }

                if ($mode === 'ganti') {
                    $obsolete = KamusAcuanEntri::query()
                        ->where('kamus_acuan_versi_id', $version->id)
                        ->whereNotIn('kode', $kodeList)
                        ->get();

                    foreach ($obsolete as $entry) {
                        $before = $entry->toSnapshot();
                        $entry->delete();
                        $this->log($version->id, $entry->id, 'entri_dihapus', $before, null, $user, 'Entri tidak ada pada berkas baru sehingga dihapus lunak.');
                        $deactivated++;
                    }
                }

                $this->log(
                    $version->id,
                    null,
                    'impor',
                    null,
                    ['dibuat' => $created, 'diperbarui' => $updated, 'dinonaktifkan' => $deactivated, 'mode' => $mode],
                    $user,
                    'Unggah Kamus Acuan ('.$mode.'): '.$created.' dibuat, '.$updated.' diperbarui, '.$deactivated.' dihapus lunak.'
                );

                return [
                    'dibuat' => $created,
                    'diperbarui' => $updated,
                    'dinonaktifkan' => $deactivated,
                    'errors' => [],
                ];
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'Penerapan berkas gagal dan seluruh perubahan dibatalkan. Tidak ada data Kamus Acuan yang berubah.',
            ]);
        }
    }

    /**
     * @param  array<int, array<int, string>>  $rawRows
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>, warnings: array<int, string>, columns: array<string, string>, header: array<int, string>}
     */
    private function mapRows(array $rawRows, KamusAcuanVersi $version): array
    {
        $headerIndex = $this->findHeaderRow($rawRows);

        if ($headerIndex === null) {
            throw ValidationException::withMessages([
                'file' => 'Baris header tidak ditemukan. Berkas harus memiliki kolom kode/klasifikasi, atau kolom isu/kamus usulan yang dapat dipetakan.',
            ]);
        }

        $headers = $rawRows[$headerIndex];
        $map = $this->headerMapFor($headers);
        $errors = [];
        $warnings = [];

        if (! isset($map['klasifikasi'])) {
            throw ValidationException::withMessages([
                'file' => 'Kolom klasifikasi tidak ditemukan. Sertakan kolom "klasifikasi", "isu", "kamus usulan", atau "isu strategis daerah".',
            ]);
        }

        $existing = KamusAcuanEntri::withTrashed()
            ->where('kamus_acuan_versi_id', $version->id)
            ->get(['kode', 'deleted_at'])
            ->mapWithKeys(fn (KamusAcuanEntri $entry) => [mb_strtoupper($entry->kode) => $entry->trashed()])
            ->all();

        $rows = [];
        $sequence = 0;
        $usedKode = [];

        foreach (array_slice($rawRows, $headerIndex + 1) as $offset => $raw) {
            $lineNumber = $headerIndex + $offset + 2;
            $get = fn (string $key) => isset($map[$key]) ? trim((string) ($raw[$map[$key]] ?? '')) : '';

            $klasifikasi = $get('klasifikasi');

            if ($klasifikasi === '') {
                continue;
            }

            $sequence++;
            $kode = $get('kode');

            if ($kode === '') {
                $kode = $this->generateKode($sequence, $usedKode);
            }

            $kodeUpper = mb_strtoupper($kode);

            if (isset($usedKode[$kodeUpper])) {
                $errors[] = 'Baris '.$lineNumber.': kode '.$kode.' ganda di dalam berkas.';
                continue;
            }

            $usedKode[$kodeUpper] = true;

            if (mb_strlen($kode) > 50) {
                $errors[] = 'Baris '.$lineNumber.': kode maksimal 50 karakter.';
                continue;
            }

            if (mb_strlen($klasifikasi) > 180) {
                $errors[] = 'Baris '.$lineNumber.': klasifikasi maksimal 180 karakter.';
                continue;
            }

            $uraian = $get('uraian');
            $kataKunci = $this->parseTerms($get('kata_kunci'));
            $sinonim = $this->parseTerms($get('sinonim'));

            if ($kataKunci === []) {
                $kataKunci = $this->deriveTerms($klasifikasi, $uraian);
            }

            if ($kataKunci === []) {
                $errors[] = 'Baris '.$lineNumber.': tidak ada kata kunci yang dapat dibentuk dari klasifikasi/uraian.';
                continue;
            }

            $commissionName = $get('komisi');
            $commissionId = $this->resolveCommissionId($commissionName);

            if ($commissionName !== '' && $commissionId === null) {
                $warnings[] = 'Baris '.$lineNumber.': komisi "'.$commissionName.'" tidak cocok dengan data komisi, kolom komisi dikosongkan.';
            }

            $rows[] = [
                'baris' => $lineNumber,
                'kode' => $kode,
                'klasifikasi' => $klasifikasi,
                'uraian' => $uraian,
                'kata_kunci' => $kataKunci,
                'sinonim' => $sinonim,
                'kriteria' => $get('kriteria'),
                'prioritas' => $get('prioritas'),
                'tindak_lanjut' => $get('tindak_lanjut'),
                'komisi' => $commissionName,
                'dprd_commission_id' => $commissionId,
                'is_active' => $this->parseActive($get('is_active')),
                'status' => array_key_exists($kodeUpper, $existing) ? 'diperbarui' : 'baru',
                'errors' => [],
            ];
        }

        if ($rows === []) {
            $errors[] = 'Tidak ada baris data yang dapat diproses dari berkas.';
        }

        $columns = [];

        foreach ($map as $key => $index) {
            $columns[$key] = $headers[$index] ?? $key;
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
            'warnings' => $warnings,
            'columns' => $columns,
            'header' => $headers,
        ];
    }

    /**
     * @param  array<int, array<int, string>>  $rawRows
     */
    private function findHeaderRow(array $rawRows): ?int
    {
        $aliases = $this->aliases();

        foreach (array_slice($rawRows, 0, 10, true) as $index => $row) {
            $matches = 0;

            foreach ($row as $cell) {
                $normalized = $this->normalizeHeader($cell);

                foreach ($aliases as $aliasList) {
                    if ($normalized !== '' && in_array($normalized, $aliasList, true)) {
                        $matches++;
                        break;
                    }
                }
            }

            if ($matches >= 2) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function headerMapFor(array $headers): array
    {
        $map = [];
        $aliases = $this->aliases();

        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);

            if ($normalized === '') {
                continue;
            }

            foreach ($aliases as $key => $aliasList) {
                if (isset($map[$key])) {
                    continue;
                }

                if (in_array($normalized, $aliasList, true)) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        /*
         * Berkas nyata (Kamus Usulan) kadang tidak punya kolom "kode".
         * Kode dibuat otomatis, sedangkan klasifikasi dipetakan ke kolom
         * isu/kamus usulan agar isi tetap terbaca, bukan sekadar disimpan.
         */
        if (! isset($map['klasifikasi'])) {
            foreach (['kamus_usulan', 'isu', 'isu_strategis', 'program_prioritas', 'uraian'] as $key) {
                if (isset($map[$key])) {
                    $map['klasifikasi'] = $map[$key];
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function aliases(): array
    {
        return [
            'kode' => ['kode', 'code', 'kode kamus', 'nomor'],
            'klasifikasi' => ['klasifikasi', 'kategori', 'nama klasifikasi', 'classification'],
            'uraian' => ['uraian', 'deskripsi', 'keterangan', 'definisi operasional', 'definisi'],
            'kata_kunci' => ['kata kunci', 'kata_kunci', 'keywords', 'keyword', 'istilah'],
            'sinonim' => ['sinonim', 'synonym', 'sinonyms'],
            'kriteria' => ['kriteria', 'syarat', 'syarat dan ketentuan', 'parameter'],
            'prioritas' => ['prioritas', 'prioritas pembangunan', 'prioritas daerah'],
            'tindak_lanjut' => ['tindak lanjut', 'tindak_lanjut', 'program kegiatan subkegiatan', 'program/kegiatan/subkegiatan', 'program prioritas', 'program'],
            'komisi' => ['komisi', 'perangkat daerah', 'opd', 'dprd commission', 'commission'],
            'is_active' => ['is active', 'is_active', 'aktif', 'status'],
            'kamus_usulan' => ['kamus usulan', 'usulan'],
            'isu' => ['isu', 'isu strategis', 'isu strategis daerah'],
            'isu_strategis' => ['isu strategis daerah'],
            'program_prioritas' => ['program prioritas'],
            'uraian' => ['uraian', 'deskripsi', 'keterangan', 'definisi operasional', 'definisi'],
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $header) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $header) ?? '');
    }

    /**
     * @param  array<int, string>  $used
     */
    private function generateKode(int $sequence, array $used): string
    {
        $attempt = $sequence;

        do {
            $kode = 'IMP-'.str_pad((string) $attempt, 4, '0', STR_PAD_LEFT);
            $attempt++;
        } while (isset($used[mb_strtoupper($kode)]));

        return $kode;
    }

    /**
     * @return array<int, array{term: string, weight: int}>
     */
    private function parseTerms(string $cell): array
    {
        if (trim($cell) === '') {
            return [];
        }

        $parts = preg_split('/[;\n]+/', $cell) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $weight = 3;
            $term = $part;

            if (str_contains($part, '|')) {
                [$term, $weightRaw] = array_pad(explode('|', $part, 2), 2, '3');
                $term = trim($term);
                $weight = (int) trim($weightRaw);
            } elseif (str_contains($part, ':')) {
                [$term, $weightRaw] = array_pad(explode(':', $part, 2), 2, '3');
                $term = trim($term);
                $weight = (int) trim($weightRaw);
            } else {
                $terms[] = ['term' => $part, 'weight' => 3];
                continue;
            }

            $term = KamusAcuanEntri::normalizeTerm($term);

            if ($term === '') {
                continue;
            }

            $terms[] = ['term' => $term, 'weight' => max(1, min(10, $weight))];
        }

        return KamusAcuanEntri::normalizeTerms($terms);
    }

    /**
     * Bentuk kata kunci dari teks klasifikasi/uraian bila berkas tidak
     * menyediakan kolom kata kunci.
     *
     * @return array<int, array{term: string, weight: int}>
     */
    private function deriveTerms(string $klasifikasi, string $uraian): array
    {
        $stopwords = [
            'dan', 'atau', 'yang', 'untuk', 'dengan', 'pada', 'di', 'ke', 'dari', 'dalam',
            'akan', 'tidak', 'agar', 'oleh', 'ini', 'itu', 'sebagai', 'serta', 'juga',
            'bagi', 'para', 'secara', 'lebih', 'dapat', 'harus', 'adalah', 'terhadap',
            'melalui', 'kepada', 'sehingga', 'karena', 'sesuai', 'bidang', 'urusan',
        ];

        $terms = [];

        foreach ([$klasifikasi => 3, $uraian => 2] as $text => $weight) {
            $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [];

            foreach ($words as $word) {
                $word = trim($word);

                if (mb_strlen($word) < 4 || in_array($word, $stopwords, true)) {
                    continue;
                }

                if (! isset($terms[$word]) || $terms[$word] < $weight) {
                    $terms[$word] = $weight;
                }
            }
        }

        $result = [];

        foreach (array_slice($terms, 0, 12, true) as $term => $weight) {
            $result[] = ['term' => $term, 'weight' => $weight];
        }

        return KamusAcuanEntri::normalizeTerms($result);
    }

    private function resolveCommissionId(string $value): ?int
    {
        if (trim($value) === '') {
            return null;
        }

        $needle = mb_strtolower(trim($value));

        return \App\Models\DprdCommission::query()
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%'])
            ->value('id') ?: null;
    }

    private function parseActive(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        return ! in_array(mb_strtolower(trim($value)), ['0', 'tidak', 'nonaktif', 'inactive', 'false'], true);
    }

    private function log(
        int $versionId,
        ?int $entryId,
        string $action,
        ?array $before,
        ?array $after,
        ?User $user,
        ?string $note = null
    ): void {
        KamusAcuanRiwayat::query()->create([
            'kamus_acuan_versi_id' => $versionId,
            'kamus_acuan_entri_id' => $entryId,
            'aksi' => $action,
            'sebelum' => $before,
            'sesudah' => $after,
            'catatan' => Str::limit((string) $note, 1000),
            'user_id' => $user?->id,
        ]);
    }
}
