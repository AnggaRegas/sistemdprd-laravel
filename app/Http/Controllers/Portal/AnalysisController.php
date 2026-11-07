<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LaporanAnalysisRun;
use App\Models\LaporanMasyarakat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    /**
     * Rincian analisis kemiripan satu laporan. Halaman ini hanya menampilkan
     * kembali snapshot perhitungan yang benar-benar dijalankan saat analisis
     * berlangsung, tanpa menghitung ulang dengan dokumen terbaru.
     */
    public function show(Request $request, LaporanMasyarakat $laporan): View
    {
        /*
         * Halaman dimaksudkan untuk Superadmin sebagai keterangan teknis
         * pemantauan duplikasi. Staf pendamping melihat hasil akhirnya
         * melalui laporan, bukan melalui rincian perhitungan ini.
         */
        $administrator = $request->user();
        abort_unless($administrator?->isAdministrator(), 403);

        $laporan->load(['member', 'assignee']);

        $analysisRun = LaporanAnalysisRun::query()
            ->where('laporan_masyarakat_id', $laporan->id)
            ->first();

        return view('analysis.show', [
            'laporan' => $laporan,
            'analysisRun' => $analysisRun,
            'snapshot' => $analysisRun?->snapshot,
        ]);
    }
}
