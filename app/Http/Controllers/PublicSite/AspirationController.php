<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicAspirationRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\LaporanMasyarakat;
use App\Services\AspirationSimilarityService;
use App\Services\KamusAcuanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AspirationController extends Controller
{
    public function create(): View
    {
        $regions = DprdRegion::query()
            ->active()
            ->ordered()
            ->get();

        $selectedRegionId = (int) old('dprd_region_id', 0);

        $members = $selectedRegionId > 0
            ? $this->membersInRegion($selectedRegionId)
            : collect();

        return view('public.aspiration.create', [
            'regions' => $regions,
            'members' => $members,
        ]);
    }

    /**
     * Daftar anggota aktif untuk wilayah yang dipilih. Tidak pernah
     * mengembalikan seluruh anggota sebagai fallback.
     */
    public function members(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dprd_region_id' => [
                'required',
                'integer',
                Rule::exists('dprd_regions', 'id')->where('is_active', true),
            ],
        ], [
            'dprd_region_id.required' => 'Pilih kecamatan atau dapil terlebih dahulu.',
            'dprd_region_id.exists' => 'Kecamatan atau dapil yang dipilih tidak tersedia.',
        ]);

        $region = DprdRegion::query()
            ->active()
            ->findOrFail((int) $validated['dprd_region_id']);

        $members = $this->membersInRegion($region->id);

        return response()->json([
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
            ],
            'members' => $members->map(static fn (DprdMember $member) => [
                'id' => $member->id,
                'name' => $member->nama_lengkap,
                'komisi' => $member->komisi,
                'dapil' => $member->dapil,
            ])->values()->all(),
            'message' => $members->isEmpty()
                ? 'Belum ada anggota DPRD aktif yang terhubung dengan wilayah ini. Silakan pilih wilayah lain atau hubungi Sekretariat DPRD.'
                : null,
        ]);
    }

    /**
     * Pratinjau klasifikasi komisi tidak lagi ditampilkan pada portal publik.
     * Endpoint lama dipertahankan sebagai pengalihan agar tautan lama tidak
     * menghasilkan galat, tetapi tidak memproses atau mengembalikan data.
     */
    public function analyze(): JsonResponse
    {
        return response()->json([
            'message' => 'Rekomendasi komisi tidak digunakan lagi pada formulir aspirasi.',
        ], 410);
    }

    /**
     * Simpan aspirasi masyarakat tanpa login. Status awal selalu menunggu
     * verifikasi administrator; disposisi staf dilakukan setelah persetujuan.
     */
    public function store(
        PublicAspirationRequest $request,
        AspirationSimilarityService $similarity,
        KamusAcuanService $kamus
    ): RedirectResponse {
        $validated = $request->validated();
        $file = $request->file('lampiran');
        $storedPath = null;

        $region = DprdRegion::query()
            ->active()
            ->find($validated['dprd_region_id']);

        if (! $region) {
            throw ValidationException::withMessages([
                'dprd_region_id' => 'Kecamatan atau dapil yang dipilih tidak tersedia.',
            ]);
        }

        $member = DprdMember::query()
            ->whereKey($validated['dprd_member_id'])
            ->where('is_active', true)
            ->inRegion($region->id)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'dprd_member_id' => 'Anggota yang dipilih tidak aktif atau tidak terhubung dengan kecamatan/dapil tersebut.',
            ]);
        }

        try {
            $report = DB::transaction(function () use ($validated, $file, $region, $member, &$storedPath) {
                if ($file) {
                    $directory = 'laporan-masyarakat/'.now()->format('Y/m');
                    $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'bin');
                    $storedName = (string) Str::uuid().'.'.$extension;

                    $storedPath = $file->storeAs($directory, $storedName, 'local');

                    if (! $storedPath) {
                        throw new \RuntimeException('Lampiran aspirasi gagal disimpan pada penyimpanan privat.');
                    }
                }

                $ringkasan = Str::limit(
                    preg_replace('/\s+/u', ' ', $validated['isi']) ?: $validated['isi'],
                    150,
                    '…'
                );

                $report = LaporanMasyarakat::query()->create([
                    'nomor_tiket' => $this->generateTicket(),
                    'pelapor_nama' => $validated['pelapor_nama'],
                    'pelapor_kontak' => $validated['pelapor_kontak'],
                    'pelapor_alamat' => $validated['pelapor_alamat'],
                    'ringkasan' => $ringkasan,
                    'isi' => $validated['isi'],
                    'kategori' => null,
                    'kecamatan' => $region->name,
                    'dprd_region_id' => $region->id,
                    'dprd_member_id' => $member->id,
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'status' => LaporanMasyarakat::STATUS_MENUNGGU_PENUGASAN,
                    'tanggal_laporan' => now()->toDateString(),
                    'ditutup_at' => null,
                    'catatan_penyelesaian' => null,
                    'lampiran_path' => $storedPath,
                    'lampiran_nama' => $file
                        ? $this->safeOriginalName($file->getClientOriginalName())
                        : null,
                    'lampiran_mime' => $file ? ($file->getMimeType() ?: 'application/octet-stream') : null,
                    'lampiran_size' => $file ? (int) $file->getSize() : null,
                ]);

                AktivitasSistem::catat(
                    'laporan.dibuat',
                    null,
                    $report,
                    [
                        'nomor_tiket' => $report->nomor_tiket,
                        'kecamatan' => $report->kecamatan,
                        'dprd_region_id' => $region->id,
                        'dprd_member_id' => $member->id,
                    ],
                    'Laporan masyarakat '.$report->nomor_tiket.' diterima melalui portal publik.'
                );

                return $report;
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Aspirasi belum dapat disimpan. Silakan periksa kembali isian Anda dan coba lagi.');
        }

        /*
         * Pemeriksaan kemiripan dijalankan setelah data benar-benar tersimpan
         * sehingga kegagalan analisis tidak membatalkan aspirasi masyarakat.
         */
        try {
            $similarity->analyzeAndStore($report);
        } catch (Throwable $exception) {
            report($exception);
        }

        /*
         * Pemeriksaan Kamus Acuan juga berjalan setelah laporan tersimpan.
         * Kegagalan pemeriksaan tidak boleh membatalkan aspirasi masyarakat.
         * Hasil otomatis disimpan terpisah dari keputusan manual Super Admin.
         */
        try {
            $kamus->periksaOtomatis($report);
        } catch (Throwable $exception) {
            report($exception);
        }

        // Nomor tiket tetap dibuat untuk kebutuhan internal, tetapi tidak pernah
        // dikirim kembali ke antarmuka publik.
        return redirect()
            ->route('aspirasi.success')
            ->with('aspiration_submitted', true);
    }

    public function success(Request $request): View|RedirectResponse
    {
        // Konfirmasi hanya dapat dibuka tepat setelah pengiriman. Tidak ada ID,
        // tiket, atau referensi internal yang diteruskan ke halaman publik.
        if (! $request->session()->pull('aspiration_submitted')) {
            return redirect()->route('welcome');
        }

        return view('public.aspiration.success');
    }

    /**
     * @return \Illuminate\Support\Collection<int, DprdMember>
     */
    private function membersInRegion(int $regionId)
    {
        return DprdMember::query()
            ->where('is_active', true)
            ->inRegion($regionId)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'komisi', 'dapil']);
    }

    private function generateTicket(): string
    {
        $prefix = 'ASP-'.now()->format('Ymd');

        do {
            $ticket = $prefix.'-'.Str::upper(Str::random(6));
        } while (LaporanMasyarakat::withTrashed()->where('nomor_tiket', $ticket)->exists());

        return $ticket;
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN ._\-()]+/u', '_', $name) ?: 'lampiran';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'lampiran', 0, 255);
    }
}
