<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\KamusAcuanEntriRequest;
use App\Http\Requests\KamusAcuanVersiRequest;
use App\Http\Requests\KamusImportRequest;
use App\Http\Requests\KamusUploadRequest;
use App\Models\DprdCommission;
use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\KamusKriteria;
use App\Models\LaporanKamusHasil;
use App\Models\LaporanMasyarakat;
use App\Services\KamusAcuanService;
use App\Services\KamusUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class KamusAcuanController extends Controller
{
    public function __construct(
        private readonly KamusAcuanService $kamus,
        private readonly KamusUploadService $uploads
    ) {
    }
    public function index(Request $request): View
    {
        $this->authorize('manage-kamus-acuan');

        $versions = KamusAcuanVersi::query()
            ->withCount('entri')
            ->orderByDesc('tahun')
            ->get();

        $selectedVersion = $this->resolveVersion($request->query('versi'), $versions);

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'komisi' => trim((string) $request->query('komisi')),
            'status' => trim((string) $request->query('status')),
        ];

        $entries = null;

        if ($selectedVersion) {
            $query = $selectedVersion->entri()
                ->with(['commission', 'versi'])
                ->orderBy('kode');

            if ($filters['q'] !== '') {
                $needle = '%'.Str::lower($filters['q']).'%';
                $query->where(function ($query) use ($needle) {
                    $query->whereRaw('LOWER(kode) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(klasifikasi) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(uraian) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(kata_kunci) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(sinonim) LIKE ?', [$needle]);
                });
            }

            if ($filters['komisi'] !== '' && ctype_digit($filters['komisi'])) {
                $query->where('dprd_commission_id', (int) $filters['komisi']);
            }

            if ($filters['status'] === 'aktif') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'nonaktif') {
                $query->where('is_active', false);
            }

            $entries = $query->paginate(20)->withQueryString();
        }

        $commissions = DprdCommission::query()->orderBy('name')->get(['id', 'name']);

        $statusCounts = LaporanMasyarakat::query()
            ->selectRaw('kamus_status, COUNT(*) as total')
            ->groupBy('kamus_status')
            ->pluck('total', 'kamus_status')
            ->all();

        $statusSummary = [];

        foreach (LaporanKamusHasil::STATUS_LABELS as $status => $label) {
            $statusSummary[$status] = [
                'label' => $label,
                'total' => (int) ($statusCounts[$status] ?? 0),
                'tone' => LaporanKamusHasil::STATUS_TONES[$status] ?? 'neutral',
            ];
        }

        $statusSummary[LaporanKamusHasil::STATUS_BELUM_DIPERIKSA]['total'] =
            (int) ($statusCounts[LaporanKamusHasil::STATUS_BELUM_DIPERIKSA] ?? 0)
            + (int) ($statusCounts[''] ?? 0);

        return view('kamus.index', [
            'versions' => $versions,
            'selectedVersion' => $selectedVersion,
            'entries' => $entries,
            'commissions' => $commissions,
            'filters' => $filters,
            'statusSummary' => $statusSummary,
            'activeVersion' => $versions->firstWhere('is_active', true),
            'totalReports' => LaporanMasyarakat::query()->count(),
            'kriteria' => $selectedVersion
                ? $selectedVersion->kriteria()->ordered()->get()
                : collect(),
            'kriteriaFaktorTersedia' => $selectedVersion
                ? collect(KamusKriteria::FAKTORS)->except(
                    $selectedVersion->kriteria()->pluck('kode')->all()
                )
                : collect(),
            'kriteriaTerakhirDiperbarui' => $selectedVersion
                ? $selectedVersion->kriteria()->max('updated_at')
                : null,
        ]);
    }

    public function createVersion(): View
    {
        $this->authorize('manage-kamus-acuan');

        return view('kamus.versi.create', [
            'versions' => KamusAcuanVersi::query()->orderByDesc('tahun')->get(),
            'activeVersion' => KamusAcuanVersi::aktif(),
        ]);
    }

    public function storeVersion(KamusAcuanVersiRequest $request): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $version = $this->kamus->createVersion($request->validated(), $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('success', 'Versi Kamus Acuan '.$version->tahun.' berhasil dibuat.');
    }

    public function editVersion(KamusAcuanVersi $version): View
    {
        $this->authorize('manage-kamus-acuan');

        $version->loadCount('entri');

        return view('kamus.versi.edit', [
            'version' => $version,
        ]);
    }

    public function updateVersion(KamusAcuanVersiRequest $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $this->kamus->updateVersion($version, $request->validated(), $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('success', 'Versi Kamus Acuan berhasil diperbarui.');
    }

    public function activateVersion(Request $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $this->kamus->activateVersion($version, $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('success', 'Kamus Acuan tahun '.$version->tahun.' sekarang aktif. Pemeriksaan ulang hanya mengubah hasil otomatis, bukan keputusan manual yang sudah ada.');
    }

    public function createEntry(KamusAcuanVersi $version): View
    {
        $this->authorize('manage-kamus-acuan');

        return view('kamus.entri.create', [
            'version' => $version,
            'entry' => null,
            'commissions' => DprdCommission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeEntry(KamusAcuanEntriRequest $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $entry = $this->kamus->createEntry($version, $this->entryPayload($request->validated()), $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $entry->kamus_acuan_versi_id])
            ->with('success', 'Entri '.$entry->kode.' berhasil ditambahkan.');
    }

    public function editEntry(KamusAcuanEntri $entry): View
    {
        $this->authorize('manage-kamus-acuan');

        $entry->load(['versi', 'commission']);

        return view('kamus.entri.edit', [
            'version' => $entry->versi,
            'entry' => $entry,
            'commissions' => DprdCommission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateEntry(KamusAcuanEntriRequest $request, KamusAcuanEntri $entry): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $this->kamus->updateEntry($entry, $this->entryPayload($request->validated()), $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $entry->kamus_acuan_versi_id])
            ->with('success', 'Entri '.$entry->kode.' berhasil diperbarui.');
    }

    public function toggleEntry(Request $request, KamusAcuanEntri $entry): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $this->kamus->toggleEntry($entry, $request->user());

        return back()->with('success', 'Status entri '.$entry->kode.' berhasil diubah.');
    }

    public function destroyEntry(Request $request, KamusAcuanEntri $entry): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $kode = $entry->kode;
        $this->kamus->deleteEntry($entry, $request->user());

        return back()->with('success', 'Entri '.$kode.' dihapus secara lunak. Riwayat pemeriksaan lama tetap tersimpan.');
    }

    public function storeKriteria(Request $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys(KamusKriteria::FAKTORS))],
            'nama' => ['nullable', 'string', 'max:120'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $criteria = $this->kamus->createCriteria($version, $validated, $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('success', 'Kriteria '.$criteria->nama.' berhasil ditambahkan dan langsung dipakai pada perhitungan berikutnya.');
    }

    public function updateKriteria(Request $request, KamusKriteria $criteria): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->kamus->updateCriteria($criteria, $validated, $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $criteria->kamus_acuan_versi_id])
            ->with('success', 'Kriteria '.$criteria->nama.' diperbarui. Perhitungan SPK berikutnya memakai nilai baru.');
    }

    public function destroyKriteria(Request $request, KamusKriteria $criteria): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $versionId = $criteria->kamus_acuan_versi_id;
        $nama = $criteria->nama;
        $this->kamus->deleteCriteria($criteria, $request->user());

        return redirect()
            ->route('kamus.index', ['versi' => $versionId])
            ->with('success', 'Kriteria '.$nama.' dihapus. Perhitungan kembali memakai nilai bawaan.');
    }

    public function importForm(KamusAcuanVersi $version): View
    {
        $this->authorize('manage-kamus-acuan');

        return view('kamus.import', [
            'version' => $version,
        ]);
    }

    public function import(KamusImportRequest $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        try {
            $result = $this->kamus->importCsv($version, $request->file('file'), $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Berkas CSV belum dapat diimpor. Periksa format berkas lalu coba lagi.');
        }

        $message = $result['dibuat'].' entri dibuat, '.$result['diperbarui'].' diperbarui, '.$result['dilewati'].' dilewati.';

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with($result['errors'] === [] ? 'success' : 'warning', $message)
            ->with('import_errors', $result['errors']);
    }

    public function export(KamusAcuanVersi $version): StreamedResponse
    {
        $this->authorize('manage-kamus-acuan');

        return $this->csvResponse(
            'kamus-acuan-'.$version->tahun.'-'.Str::slug($version->nama).'.csv',
            $this->kamus->exportRows($version)
        );
    }

    public function uploadForm(KamusAcuanVersi $version): View
    {
        $this->authorize('manage-kamus-acuan');

        return view('kamus.upload', [
            'version' => $version,
            'limits' => $this->uploads->limits(),
            'formats' => \App\Services\KamusFileParser::FORMAT_LABELS,
        ]);
    }

    public function uploadPreview(
        KamusUploadRequest $request,
        KamusAcuanVersi $version
    ): RedirectResponse {
        $this->authorize('manage-kamus-acuan');

        try {
            $preview = $this->uploads->analyze($request->file('file'), $version);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with(
                'error',
                'Berkas belum dapat dibaca. Pastikan format dan isi berkas valid; tidak ada data Kamus Acuan yang diubah.'
            );
        }

        $request->session()->put($this->uploads->sessionKey($version->id), $preview);

        return redirect()->route('kamus.versi.unggah.pratinjau', $version);
    }

    public function uploadPreviewShow(Request $request, KamusAcuanVersi $version): View|RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $preview = $request->session()->get($this->uploads->sessionKey($version->id));

        if (! is_array($preview) || (int) ($preview['version_id'] ?? 0) !== (int) $version->id) {
            return redirect()
                ->route('kamus.versi.unggah', $version)
                ->with('warning', 'Pratinjau tidak ditemukan atau sudah kedaluwarsa. Silakan unggah berkas kembali.');
        }

        return view('kamus.upload_preview', [
            'version' => $version,
            'preview' => $preview,
            'limits' => $this->uploads->limits(),
        ]);
    }

    public function uploadApply(Request $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:gabung,ganti'],
            'token' => ['required', 'string'],
        ]);

        $preview = $request->session()->get($this->uploads->sessionKey($version->id));

        if (! is_array($preview) || ! hash_equals((string) ($preview['token'] ?? ''), $validated['token'])) {
            return redirect()
                ->route('kamus.versi.unggah', $version)
                ->with('error', 'Sesi pratinjau tidak cocok atau sudah kedaluwarsa. Unggah ulang berkas untuk mencegah perubahan yang tidak disengaja.');
        }

        try {
            $result = $this->uploads->apply($version, $preview, $request->user(), $validated['mode']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('kamus.versi.unggah.pratinjau', $version)
                ->with('error', 'Penerapan berkas gagal dan seluruh perubahan dibatalkan.');
        }

        $request->session()->forget($this->uploads->sessionKey($version->id));

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('success', sprintf(
                'Berkas Kamus Acuan diterapkan: %d entri dibuat, %d diperbarui, %d dihapus lunak.',
                $result['dibuat'],
                $result['diperbarui'],
                $result['dinonaktifkan']
            ));
    }

    public function uploadCancel(Request $request, KamusAcuanVersi $version): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        $request->session()->forget($this->uploads->sessionKey($version->id));

        return redirect()
            ->route('kamus.index', ['versi' => $version->id])
            ->with('info', 'Pratinjau unggahan dibatalkan. Tidak ada data Kamus Acuan yang diubah.');
    }

    public function template(): StreamedResponse
    {
        $this->authorize('manage-kamus-acuan');

        return $this->csvResponse('templat-impor-kamus-acuan.csv', [
            [
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
            ],
            $this->kamus->templateRow(),
        ]);
    }

    public function riwayat(Request $request): View
    {
        $this->authorize('manage-kamus-acuan');

        $versions = KamusAcuanVersi::query()->orderByDesc('tahun')->get(['id', 'tahun', 'nama']);

        $filters = [
            'versi' => trim((string) $request->query('versi')),
            'aksi' => trim((string) $request->query('aksi')),
        ];

        $query = KamusAcuanRiwayat::query()
            ->with(['versi', 'entri', 'user'])
            ->latest('id');

        if ($filters['versi'] !== '' && ctype_digit($filters['versi'])) {
            $query->where('kamus_acuan_versi_id', (int) $filters['versi']);
        }

        if ($filters['aksi'] !== '' && array_key_exists($filters['aksi'], KamusAcuanRiwayat::AKSI_LABELS)) {
            $query->where('aksi', $filters['aksi']);
        }

        return view('kamus.riwayat', [
            'riwayat' => $query->paginate(30)->withQueryString(),
            'versions' => $versions,
            'filters' => $filters,
            'aksiLabels' => KamusAcuanRiwayat::AKSI_LABELS,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function entryPayload(array $validated): array
    {
        $kataKunci = KamusAcuanEntri::parseTermsText($validated['kata_kunci_text'] ?? '');
        $sinonim = KamusAcuanEntri::parseTermsText($validated['sinonim_text'] ?? '');

        if ($kataKunci['errors'] !== []) {
            throw ValidationException::withMessages([
                'kata_kunci_text' => $kataKunci['errors'],
            ]);
        }

        if ($sinonim['errors'] !== []) {
            throw ValidationException::withMessages([
                'sinonim_text' => $sinonim['errors'],
            ]);
        }

        return [
            'dprd_commission_id' => $validated['dprd_commission_id'] ?? null,
            'kode' => $validated['kode'],
            'klasifikasi' => $validated['klasifikasi'],
            'uraian' => $validated['uraian'] ?: null,
            'kata_kunci' => $kataKunci['terms'],
            'sinonim' => $sinonim['terms'],
            'kriteria' => $validated['kriteria'] ?: null,
            'prioritas' => $validated['prioritas'] ?: null,
            'tindak_lanjut' => $validated['tindak_lanjut'] ?: null,
            'is_active' => (bool) $validated['is_active'],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, KamusAcuanVersi>  $versions
     */
    private function resolveVersion(mixed $requested, $versions): ?KamusAcuanVersi
    {
        if ($requested !== null && ctype_digit((string) $requested)) {
            $selected = $versions->firstWhere('id', (int) $requested);

            if ($selected) {
                return $selected;
            }
        }

        return $versions->firstWhere('is_active', true) ?: $versions->first();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function csvResponse(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM membantu Excel membaca huruf beraksen dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
