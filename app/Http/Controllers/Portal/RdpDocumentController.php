<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\RdpDocumentRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdCommission;
use App\Models\RdpDocument;
use App\Models\RdpMeeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RdpDocumentController extends Controller
{
    /**
     * Simpan berkas pada disk privat (storage/app), bukan direktori publik.
     * Nama berkas dibuat acak agar nama asli dari browser tidak pernah
     * dipakai langsung sebagai path.
     */
    public function store(
        DprdCommission $commission,
        RdpMeeting $meeting,
        RdpDocumentRequest $request
    ): RedirectResponse {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);

        $validated = $request->validated();
        $file = $request->file('file');
        $directory = 'rdp-documents/'.$meeting->id;
        $storedName = (string) Str::uuid().'.'.$file->extension();
        $path = $file->storeAs($directory, $storedName, 'local');

        if (! $path) {
            return back()->withErrors([
                'file' => 'Berkas gagal disimpan. Silakan coba lagi.',
            ], 'documentUpload');
        }

        $document = RdpDocument::query()->create([
            'rdp_meeting_id' => $meeting->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'document_date' => $validated['document_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'original_name' => $this->safeOriginalName($file->getClientOriginalName()),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        AktivitasSistem::catat(
            'rdp.document.uploaded',
            $request->user(),
            $document,
            [
                'rdp_meeting_id' => $meeting->id,
                'category' => $document->category,
            ],
            'Dokumen '.$document->title.' diunggah pada RDP '.$meeting->title.'.'
        );

        return redirect()
            ->route('komisi.rdp.show', [$commission, $meeting])
            ->with('success', 'Dokumen berhasil diunggah dan tersimpan pada penyimpanan privat.');
    }

    /**
     * Unduhan hanya dapat dilakukan administrator yang berwenang dan hanya
     * untuk dokumen milik RDP pada folder komisi yang sedang dibuka.
     */
    public function download(
        DprdCommission $commission,
        RdpMeeting $meeting,
        RdpDocument $document
    ): StreamedResponse|RedirectResponse {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);
        $this->ensureDocumentBelongsToMeeting($document, $meeting);

        if (! Storage::disk('local')->exists($document->file_path)) {
            return back()->with('error', 'Berkas dokumen tidak ditemukan pada penyimpanan.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream']
        );
    }

    /**
     * Dokumen di-soft delete. Berkas fisik tetap disimpan untuk keperluan
     * audit sehingga riwayat RDP tidak hilang.
     */
    public function destroy(
        Request $request,
        DprdCommission $commission,
        RdpMeeting $meeting,
        RdpDocument $document
    ): RedirectResponse {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);
        $this->ensureDocumentBelongsToMeeting($document, $meeting);

        $document->delete();

        AktivitasSistem::catat(
            'rdp.document.deleted',
            $request->user(),
            $document,
            ['rdp_meeting_id' => $meeting->id],
            'Dokumen '.$document->title.' dihapus (soft delete) dari RDP '.$meeting->title.'.'
        );

        return redirect()
            ->route('komisi.rdp.show', [$commission, $meeting])
            ->with('success', 'Dokumen berhasil dihapus. Berkas fisik tetap disimpan untuk keperluan audit.');
    }

    private function ensureMeetingBelongsToCommission(RdpMeeting $meeting, DprdCommission $commission): void
    {
        abort_unless(
            (int) $meeting->dprd_commission_id === (int) $commission->id,
            404,
            'RDP tidak berada pada folder komisi yang diminta.'
        );
    }

    private function ensureDocumentBelongsToMeeting(RdpDocument $document, RdpMeeting $meeting): void
    {
        abort_unless(
            (int) $document->rdp_meeting_id === (int) $meeting->id,
            404,
            'Dokumen tidak berada pada RDP yang diminta.'
        );
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN ._\-()]+/u', '_', $name) ?: 'dokumen';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'dokumen', 0, 255);
    }
}
