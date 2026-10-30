<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\RdpMeetingRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdCommission;
use App\Models\RdpMeeting;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RdpMeetingController extends Controller
{
    public function index(DprdCommission $commission, Request $request): View
    {
        $this->authorize('manage-rdp-archives');

        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => (string) $request->query('status'),
            'tanggal' => (string) $request->query('tanggal'),
        ];

        if ($filters['tanggal'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['tanggal'])) {
            $filters['tanggal'] = '';
        }

        $query = $commission->rdpMeetings()
            ->withCount('documents')
            ->with('creator');

        if ($filters['q'] !== '') {
            $needle = '%'.Str::lower($filters['q']).'%';

            $query->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
            });
        }

        if (array_key_exists($filters['status'], RdpMeeting::STATUS_LABELS)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['tanggal'] !== '') {
            $query->whereDate('meeting_date', $filters['tanggal']);
        }

        $filteredTotal = (clone $query)->count();

        $meetings = $query
            ->orderByDesc('meeting_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('rdp.index', [
            'commission' => $commission,
            'meetings' => $meetings,
            'filters' => $filters,
            'statuses' => RdpMeeting::STATUS_LABELS,
            'totalRdp' => $commission->rdpMeetings()->count(),
            'totalDocuments' => $commission->documents()->count(),
            'filteredTotal' => $filteredTotal,
            'hasFilters' => $filters['q'] !== ''
                || $filters['status'] !== ''
                || $filters['tanggal'] !== '',
        ]);
    }

    public function create(DprdCommission $commission): View
    {
        $this->authorize('manage-rdp-archives');

        if (! $commission->is_active) {
            abort(403, 'RDP baru tidak dapat ditambahkan pada komisi yang nonaktif.');
        }

        $meeting = new RdpMeeting([
            'dprd_commission_id' => $commission->id,
            'meeting_date' => now()->toDateString(),
            'status' => RdpMeeting::STATUS_DRAF,
        ]);

        return view('rdp.create', [
            'commission' => $commission,
            'meeting' => $meeting,
            'commissions' => collect([$commission]),
            'statuses' => RdpMeeting::STATUS_LABELS,
        ]);
    }

    public function store(DprdCommission $commission, RdpMeetingRequest $request): RedirectResponse
    {
        $this->authorize('manage-rdp-archives');

        if (! $commission->is_active) {
            abort(403, 'RDP baru tidak dapat ditambahkan pada komisi yang nonaktif.');
        }

        $validated = $request->validated();

        if ((int) $validated['dprd_commission_id'] !== $commission->id) {
            throw ValidationException::withMessages([
                'dprd_commission_id' => 'Komisi tidak sesuai dengan folder RDP yang sedang dibuka.',
            ]);
        }

        try {
            $meeting = DB::transaction(function () use ($commission, $validated, $request) {
                return RdpMeeting::query()->create([
                    'dprd_commission_id' => $commission->id,
                    'title' => $validated['title'],
                    'code' => $validated['code'] ?: null,
                    'meeting_date' => $validated['meeting_date'],
                    'start_time' => $validated['start_time'] ?: null,
                    'location' => $validated['location'] ?: null,
                    'description' => $validated['description'] ?: null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?: null,
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateCode($exception);
        }

        AktivitasSistem::catat(
            'rdp.created',
            $request->user(),
            $meeting,
            ['dprd_commission_id' => $meeting->dprd_commission_id],
            'RDP '.$meeting->title.' ditambahkan pada '.$commission->name.'.'
        );

        return redirect()
            ->route('komisi.rdp.index', $commission)
            ->with('success', 'RDP berhasil ditambahkan ke folder '.$commission->name.'.');
    }

    public function show(DprdCommission $commission, RdpMeeting $meeting): View
    {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);

        $meeting->load([
            'commission',
            'creator',
            'documents' => function ($query) {
                $query->with('uploader')->orderByDesc('created_at');
            },
        ]);

        return view('rdp.show', [
            'commission' => $commission,
            'meeting' => $meeting,
            'categories' => \App\Models\RdpDocument::CATEGORY_LABELS,
        ]);
    }

    public function edit(DprdCommission $commission, RdpMeeting $meeting): View
    {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);

        $meeting->load('commission');

        $commissions = DprdCommission::query()
            ->where(function ($query) use ($meeting) {
                $query->where('is_active', true)
                    ->orWhere('id', $meeting->dprd_commission_id);
            })
            ->orderBy('id')
            ->get();

        return view('rdp.edit', [
            'meeting' => $meeting,
            'commission' => $commission,
            'commissions' => $commissions,
            'statuses' => RdpMeeting::STATUS_LABELS,
        ]);
    }

    public function update(
        DprdCommission $commission,
        RdpMeetingRequest $request,
        RdpMeeting $meeting
    ): RedirectResponse {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);

        $validated = $request->validated();
        $targetCommission = DprdCommission::query()->findOrFail($validated['dprd_commission_id']);

        if (
            $targetCommission->id !== $meeting->dprd_commission_id
            && ! $targetCommission->is_active
        ) {
            throw ValidationException::withMessages([
                'dprd_commission_id' => 'RDP hanya dapat dipindahkan ke komisi yang aktif.',
            ]);
        }

        try {
            DB::transaction(function () use ($meeting, $validated, $targetCommission) {
                $meeting->update([
                    'dprd_commission_id' => $targetCommission->id,
                    'title' => $validated['title'],
                    'code' => $validated['code'] ?: null,
                    'meeting_date' => $validated['meeting_date'],
                    'start_time' => $validated['start_time'] ?: null,
                    'location' => $validated['location'] ?: null,
                    'description' => $validated['description'] ?: null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?: null,
                ]);
            });
        } catch (QueryException $exception) {
            $this->throwDuplicateCode($exception);
        }

        AktivitasSistem::catat(
            'rdp.updated',
            $request->user(),
            $meeting,
            ['dprd_commission_id' => $meeting->dprd_commission_id],
            'RDP '.$meeting->title.' diperbarui.'
        );

        return redirect()
            ->route('komisi.rdp.index', $targetCommission)
            ->with('success', 'RDP berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        DprdCommission $commission,
        RdpMeeting $meeting
    ): RedirectResponse {
        $this->authorize('manage-rdp-archives');
        $this->ensureMeetingBelongsToCommission($meeting, $commission);

        DB::transaction(function () use ($meeting) {
            $meeting->delete();
        });

        AktivitasSistem::catat(
            'rdp.deleted',
            $request->user(),
            $meeting,
            ['dprd_commission_id' => $meeting->dprd_commission_id],
            'RDP '.$meeting->title.' dihapus (soft delete).'
        );

        return redirect()
            ->route('komisi.rdp.index', $commission)
            ->with('success', 'RDP berhasil dihapus. Dokumen terkait tetap tersimpan untuk keperluan audit.');
    }

    private function ensureMeetingBelongsToCommission(RdpMeeting $meeting, DprdCommission $commission): void
    {
        abort_unless(
            (int) $meeting->dprd_commission_id === (int) $commission->id,
            404,
            'RDP tidak berada pada folder komisi yang diminta.'
        );
    }

    private function throwDuplicateCode(QueryException $exception): void
    {
        if (str_contains($exception->getMessage(), 'rdp_meetings_commission_active_code_unique')) {
            throw ValidationException::withMessages([
                'code' => 'Nomor atau kode RDP sudah digunakan pada komisi ini.',
            ]);
        }

        throw $exception;
    }
}
