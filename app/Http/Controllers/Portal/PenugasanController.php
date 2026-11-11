<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteAssignmentRequest;
use App\Models\AktivitasSistem;
use App\Models\LaporanPenugasan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenugasanController extends Controller
{
    /**
     * Monitoring penugasan. Superadmin memantau seluruh penugasan staf
     * pendamping, staf pendamping hanya melihat penugasannya sendiri.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdministrator();

        $statusOptions = LaporanPenugasan::STATUS_LABELS;

        $filters = [
            'status' => $this->statusFilter($request->query('status')),
            'staff' => $isAdmin ? (int) $request->query('staff') : (int) $user->id,
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
        ];

        $staffs = $isAdmin
            ? User::query()
                ->where('role', User::ROLE_STAF_PENDAMPING)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $base = LaporanPenugasan::query()->when(
            ! $isAdmin,
            static fn ($query) => $query->where('user_id', $user->id)
        );

        $summary = [
            'total' => (clone $base)->count(),
            'ditugaskan' => (clone $base)
                ->where('status', LaporanPenugasan::STATUS_DITUGASKAN)
                ->whereNull('released_at')
                ->count(),
            'sedang' => (clone $base)
                ->where('status', LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI)
                ->count(),
            'selesai' => (clone $base)
                ->where('status', LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI)
                ->count(),
        ];

        $query = (clone $base)
            ->with(['laporan.member', 'user', 'assignedBy', 'completedBy'])
            ->latest('assigned_at')
            ->latest('id');

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);

            /*
             * Penugasan yang dilepas tetap tersimpan sebagai riwayat. Status
             * Ditugaskan hanya relevan untuk penugasan yang masih aktif.
             */
            if ($filters['status'] === LaporanPenugasan::STATUS_DITUGASKAN) {
                $query->whereNull('released_at');
            }
        }

        if ($filters['staff'] > 0) {
            $query->where('user_id', $filters['staff']);
        }

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            /*
             * Pencarian dilakukan hanya melalui relasi laporan (judul
             * aspirasi dan nomor tiket) serta nama staf. Tabel
             * laporan_penugasans sendiri tidak menyimpan kolom judul.
             */
            $query->where(function ($query) use ($needle) {
                $query->whereHas('laporan', function ($query) use ($needle) {
                    $query->whereRaw('LOWER(nomor_tiket) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(ringkasan) LIKE ?', [$needle]);
                })
                    ->orWhereHas('user', function ($query) use ($needle) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$needle]);
                    });
            });
        }

        $penugasans = $query->paginate(15)->withQueryString();

        return view('penugasan.index', [
            'isAdmin' => $isAdmin,
            'filters' => $filters,
            'statuses' => $statusOptions,
            'staffs' => $staffs,
            'summary' => $summary,
            'penugasans' => $penugasans,
        ]);
    }

    /**
     * Ekspor tugas milik staf yang sedang login. Query selalu dibatasi pada
     * user_id milik staf sehingga tidak mungkin memuat penugasan staf lain.
     * Filter status dan pencarian mengikuti filter yang aktif di halaman.
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStaff(), 403);

        $filters = [
            'status' => $this->statusFilter($request->query('status')),
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
        ];

        try {
            $query = LaporanPenugasan::query()
                ->where('user_id', $user->id)
                ->with(['laporan.member'])
                ->latest('assigned_at')
                ->latest('id');

            if ($filters['status'] !== '') {
                $query->where('status', $filters['status']);

                if ($filters['status'] === LaporanPenugasan::STATUS_DITUGASKAN) {
                    $query->whereNull('released_at');
                }
            }

            if ($filters['q'] !== '') {
                $needle = '%'.Str::lower($filters['q']).'%';

                $query->whereHas('laporan', function ($query) use ($needle) {
                    $query->whereRaw('LOWER(nomor_tiket) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(ringkasan) LIKE ?', [$needle]);
                });
            }

            $assignments = $query->get();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.staf')
                ->with('error', 'Gagal menyiapkan file ekspor tugas. Silakan coba lagi.');
        }

        $filename = 'tugas-staf-'.$user->id.'-'.now()->format('Ymd-His').'.csv';

        session()->flash(
            'success',
            'Ekspor tugas berhasil dibuat ('.$assignments->count().' penugasan milik Anda).'
        );

        return response()->streamDownload(function () use ($assignments): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nomor Tiket',
                'Ringkasan Laporan',
                'Anggota DPRD',
                'Status Penugasan',
                'Ditugaskan Pada',
                'Mulai Ditindaklanjuti',
                'Selesai Ditindaklanjuti',
                'Hasil Tindak Lanjut',
                'Keterangan Tambahan',
            ], ';');

            foreach ($assignments as $assignment) {
                fputcsv($handle, [
                    $assignment->laporan?->nomor_tiket ?? '',
                    $assignment->laporan?->ringkasan ?? '',
                    $assignment->laporan?->member?->nama_lengkap ?? '',
                    $assignment->status_label,
                    $assignment->assigned_at?->format('Y-m-d H:i:s') ?? '',
                    $assignment->started_at?->format('Y-m-d H:i:s') ?? '',
                    $assignment->completed_at?->format('Y-m-d H:i:s') ?? '',
                    $assignment->hasil_tindak_lanjut ?? '',
                    $assignment->keterangan_tambahan ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Detail penugasan: hasil tindak lanjut, bukti, dan riwayat waktu.
     * Superadmin membuka semua penugasan, staf hanya penugasannya sendiri.
     */
    public function show(Request $request, LaporanPenugasan $penugasan): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdministrator();

        abort_unless(
            $isAdmin || ((int) $penugasan->user_id === (int) $user->id),
            403,
            'Penugasan ini bukan milik Anda.'
        );

        $penugasan->load([
            'laporan.member',
            'laporan.assignee',
            'user',
            'assignedBy',
            'completedBy',
            'verifiedBy',
        ]);

        $tahapan = collect([
            [
                'label' => 'Ditugaskan',
                'waktu' => $penugasan->assigned_at,
                'oleh' => $penugasan->assignedBy?->name ?? 'Superadmin',
            ],
            [
                'label' => 'Sedang Ditindaklanjuti',
                'waktu' => $penugasan->started_at,
                'oleh' => $penugasan->user?->name,
            ],
            [
                'label' => 'Selesai Ditindaklanjuti',
                'waktu' => $penugasan->completed_at,
                'oleh' => $penugasan->completedBy?->name,
            ],
        ]);

        return view('penugasan.show', [
            'isAdmin' => $isAdmin,
            'penugasan' => $penugasan,
            'laporan' => $penugasan->laporan,
            'tahapan' => $tahapan,
        ]);
    }

    /**
     * Staf pendamping mulai mengerjakan tugasnya: Ditugaskan menjadi
     * Sedang Ditindaklanjuti. Status aspirasi tidak diubah otomatis.
     */
    public function start(Request $request, LaporanPenugasan $penugasan): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeStaff($user, $penugasan);

        DB::transaction(function () use ($penugasan, $user): void {
            $assignment = LaporanPenugasan::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            $this->authorizeStaff($user, $assignment);

            if ($assignment->status !== LaporanPenugasan::STATUS_DITUGASKAN) {
                return;
            }

            $assignment->status = LaporanPenugasan::STATUS_SEDANG_DITINDAKLANJUTI;
            $assignment->started_at = now();
            $assignment->save();

            AktivitasSistem::catat(
                'penugasan.mulai',
                $user,
                $assignment,
                [
                    'laporan_masyarakat_id' => $assignment->laporan_masyarakat_id,
                    'status' => $assignment->status,
                ],
                'Penugasan mulai ditindaklanjuti oleh staf pendamping.'
            );
        });

        return back()->with('success', 'Penugasan ditandai Sedang Ditindaklanjuti.');
    }

    /**
     * Staf pendamping menandai penugasan selesai. Hasil tindak lanjut wajib,
     * data disimpan pada penugasan, dan Superadmin menerima notifikasi.
     * Aksi berulang bersifat idempotent sehingga tidak ada hasil ganda.
     */
    public function complete(CompleteAssignmentRequest $request, LaporanPenugasan $penugasan): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeStaff($user, $penugasan);

        $validated = $request->validated();
        $file = $request->file('bukti');
        $storedPath = null;
        $wasAlreadyCompleted = false;

        try {
            DB::transaction(function () use ($penugasan, $user, $validated, $file, &$storedPath, &$wasAlreadyCompleted): void {
                $assignment = LaporanPenugasan::query()
                    ->lockForUpdate()
                    ->findOrFail($penugasan->id);

                $this->authorizeStaff($user, $assignment);

                if ($assignment->released_at !== null) {
                    throw ValidationException::withMessages([
                        'penugasan' => 'Penugasan tersebut sudah tidak aktif dan tidak bisa ditandai selesai.',
                    ])->errorBag('penugasan');
                }

                /*
                 * Request berulang (double click / retry jaringan) tidak boleh
                 * menimpa hasil dan tidak boleh membuat notifikasi ganda.
                 */
                if ($assignment->isSelesai()) {
                    $wasAlreadyCompleted = true;

                    return;
                }

                if ($file) {
                    $storedPath = $this->storeBukti($file);
                }

                $assignment->status = LaporanPenugasan::STATUS_SELESAI_DITINDAKLANJUTI;
                $assignment->started_at = $assignment->started_at ?: now();
                $assignment->hasil_tindak_lanjut = $validated['hasil_tindak_lanjut'];
                $assignment->keterangan_tambahan = $validated['keterangan_tambahan'] ?? null;
                $assignment->completed_at = now();
                $assignment->completed_by = $user->id;

                if ($storedPath) {
                    $assignment->bukti_path = $storedPath;
                    $assignment->bukti_nama = $file?->getClientOriginalName();
                    $assignment->bukti_mime = $file?->getMimeType() ?: 'application/octet-stream';
                    $assignment->bukti_size = (int) $file?->getSize();
                }

                $assignment->save();

                AktivitasSistem::catat(
                    'penugasan.selesai',
                    $user,
                    $assignment,
                    [
                        'laporan_masyarakat_id' => $assignment->laporan_masyarakat_id,
                        'status' => $assignment->status,
                        'hasil_tindak_lanjut' => $validated['hasil_tindak_lanjut'],
                        'bukti_tersimpan' => $storedPath !== null,
                    ],
                    'Penugasan ditandai selesai oleh staf pendamping; Superadmin diberi notifikasi.'
                );
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('dashboard.staf')
            ->with(
                'success',
                $wasAlreadyCompleted
                    ? 'Penugasan ini sudah selesai sebelumnya. Hasil tindak lanjut tetap tersimpan dan tidak ada notifikasi ganda.'
                    : 'Penugasan berhasil ditandai selesai. Superadmin telah menerima notifikasi.'
            );
    }

    /**
     * Konfirmasi hasil tindak lanjut oleh Superadmin. Tindakan ini hanya
     * menandai verifikasi pada penugasan; status aspirasi tetap dikelola
     * melalui alur status laporan yang sudah ada.
     */
    public function verify(Request $request, LaporanPenugasan $penugasan): RedirectResponse
    {
        $administrator = $request->user();
        abort_unless($administrator?->isAdministrator(), 403);

        $validated = $request->validate([
            'catatan_verifikasi' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $penugasan->isSelesai()) {
            return back()->with(
                'warning',
                'Penugasan belum selesai ditindaklanjuti sehingga belum bisa diverifikasi.'
            );
        }

        DB::transaction(function () use ($penugasan, $administrator, $validated): void {
            $assignment = LaporanPenugasan::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            if ($assignment->verified_at) {
                return;
            }

            $assignment->verified_at = now();
            $assignment->verified_by = $administrator->id;
            $assignment->catatan_verifikasi = $validated['catatan_verifikasi'] ?? null;
            $assignment->save();

            AktivitasSistem::catat(
                'penugasan.diverifikasi',
                $administrator,
                $assignment,
                [
                    'laporan_masyarakat_id' => $assignment->laporan_masyarakat_id,
                ],
                'Hasil tindak lanjut penugasan diverifikasi Superadmin.'
            );
        });

        return back()->with('success', 'Hasil tindak lanjut ditandai terverifikasi.');
    }

    /**
     * Simpan bukti tindak lanjut ke disk privat. Hanya dipanggil ketika staf
     * benar-benar memilih file, sehingga tidak ada proses unggah saat bukti
     * dikosongkan (opsional).
     */
    private function storeBukti(UploadedFile $file): string
    {
        $directory = 'penugasan-bukti/'.now()->format('Y/m');
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'bin');
        $storedName = (string) Str::uuid().'.'.$extension;

        $storedPath = null;

        try {
            $storedPath = $file->storeAs($directory, $storedName, 'local');
        } catch (\Throwable $exception) {
            report($exception);
        }

        if (! is_string($storedPath) || $storedPath === '') {
            throw ValidationException::withMessages([
                'bukti' => 'Gagal menyimpan bukti tindak lanjut. Silakan coba kembali.',
            ])->errorBag('penugasan');
        }

        return $storedPath;
    }

    /**
     * Unduh bukti tindak lanjut. Hanya Superadmin atau pemilik penugasan.
     */
    public function downloadBukti(Request $request, LaporanPenugasan $penugasan): StreamedResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isAdministrator() || (int) $penugasan->user_id === (int) $user->id,
            403,
            'Bukti tindak lanjut tidak dapat diakses.'
        );

        abort_unless($penugasan->hasBukti(), 404, 'Bukti tindak lanjut tidak tersedia.');

        $path = (string) $penugasan->bukti_path;

        abort_unless(
            Str::startsWith($path, 'penugasan-bukti/')
                && ! Str::contains($path, ['..', '\\']),
            404,
            'Bukti tindak lanjut tidak tersedia.'
        );

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'Berkas bukti tidak ditemukan.');

        $name = $this->safeDownloadName($penugasan->bukti_nama ?: 'bukti-tindak-lanjut');

        return $disk->download($path, $name, [
            'Content-Type' => $penugasan->bukti_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeStaff(User $user, LaporanPenugasan $penugasan): void
    {
        abort_unless(
            ($user->isStaff() ?? false)
                && (int) $penugasan->user_id === (int) $user->id
                && $penugasan->released_at === null,
            403,
            'Penugasan ini bukan milik Anda atau sudah tidak aktif.'
        );
    }

    private function statusFilter(mixed $value): string
    {
        $value = trim((string) $value);

        return array_key_exists($value, LaporanPenugasan::STATUS_LABELS) ? $value : '';
    }

    private function safeDownloadName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) ($name ?: 'bukti')));
        $name = preg_replace('/[^\pL\pN ._\-()]+/u', '_', $name) ?: 'bukti';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'bukti', 0, 255);
    }
}
