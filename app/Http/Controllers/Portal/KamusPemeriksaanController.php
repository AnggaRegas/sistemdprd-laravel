<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\KamusVerifikasiRequest;
use App\Models\LaporanMasyarakat;
use App\Services\KamusAcuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class KamusPemeriksaanController extends Controller
{
    public function __construct(private readonly KamusAcuanService $kamus)
    {
    }

    /**
     * Jalankan pemeriksaan otomatis ulang untuk satu laporan. Hasil lama
     * tetap tersimpan sebagai riwayat, keputusan manual tidak ditimpa.
     */
    public function periksa(Request $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        abort_unless($request->user()?->isAdministrator(), 403);

        try {
            $hasil = $this->kamus->periksaOtomatis($laporan, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('laporan.show', $laporan)
                ->with('error', 'Pemeriksaan Kamus Acuan belum berhasil. Silakan coba lagi.');
        }

        if (! $hasil) {
            return redirect()
                ->route('laporan.show', $laporan)
                ->with('warning', 'Belum ada versi Kamus Acuan aktif. Tetapkan versi terlebih dahulu.');
        }

        return redirect()
            ->route('laporan.show', $laporan)
            ->with('success', 'Pemeriksaan Kamus Acuan selesai dengan status '.$hasil->status_label.'.');
    }

    public function verifikasi(KamusVerifikasiRequest $request, LaporanMasyarakat $laporan): RedirectResponse
    {
        $this->authorize('manage-kamus-acuan');

        abort_unless($request->user()?->isAdministrator(), 403);

        $this->kamus->verifikasi($laporan, $request->validated(), $request->user());

        return redirect()
            ->route('laporan.show', $laporan)
            ->with('success', 'Keputusan verifikasi Kamus Acuan berhasil disimpan. Keputusan manual ini tidak mengubah hasil otomatis.');
    }
}
