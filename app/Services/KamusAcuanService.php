<?php

namespace App\Services;

use App\Models\AktivitasSistem;
use App\Models\DprdCommission;
use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\KamusKriteria;
use App\Models\LaporanKamusHasil;
use App\Models\LaporanKamusVerifikasi;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mesin Kamus Acuan.
 *
 * Tanggung jawab utama:
 * - mencocokkan teks aspirasi dengan entri kamus memakai kata/frasa utuh;
 * - menyimpan hasil otomatis sebagai baris baru (tidak menimpa riwayat);
 * - menyimpan keputusan manual Super Admin secara terpisah;
 * - mencatat perubahan kamus pada tabel riwayat.
 *
 * Fungsi ini tidak pernah mengubah alur persetujuan, disposisi, atau Pokir.
 */
class KamusAcuanService
{
    public function method(): string
    {
        return (string) config('kamus_acuan.method', 'kamus_acuan_whole_word_v1');
    }

    public function activeVersion(): ?KamusAcuanVersi
    {
        return KamusAcuanVersi::aktif();
    }

    /**
     * Teks yang diperiksa: isi aspirasi lalu ringkasan sebagai pelengkap.
     * Kode tiket, nama pelapor, dan alamat tidak pernah ikut dicocokkan.
     */
    public static function reportText(LaporanMasyarakat $report): string
    {
        $parts = [];

        foreach ([$report->isi, $report->ringkasan] as $part) {
            $part = trim((string) $part);

            if ($part !== '' && ! in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Evaluasi teks tanpa menyimpan apa pun.
     *
     * @return array{
     *     status: string,
     *     versi: KamusAcuanVersi|null,
     *     teks: string,
     *     alasan: string,
     *     kandidat: array<int, array<string, mixed>>,
     *     entri_utama: KamusAcuanEntri|null,
     *     prioritas: string|null
     * }
     */
    public function evaluateText(string $text, ?KamusAcuanVersi $version = null): array
    {
        $version ??= $this->activeVersion();
        $normalized = KamusAcuanEntri::normalizeTerm($text);

        if (! $version) {
            return [
                'status' => LaporanKamusHasil::STATUS_BELUM_DIPERIKSA,
                'versi' => null,
                'teks' => $text,
                'alasan' => 'Belum ada versi Kamus Acuan yang aktif. Tetapkan satu versi terlebih dahulu agar aspirasi dapat diperiksa.',
                'kandidat' => [],
                'entri_utama' => null,
                'prioritas' => null,
            ];
        }

        $entries = $version->entri()
            ->active()
            ->with(['versi', 'commission'])
            ->orderBy('kode')
            ->get();

        $criteria = $this->criteriaForVersion($version);

        $candidates = [];

        foreach ($entries as $entry) {
            $scored = $this->scoreEntry($entry, $normalized, $criteria);

            if ($scored['skor'] <= 0) {
                continue;
            }

            $snapshot = $entry->toSnapshot();

            $candidates[] = [
                'entri_id' => $entry->id,
                'kode' => $entry->kode,
                'klasifikasi' => $entry->klasifikasi,
                'komisi' => $entry->commission?->name,
                'komisi_id' => $entry->dprd_commission_id,
                'tahun' => $version->tahun,
                'prioritas' => $entry->prioritas,
                'tindak_lanjut' => $entry->tindak_lanjut,
                'skor' => $scored['skor'],
                'matched_terms' => $scored['matched_terms'],
                'entri_snapshot' => $snapshot,
            ];
        }

        usort($candidates, static function (array $a, array $b): int {
            return [$b['skor'], $a['kode']] <=> [$a['skor'], $b['kode']];
        });

        $minimumLength = (int) ($criteria['panjang_minimum_teks'] ?? config('kamus_acuan.minimum_text_length', 12));
        $minimumTokens = (int) ($criteria['minimal_jumlah_kata'] ?? config('kamus_acuan.minimum_tokens', 3));
        $tokens = $normalized === '' ? [] : explode(' ', $normalized);
        $teksTerlaluPendek = mb_strlen($normalized) < $minimumLength
            || count($tokens) < $minimumTokens;

        if ($candidates === []) {
            return [
                'status' => LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN,
                'versi' => $version,
                'teks' => $text,
                'alasan' => $normalized === ''
                    ? 'Teks aspirasi kosong sehingga tidak ada istilah yang dapat dicocokkan dengan Kamus Acuan.'
                    : 'Tidak ada kata kunci, sinonim, kode, atau klasifikasi pada Kamus Acuan '.$version->tahun.' yang cocok dengan teks aspirasi.',
                'kandidat' => [],
                'entri_utama' => null,
                'prioritas' => null,
            ];
        }

        $top = $candidates[0];
        $topEntry = $entries->firstWhere('id', $top['entri_id']);
        $minimumScore = (float) ($criteria['skor_minimum'] ?? config('kamus_acuan.minimum_score', 4));

        if (count($candidates) > 1) {
            return [
                'status' => LaporanKamusHasil::STATUS_PERLU_VERIFIKASI,
                'versi' => $version,
                'teks' => $text,
                'alasan' => sprintf(
                    'Ditemukan %d kandidat pada Kamus Acuan %d. Kandidat terkuat adalah %s dengan skor %s, sehingga Super Admin perlu memilih entri yang tepat.',
                    count($candidates),
                    $version->tahun,
                    $top['kode'].' — '.$top['klasifikasi'],
                    self::formatScore((float) $top['skor'])
                ),
                'kandidat' => $candidates,
                'entri_utama' => $topEntry,
                'prioritas' => null,
            ];
        }

        if ($teksTerlaluPendek) {
            return [
                'status' => LaporanKamusHasil::STATUS_PERLU_VERIFIKASI,
                'versi' => $version,
                'teks' => $text,
                'alasan' => 'Teks aspirasi terlalu pendek atau belum jelas. Kandidat '.$top['kode'].' — '.$top['klasifikasi'].' perlu diverifikasi Super Admin sebelum dianggap sesuai kamus.',
                'kandidat' => $candidates,
                'entri_utama' => $topEntry,
                'prioritas' => $topEntry?->prioritas,
            ];
        }

        if ((float) $top['skor'] < $minimumScore) {
            return [
                'status' => LaporanKamusHasil::STATUS_PERLU_VERIFIKASI,
                'versi' => $version,
                'teks' => $text,
                'alasan' => sprintf(
                    'Hanya ada satu kandidat, yaitu %s, tetapi skornya %s masih di bawah batas %s. Hasil ini perlu diverifikasi Super Admin.',
                    $top['kode'].' — '.$top['klasifikasi'],
                    self::formatScore((float) $top['skor']),
                    self::formatScore($minimumScore)
                ),
                'kandidat' => $candidates,
                'entri_utama' => $topEntry,
                'prioritas' => $topEntry?->prioritas,
            ];
        }

        return [
            'status' => LaporanKamusHasil::STATUS_SESUAI,
            'versi' => $version,
            'teks' => $text,
            'alasan' => sprintf(
                'Teks aspirasi cocok dengan %s — %s pada Kamus Acuan %d dengan skor %s. Istilah yang cocok: %s.',
                $top['kode'],
                $top['klasifikasi'],
                $version->tahun,
                self::formatScore((float) $top['skor']),
                self::matchedTermsLabel($top['matched_terms'])
            ),
            'kandidat' => $candidates,
            'entri_utama' => $topEntry,
            'prioritas' => $topEntry?->prioritas,
        ];
    }

    /**
     * Jalankan pemeriksaan otomatis dan simpan sebagai baris hasil baru.
     * Keputusan manual yang sudah ada tidak ditimpa.
     */
    public function periksaOtomatis(LaporanMasyarakat $report, ?User $user = null, ?KamusAcuanVersi $version = null): ?LaporanKamusHasil
    {
        $version ??= $this->activeVersion();

        if (! $version) {
            return null;
        }

        $evaluation = $this->evaluateText(self::reportText($report), $version);

        $hasil = DB::transaction(function () use ($report, $evaluation, $version, $user) {
            $locked = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($report->id);

            $entri = $evaluation['entri_utama'];
            $diperiksaAt = now();

            $hasil = LaporanKamusHasil::query()->create([
                'laporan_masyarakat_id' => $locked->id,
                'kamus_acuan_versi_id' => $version->id,
                'tahun_kamus' => $version->tahun,
                'status' => $evaluation['status'],
                'metode' => $this->method(),
                'teks_diperiksa' => self::reportText($locked),
                'alasan' => $evaluation['alasan'],
                'kandidat' => $evaluation['kandidat'],
                'entri_utama_id' => $entri?->id,
                'entri_utama_snapshot' => $entri?->toSnapshot(),
                'prioritas_rekomendasi' => $evaluation['prioritas'],
                'jumlah_kandidat' => count($evaluation['kandidat']),
                'diperiksa_at' => $diperiksaAt,
                'diperiksa_oleh' => $user?->id,
            ]);

            $this->syncAutoCache($locked, $hasil);

            AktivitasSistem::catat(
                'kamus.laporan.diperiksa',
                $user,
                $locked,
                [
                    'nomor_tiket' => $locked->nomor_tiket,
                    'kamus_hasil_id' => $hasil->id,
                    'status' => $hasil->status,
                    'tahun' => $version->tahun,
                    'jumlah_kandidat' => $hasil->jumlah_kandidat,
                ],
                'Pemeriksaan Kamus Acuan untuk laporan '.$locked->nomor_tiket.' dijalankan.'
            );

            return $hasil;
        });

        $report->refresh();

        return $hasil;
    }

    /**
     * Periksa banyak laporan. $onlyOutdated memeriksa laporan yang belum
     * pernah diperiksa atau hasil otomatisnya berasal dari versi lama.
     *
     * @return array{diperiksa: int, dilewati: int, tanpa_versi: bool}
     */
    public function periksaMassal(?User $user = null, bool $onlyOutdated = false, ?int $limit = null, ?int $tahun = null): array
    {
        $version = $tahun
            ? KamusAcuanVersi::query()->where('tahun', $tahun)->first()
            : $this->activeVersion();

        if (! $version) {
            return ['diperiksa' => 0, 'dilewati' => 0, 'tanpa_versi' => true];
        }

        $query = LaporanMasyarakat::query()->orderBy('id');

        if ($onlyOutdated) {
            $query->where(function ($query) use ($version) {
                $query->whereNull('kamus_diperiksa_at')
                    ->orWhereNull('kamus_versi_id')
                    ->orWhere('kamus_versi_id', '!=', $version->id);
            });
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $diperiksa = 0;
        $dilewati = 0;

        if ($limit !== null && $limit > 0) {
            foreach ($query->get() as $report) {
                try {
                    $this->periksaOtomatis($report, $user, $version);
                    $diperiksa++;
                } catch (Throwable $exception) {
                    report($exception);
                    $dilewati++;
                }
            }

            return ['diperiksa' => $diperiksa, 'dilewati' => $dilewati, 'tanpa_versi' => false];
        }

        $query->chunkById(100, function ($reports) use ($user, $version, &$diperiksa, &$dilewati) {
            foreach ($reports as $report) {
                try {
                    $this->periksaOtomatis($report, $user, $version);
                    $diperiksa++;
                } catch (Throwable $exception) {
                    report($exception);
                    $dilewati++;
                }
            }
        });

        return ['diperiksa' => $diperiksa, 'dilewati' => $dilewati, 'tanpa_versi' => false];
    }

    /**
     * Simpan keputusan manual Super Admin. Hasil otomatis tetap utuh.
     *
     * @param  array{keputusan: string, entri_id?: int|null, catatan?: string|null}  $data
     */
    public function verifikasi(LaporanMasyarakat $report, array $data, User $user): LaporanKamusVerifikasi
    {
        return DB::transaction(function () use ($report, $data, $user) {
            $locked = LaporanMasyarakat::query()
                ->lockForUpdate()
                ->findOrFail($report->id);

            $keputusan = $data['keputusan'];
            $entry = null;

            if (! empty($data['entri_id'])) {
                $entry = KamusAcuanEntri::query()
                    ->with(['versi', 'commission'])
                    ->findOrFail((int) $data['entri_id']);
            }

            if ($keputusan === LaporanKamusVerifikasi::KEPUTUSAN_SESUAI && ! $entry) {
                throw ValidationException::withMessages([
                    'entri_id' => 'Pilih entri Kamus Acuan yang sesuai sebelum menetapkan status sesuai kamus.',
                ]);
            }

            if ($keputusan === LaporanKamusVerifikasi::KEPUTUSAN_TIDAK_DITEMUKAN) {
                $entry = null;
            }

            $statusSesudah = match ($keputusan) {
                LaporanKamusVerifikasi::KEPUTUSAN_SESUAI => LaporanKamusHasil::STATUS_SESUAI,
                LaporanKamusVerifikasi::KEPUTUSAN_TIDAK_DITEMUKAN => LaporanKamusHasil::STATUS_TIDAK_DITEMUKAN,
                default => LaporanKamusHasil::STATUS_PERLU_VERIFIKASI,
            };

            $snapshot = $entry?->toSnapshot();
            $statusSebelum = $locked->kamus_status;

            $verifikasi = LaporanKamusVerifikasi::query()->create([
                'laporan_masyarakat_id' => $locked->id,
                'laporan_kamus_hasil_id' => $locked->kamus_hasil_id,
                'status_sebelum' => $statusSebelum,
                'status_sesudah' => $statusSesudah,
                'entri_kamus_id' => $entry?->id,
                'entri_snapshot' => $snapshot,
                'keputusan' => $keputusan,
                'catatan' => $data['catatan'] ?? null,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_at' => now(),
            ]);

            $locked->forceFill([
                'kamus_status' => $statusSesudah,
                'kamus_entri_id' => $entry?->id,
                'kamus_versi_id' => $entry?->kamus_acuan_versi_id ?? $locked->kamus_versi_id,
                'kamus_tahun' => $entry?->versi?->tahun ?? $locked->kamus_tahun,
                'kamus_prioritas' => $entry?->prioritas,
                'kamus_diverifikasi_oleh' => $user->id,
                'kamus_diverifikasi_at' => now(),
                'kamus_catatan_verifikasi' => $data['catatan'] ?? null,
            ])->save();

            AktivitasSistem::catat(
                'kamus.laporan.diverifikasi',
                $user,
                $locked,
                [
                    'nomor_tiket' => $locked->nomor_tiket,
                    'status_sebelum' => $statusSebelum,
                    'status_sesudah' => $statusSesudah,
                    'entri_kamus_id' => $entry?->id,
                    'keputusan' => $keputusan,
                ],
                'Verifikasi Kamus Acuan untuk laporan '.$locked->nomor_tiket.' disimpan.'
            );

            return $verifikasi;
        });
    }

    /**
     * @param  array{tahun: int|string, nama: string, keterangan?: string|null, is_active?: bool, salin_dari?: int|string|null}  $data
     */
    public function createVersion(array $data, User $user): KamusAcuanVersi
    {
        return DB::transaction(function () use ($data, $user) {
            $active = filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOL);

            if ($active) {
                $this->deactivateVersions(null, $user);
            }

            $version = KamusAcuanVersi::query()->create([
                'tahun' => (int) $data['tahun'],
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? null,
                'is_active' => $active,
                'ditetapkan_oleh' => $active ? $user->id : null,
                'ditetapkan_at' => $active ? now() : null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $copied = 0;
            $sourceId = (int) ($data['salin_dari'] ?? 0);

            if ($sourceId > 0) {
                $source = KamusAcuanVersi::query()->find($sourceId);

                if ($source) {
                    /*
                     * Hanya entri yang tidak dihapus yang disalin. Entri lama
                     * yang sudah dihapus tetap tersimpan pada versi asalnya
                     * sebagai riwayat dan tidak perlu diduplikasi.
                     */
                    foreach ($source->entri()->get() as $entry) {
                        KamusAcuanEntri::query()->create([
                            'kamus_acuan_versi_id' => $version->id,
                            'dprd_commission_id' => $entry->dprd_commission_id,
                            'kode' => $entry->kode,
                            'klasifikasi' => $entry->klasifikasi,
                            'uraian' => $entry->uraian,
                            'kata_kunci' => $entry->kata_kunci,
                            'sinonim' => $entry->sinonim,
                            'kriteria' => $entry->kriteria,
                            'prioritas' => $entry->prioritas,
                            'tindak_lanjut' => $entry->tindak_lanjut,
                            'is_active' => $entry->is_active,
                        ]);
                        $copied++;
                    }

                    /*
                     * Kriteria/bobot perhitungan ikut disalin agar versi baru
                     * memakai parameter SPK yang sama sampai diubah admin.
                     */
                    foreach ($source->kriteria()->ordered()->get() as $criteria) {
                        KamusKriteria::query()->create([
                            'kamus_acuan_versi_id' => $version->id,
                            'kode' => $criteria->kode,
                            'nama' => $criteria->nama,
                            'bobot' => $criteria->bobot,
                            'satuan' => $criteria->satuan,
                            'keterangan' => $criteria->keterangan,
                            'is_active' => $criteria->is_active,
                            'urutan' => $criteria->urutan,
                        ]);
                    }
                }
            }

            $this->seedDefaultCriteria($version, $user);

            $this->log($version->id, null, 'versi_dibuat', null, $this->versionSnapshot($version), $user, $copied > 0 ? 'Versi dibuat dan menyalin '.$copied.' entri.' : 'Versi kamus dibuat.');

            return $version->refresh();
        });
    }

    /**
     * @param  array{tahun: int|string, nama: string, keterangan?: string|null, is_active?: bool}  $data
     */
    public function updateVersion(KamusAcuanVersi $version, array $data, User $user): KamusAcuanVersi
    {
        return DB::transaction(function () use ($version, $data, $user) {
            $before = $this->versionSnapshot($version);
            $active = filter_var($data['is_active'] ?? $version->is_active, FILTER_VALIDATE_BOOL);

            if ($active) {
                $this->deactivateVersions($version->id, $user);
            }

            $version->update([
                'tahun' => (int) $data['tahun'],
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? null,
                'is_active' => $active,
                'ditetapkan_oleh' => $active ? ($version->ditetapkan_oleh ?: $user->id) : $version->ditetapkan_oleh,
                'ditetapkan_at' => $active ? ($version->ditetapkan_at ?: now()) : $version->ditetapkan_at,
                'updated_by' => $user->id,
            ]);

            $this->log($version->id, null, 'versi_diubah', $before, $this->versionSnapshot($version->refresh()), $user, 'Versi kamus diperbarui.');

            return $version;
        });
    }

    public function activateVersion(KamusAcuanVersi $version, User $user): KamusAcuanVersi
    {
        return DB::transaction(function () use ($version, $user) {
            if ($version->is_active) {
                return $version;
            }

            $this->deactivateVersions($version->id, $user);

            $version->update([
                'is_active' => true,
                'ditetapkan_oleh' => $user->id,
                'ditetapkan_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->log($version->id, null, 'versi_diaktifkan', null, $this->versionSnapshot($version->refresh()), $user, 'Versi kamus diaktifkan.');

            return $version;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEntry(KamusAcuanVersi $version, array $data, User $user): KamusAcuanEntri
    {
        return DB::transaction(function () use ($version, $data, $user) {
            $entry = KamusAcuanEntri::query()->create([
                'kamus_acuan_versi_id' => $version->id,
                'dprd_commission_id' => $data['dprd_commission_id'] ?? null,
                'kode' => $data['kode'],
                'klasifikasi' => $data['klasifikasi'],
                'uraian' => $data['uraian'] ?? null,
                'kata_kunci' => $data['kata_kunci'] ?? [],
                'sinonim' => $data['sinonim'] ?? [],
                'kriteria' => $data['kriteria'] ?? null,
                'prioritas' => $data['prioritas'] ?? null,
                'tindak_lanjut' => $data['tindak_lanjut'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->log($version->id, $entry->id, 'entri_dibuat', null, $this->entrySnapshot($entry), $user, 'Entri kamus ditambahkan.');

            return $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEntry(KamusAcuanEntri $entry, array $data, User $user): KamusAcuanEntri
    {
        return DB::transaction(function () use ($entry, $data, $user) {
            $before = $this->entrySnapshot($entry);

            $entry->update([
                'dprd_commission_id' => $data['dprd_commission_id'] ?? null,
                'kode' => $data['kode'],
                'klasifikasi' => $data['klasifikasi'],
                'uraian' => $data['uraian'] ?? null,
                'kata_kunci' => $data['kata_kunci'] ?? [],
                'sinonim' => $data['sinonim'] ?? [],
                'kriteria' => $data['kriteria'] ?? null,
                'prioritas' => $data['prioritas'] ?? null,
                'tindak_lanjut' => $data['tindak_lanjut'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->log($entry->kamus_acuan_versi_id, $entry->id, 'entri_diubah', $before, $this->entrySnapshot($entry->refresh()), $user, 'Entri kamus diperbarui.');

            return $entry;
        });
    }

    public function toggleEntry(KamusAcuanEntri $entry, User $user): KamusAcuanEntri
    {
        return DB::transaction(function () use ($entry, $user) {
            $before = $this->entrySnapshot($entry);
            $entry->update(['is_active' => ! $entry->is_active]);

            $this->log(
                $entry->kamus_acuan_versi_id,
                $entry->id,
                $entry->is_active ? 'entri_diaktifkan' : 'entri_dinonaktifkan',
                $before,
                $this->entrySnapshot($entry->refresh()),
                $user,
                $entry->is_active ? 'Entri diaktifkan.' : 'Entri dinonaktifkan.'
            );

            return $entry;
        });
    }

    public function deleteEntry(KamusAcuanEntri $entry, User $user): void
    {
        DB::transaction(function () use ($entry, $user) {
            $before = $this->entrySnapshot($entry);
            $entry->delete();

            $this->log(
                $entry->kamus_acuan_versi_id,
                $entry->id,
                'entri_dihapus',
                $before,
                null,
                $user,
                'Entri dihapus secara lunak. Hasil pemeriksaan historis tetap tersimpan.'
            );
        });
    }

    /**
     * Impor CSV. Baris yang tidak valid dilewati dan dilaporkan tanpa
     * membatalkan baris lain.
     *
     * @return array{dibuat: int, diperbarui: int, dilewati: int, errors: array<int, string>}
     */
    public function importCsv(KamusAcuanVersi $version, UploadedFile $file, User $user): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $maxRows = (int) config('kamus_acuan.import_max_rows', 5000);

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Berkas CSV tidak dapat dibaca.',
            ]);
        }

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false) {
                throw ValidationException::withMessages([
                    'file' => 'Berkas CSV kosong.',
                ]);
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $delimiter = $this->detectDelimiter($firstLine);
            $headers = $this->parseCsvLine($firstLine, $delimiter);
            $headerMap = $this->headerMap($headers);

            if (! isset($headerMap['kode']) || ! isset($headerMap['klasifikasi'])) {
                throw ValidationException::withMessages([
                    'file' => 'Header CSV harus memuat kolom kode dan klasifikasi.',
                ]);
            }

            $rowNumber = 1;

            DB::transaction(function () use (
                $handle,
                $delimiter,
                $headerMap,
                $version,
                $user,
                $maxRows,
                &$created,
                &$updated,
                &$skipped,
                &$errors,
                &$rowNumber
            ) {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;

                    if ($rowNumber - 1 > $maxRows) {
                        $errors[] = 'Impor dihentikan karena melebihi batas '.$maxRows.' baris.';
                        break;
                    }

                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    $values = $this->combineRow($row, $headerMap);
                    $kode = trim((string) ($values['kode'] ?? ''));
                    $klasifikasi = trim((string) ($values['klasifikasi'] ?? ''));

                    if ($kode === '' || $klasifikasi === '') {
                        $errors[] = 'Baris '.$rowNumber.': kode dan klasifikasi wajib diisi.';
                        $skipped++;

                        continue;
                    }

                    if (mb_strlen($kode) > 50 || mb_strlen($klasifikasi) > 180) {
                        $errors[] = 'Baris '.$rowNumber.': kode maksimal 50 karakter dan klasifikasi maksimal 180 karakter.';
                        $skipped++;

                        continue;
                    }

                    $kataKunci = $this->parseCsvTerms($values['kata_kunci'] ?? '');
                    $sinonim = $this->parseCsvTerms($values['sinonim'] ?? '');

                    if ($kataKunci['errors'] !== [] || $sinonim['errors'] !== []) {
                        $errors[] = 'Baris '.$rowNumber.': '.implode(' ', array_merge($kataKunci['errors'], $sinonim['errors']));
                        $skipped++;

                        continue;
                    }

                    $commissionId = $this->resolveCommissionId($values['komisi'] ?? '');

                    if ($commissionId === false) {
                        $errors[] = 'Baris '.$rowNumber.': komisi "'.trim((string) ($values['komisi'] ?? '')).'" tidak ditemukan.';
                        $skipped++;

                        continue;
                    }

                    $attributes = [
                        'dprd_commission_id' => $commissionId,
                        'kode' => $kode,
                        'klasifikasi' => $klasifikasi,
                        'uraian' => $this->nullableString($values['uraian'] ?? ''),
                        'kata_kunci' => $kataKunci['terms'],
                        'sinonim' => $sinonim['terms'],
                        'kriteria' => $this->nullableString($values['kriteria'] ?? ''),
                        'prioritas' => $this->nullableString($values['prioritas'] ?? ''),
                        'tindak_lanjut' => $this->nullableString($values['tindak_lanjut'] ?? ''),
                        'is_active' => $this->parseBoolean($values['is_active'] ?? '', true),
                    ];

                    /*
                     * Utamakan baris aktif. Baris lama yang sudah dihapus
                     * lunak hanya dipulihkan jika belum ada baris aktif
                     * dengan kode yang sama pada versi ini.
                     */
                    $existing = KamusAcuanEntri::query()
                        ->where('kamus_acuan_versi_id', $version->id)
                        ->where('kode', $kode)
                        ->first();

                    if (! $existing) {
                        $existing = KamusAcuanEntri::onlyTrashed()
                            ->where('kamus_acuan_versi_id', $version->id)
                            ->where('kode', $kode)
                            ->latest('id')
                            ->first();
                    }

                    if ($existing) {
                        $before = $this->entrySnapshot($existing);

                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        $existing->update($attributes);
                        $this->log($version->id, $existing->id, 'entri_diubah', $before, $this->entrySnapshot($existing->refresh()), $user, 'Entri diperbarui melalui impor CSV.');
                        $updated++;

                        continue;
                    }

                    $entry = KamusAcuanEntri::query()->create([
                        'kamus_acuan_versi_id' => $version->id,
                    ] + $attributes);
                    $this->log($version->id, $entry->id, 'entri_dibuat', null, $this->entrySnapshot($entry), $user, 'Entri ditambahkan melalui impor CSV.');
                    $created++;
                }
            });

            $this->log(
                $version->id,
                null,
                'impor',
                null,
                ['dibuat' => $created, 'diperbarui' => $updated, 'dilewati' => $skipped],
                $user,
                'Impor CSV: '.$created.' entri dibuat, '.$updated.' diperbarui, '.$skipped.' dilewati.'
            );
        } finally {
            fclose($handle);
        }

        return [
            'dibuat' => $created,
            'diperbarui' => $updated,
            'dilewati' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function exportRows(KamusAcuanVersi $version): array
    {
        $rows = [[
            'kode',
            'klasifikasi',
            'uraian',
            'kata_kunci',
            'sinonim',
            'kriteria',
            'prioritas',
            'tindak_lanjut',
            'komisi',
            'is_active',
        ]];

        $entries = $version->entri()
            ->with('commission')
            ->orderBy('kode')
            ->get();

        foreach ($entries as $entry) {
            $rows[] = [
                $entry->kode,
                $entry->klasifikasi,
                (string) $entry->uraian,
                $this->termsToCsv($entry->kata_kunci_list),
                $this->termsToCsv($entry->sinonim_list),
                (string) $entry->kriteria,
                (string) $entry->prioritas,
                (string) $entry->tindak_lanjut,
                (string) ($entry->commission?->name ?? ''),
                $entry->is_active ? '1' : '0',
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function templateRow(): array
    {
        return [
            'KOM-01',
            'Infrastruktur jalan dan drainase',
            'Contoh uraian klasifikasi.',
            'jalan|3; jalan rusak|3; drainase|3',
            'infrastruktur|2; jalanan|2',
            'Contoh kriteria yang dapat diverifikasi.',
            '',
            'Contoh tindak lanjut.',
            'Komisi 2',
            '1',
        ];
    }

    private function syncAutoCache(LaporanMasyarakat $report, LaporanKamusHasil $hasil): void
    {
        $attributes = [
            'kamus_status_otomatis' => $hasil->status,
            'kamus_hasil_id' => $hasil->id,
            'kamus_diperiksa_at' => $hasil->diperiksa_at,
        ];

        /*
         * Keputusan manual adalah sumber kebenaran terakhir. Pemeriksaan
         * ulang hanya memperbarui hasil otomatis dan tidak mengubah
         * keputusan tersebut.
         */
        if (! $report->isKamusVerified()) {
            $attributes['kamus_status'] = $hasil->status;
            $attributes['kamus_entri_id'] = $hasil->entri_utama_id;
            $attributes['kamus_versi_id'] = $hasil->kamus_acuan_versi_id;
            $attributes['kamus_tahun'] = $hasil->tahun_kamus;
            $attributes['kamus_prioritas'] = $hasil->prioritas_rekomendasi;
        }

        $report->forceFill($attributes)->save();
    }

    /**
     * Hitung skor satu entri memakai kriteria/bobot versi kamus.
     *
     * Rumus: jumlah (kemunculan × bobot istilah × pengali kriteria).
     * Pengali kata kunci/sinonim dan bonus frasa berasal dari tabel
     * kamus_kriteria, bukan dari nilai hardcode.
     *
     * @param  array<string, float>  $criteria
     * @return array{skor: float, matched_terms: array<int, array<string, mixed>>}
     */
    private function scoreEntry(KamusAcuanEntri $entry, string $normalizedText, array $criteria = []): array
    {
        $score = 0.0;
        $matches = [];

        $keywordMultiplier = max(0.0, (float) ($criteria['bobot_kata_kunci'] ?? 1.0));
        $synonymMultiplier = max(0.0, (float) ($criteria['bobot_sinonim'] ?? 0.8));
        $phraseMultiplier = max(0.0, (float) ($criteria['bobot_frasa'] ?? 1.0));
        $maxOccurrences = max(1, (int) round((float) ($criteria['maks_kemunculan']
            ?? config('kamus_acuan.max_occurrences', 3))));

        foreach ($entry->matchingTerms() as $term) {
            $occurrences = $this->countOccurrences($normalizedText, $term['term'], $maxOccurrences);

            if ($occurrences === 0) {
                continue;
            }

            $sourceMultiplier = ($term['sumber'] ?? '') === 'sinonim'
                ? $synonymMultiplier
                : $keywordMultiplier;
            $termMultiplier = str_contains($term['term'], ' ') ? $phraseMultiplier : 1.0;
            $effectiveWeight = $term['weight'] * $sourceMultiplier * $termMultiplier;
            $subtotal = $occurrences * $effectiveWeight;
            $score += $subtotal;

            $matches[] = [
                'term' => $term['term'],
                'weight' => $term['weight'],
                'effective_weight' => round($effectiveWeight, 3),
                'occurrences' => $occurrences,
                'subtotal' => round($subtotal, 3),
                'sumber' => $term['sumber'],
            ];
        }

        usort($matches, static fn (array $a, array $b) => $b['subtotal'] <=> $a['subtotal']);

        return [
            'skor' => round($score, 2),
            'matched_terms' => $matches,
        ];
    }

    private function countOccurrences(string $normalizedText, string $term, ?int $max = null): int
    {
        if ($normalizedText === '' || $term === '') {
            return 0;
        }

        $needle = ' '.$term.' ';
        $haystack = ' '.$normalizedText.' ';
        $count = substr_count($haystack, $needle);
        $max ??= max(1, (int) config('kamus_acuan.max_occurrences', 3));

        return min($count, max(1, $max));
    }

    /**
     * Nilai kriteria aktif untuk satu versi. Nilai bawaan dipakai sebagai
     * dasar, lalu ditimpa baris aktif pada tabel kamus_kriteria.
     *
     * @return array<string, float>
     */
    public function criteriaForVersion(?KamusAcuanVersi $version): array
    {
        $values = KamusKriteria::defaultValues();

        if (! $version) {
            return $values;
        }

        $rows = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->active()
            ->get(['kode', 'bobot']);

        foreach ($rows as $row) {
            if (array_key_exists($row->kode, KamusKriteria::FAKTORS)) {
                $values[$row->kode] = (float) $row->bobot;
            }
        }

        return $values;
    }

    /**
     * Pastikan versi memiliki baris kriteria bawaan. Aman dipanggil
     * berulang: baris yang sudah ada tidak ditimpa.
     */
    public function seedDefaultCriteria(KamusAcuanVersi $version, ?User $user = null): void
    {
        $existing = KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->pluck('kode')
            ->all();

        foreach (KamusKriteria::defaults() as $row) {
            if (in_array($row['kode'], $existing, true)) {
                continue;
            }

            KamusKriteria::query()->create([
                'kamus_acuan_versi_id' => $version->id,
            ] + $row);
        }

        if ($existing === []) {
            $this->log(
                $version->id,
                null,
                'kriteria_dibuat',
                null,
                ['kriteria' => count(KamusKriteria::defaults())],
                $user,
                'Kriteria dan bobot bawaan ditambahkan untuk perhitungan SPK.'
            );
        }
    }

    public function createCriteria(KamusAcuanVersi $version, array $data, User $user): KamusKriteria
    {
        $kode = (string) $data['kode'];

        if (! array_key_exists($kode, KamusKriteria::FAKTORS)) {
            throw ValidationException::withMessages([
                'kode' => 'Faktor perhitungan tidak dikenal.',
            ]);
        }

        if (KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->where('kode', $kode)
            ->exists()) {
            throw ValidationException::withMessages([
                'kode' => 'Kriteria ini sudah ada pada versi kamus tersebut.',
            ]);
        }

        $faktor = KamusKriteria::FAKTORS[$kode];
        $urutan = (int) KamusKriteria::query()
            ->where('kamus_acuan_versi_id', $version->id)
            ->max('urutan');

        return DB::transaction(function () use ($version, $data, $user, $kode, $faktor, $urutan) {
            $criteria = KamusKriteria::query()->create([
                'kamus_acuan_versi_id' => $version->id,
                'kode' => $kode,
                'nama' => $this->nullableString($data['nama'] ?? '') ?? $faktor['nama'],
                'bobot' => (float) ($data['bobot'] ?? $faktor['bobot']),
                'satuan' => $faktor['satuan'],
                'keterangan' => $this->nullableString($data['keterangan'] ?? '') ?? $faktor['keterangan'],
                'is_active' => $this->parseBoolean($data['is_active'] ?? true, true),
                'urutan' => $urutan + 1,
            ]);

            $this->log(
                $version->id,
                null,
                'kriteria_dibuat',
                null,
                ['kriteria' => $this->criteriaSnapshot($criteria)],
                $user,
                'Kriteria '.$criteria->nama.' ditambahkan.'
            );

            return $criteria;
        });
    }

    public function updateCriteria(KamusKriteria $criteria, array $data, User $user): KamusKriteria
    {
        return DB::transaction(function () use ($criteria, $data, $user) {
            $before = $this->criteriaSnapshot($criteria);

            $criteria->update([
                'nama' => $this->nullableString($data['nama'] ?? '') ?? $criteria->nama,
                'bobot' => array_key_exists('bobot', $data)
                    ? (float) $data['bobot']
                    : $criteria->bobot,
                'keterangan' => array_key_exists('keterangan', $data)
                    ? $this->nullableString($data['keterangan'])
                    : $criteria->keterangan,
                'is_active' => array_key_exists('is_active', $data)
                    ? $this->parseBoolean($data['is_active'], true)
                    : $criteria->is_active,
            ]);

            $this->log(
                $criteria->kamus_acuan_versi_id,
                null,
                'kriteria_diubah',
                $before,
                $this->criteriaSnapshot($criteria->refresh()),
                $user,
                'Kriteria '.$criteria->nama.' diperbarui; perhitungan SPK berikutnya memakai nilai baru.'
            );

            return $criteria;
        });
    }

    public function deleteCriteria(KamusKriteria $criteria, User $user): void
    {
        DB::transaction(function () use ($criteria, $user) {
            $before = $this->criteriaSnapshot($criteria);
            $versionId = $criteria->kamus_acuan_versi_id;
            $nama = $criteria->nama;

            $criteria->delete();

            $this->log(
                $versionId,
                null,
                'kriteria_dihapus',
                $before,
                null,
                $user,
                'Kriteria '.$nama.' dihapus; perhitungan kembali memakai nilai bawaan.'
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function criteriaSnapshot(KamusKriteria $criteria): array
    {
        return [
            'id' => $criteria->id,
            'kode' => $criteria->kode,
            'nama' => $criteria->nama,
            'bobot' => (float) $criteria->bobot,
            'is_active' => (bool) $criteria->is_active,
        ];
    }

    private function deactivateVersions(?int $exceptId = null, ?User $user = null): void
    {
        $versions = KamusAcuanVersi::query()
            ->where('is_active', true)
            ->when($exceptId, static fn ($query) => $query->where('id', '!=', $exceptId))
            ->get();

        foreach ($versions as $version) {
            $before = $this->versionSnapshot($version);

            $version->update([
                'is_active' => false,
                'updated_by' => $user?->id,
            ]);

            $this->log(
                $version->id,
                null,
                'versi_dinonaktifkan',
                $before,
                $this->versionSnapshot($version->refresh()),
                $user,
                'Versi dinonaktifkan karena versi lain diaktifkan.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function versionSnapshot(KamusAcuanVersi $version): array
    {
        return [
            'id' => $version->id,
            'tahun' => $version->tahun,
            'nama' => $version->nama,
            'keterangan' => $version->keterangan,
            'is_active' => (bool) $version->is_active,
            'jumlah_entri' => $version->entri()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entrySnapshot(KamusAcuanEntri $entry): array
    {
        return $entry->fresh(['versi', 'commission'])?->toSnapshot() ?? [];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function log(
        ?int $versionId,
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
            'catatan' => $note,
            'user_id' => $user?->id,
        ]);
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($candidates);

        return (string) array_key_first($candidates) ?: ',';
    }

    /**
     * @return array<int, string>
     */
    private function parseCsvLine(string $line, string $delimiter): array
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return [];
        }

        fwrite($stream, $line);
        rewind($stream);
        $row = fgetcsv($stream, 0, $delimiter);
        fclose($stream);

        return is_array($row) ? array_map(static fn ($value) => trim((string) $value), $row) : [];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function headerMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';
            $key = trim($key, '_');
            $aliases = [
                'kode_entri' => 'kode',
                'keywords' => 'kata_kunci',
                'kata_kunci_' => 'kata_kunci',
                'synonyms' => 'sinonim',
                'kriteria_' => 'kriteria',
                'komisi_id' => 'komisi',
                'status' => 'is_active',
            ];
            $key = $aliases[$key] ?? $key;

            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerMap
     * @return array<string, string>
     */
    private function combineRow(array $row, array $headerMap): array
    {
        $values = [];

        foreach ($headerMap as $key => $index) {
            $values[$key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $values;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{terms: array<int, array{term: string, weight: int}>, errors: array<int, string>}
     */
    private function parseCsvTerms(string $cell): array
    {
        $cell = trim($cell);

        if ($cell === '') {
            return ['terms' => [], 'errors' => []];
        }

        $lines = preg_split('/[;\r\n]+/u', $cell) ?: [];

        return KamusAcuanEntri::parseTermsText(implode("\n", $lines));
    }

    /**
     * @param  array<int, array{term: string, weight: int}>  $terms
     */
    private function termsToCsv(array $terms): string
    {
        return collect($terms)
            ->map(static fn (array $term) => $term['term'].'|'.$term['weight'])
            ->implode('; ');
    }

    private function resolveCommissionId(string $value): int|false|null
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $query = DprdCommission::withTrashed();

        if (ctype_digit($value)) {
            $commission = $query->find((int) $value);
        } else {
            $commission = $query
                ->where(static function ($query) use ($value) {
                    $query->where('name', $value)
                        ->orWhere('slug', $value)
                        ->orWhere('field', $value);
                })
                ->first();
        }

        return $commission?->id ?? false;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function parseBoolean(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'ya', 'aktif', 'active'], true);
    }

    private static function matchedTermsLabel(array $matchedTerms): string
    {
        return collect($matchedTerms)
            ->take(6)
            ->map(static fn (array $term) => $term['term'].' (bobot '.$term['weight'].', muncul '.$term['occurrences'].'x)')
            ->implode(', ');
    }

    private static function formatScore(float $score): string
    {
        return rtrim(rtrim(number_format($score, 2, ',', '.'), '0'), ',');
    }
}
