<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingAgendaRequest;
use App\Models\AktivitasSistem;
use App\Models\LandingAgenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingAgendaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-landing-page');

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'status' => $this->allowedValue($request->query('status'), array_keys(LandingAgenda::STATUSES)),
            'event_status' => $this->allowedValue($request->query('event_status'), array_keys(LandingAgenda::EVENT_STATUSES)),
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
        ];

        $agendas = LandingAgenda::query()
            ->when($filters['q'] !== '', fn ($query) => $query->where('title', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== null, fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['event_status'] !== null, fn ($query) => $query->where('event_status', $filters['event_status']))
            ->when($filters['date_from'] !== null, fn ($query) => $query->whereDate('event_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($query) => $query->whereDate('event_date', '<=', $filters['date_to']))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => LandingAgenda::count(),
            'published' => LandingAgenda::where('status', LandingAgenda::STATUS_PUBLISHED)->count(),
            'draft' => LandingAgenda::where('status', LandingAgenda::STATUS_DRAFT)->count(),
            'upcoming' => LandingAgenda::where('status', LandingAgenda::STATUS_PUBLISHED)
                ->whereDate('event_date', '>=', now()->toDateString())
                ->count(),
        ];

        return view('landing.agendas.index', [
            'agendas' => $agendas,
            'filters' => $filters,
            'stats' => $stats,
            'statusOptions' => LandingAgenda::STATUSES,
            'eventStatusOptions' => LandingAgenda::EVENT_STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-landing-page');

        return view('landing.agendas.create', [
            'agenda' => new LandingAgenda(),
            'statusOptions' => LandingAgenda::STATUSES,
            'eventStatusOptions' => LandingAgenda::EVENT_STATUSES,
        ]);
    }

    public function store(LandingAgendaRequest $request): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validated();
        $storedImage = $this->storeImage($request);

        try {
            $agenda = DB::transaction(function () use ($request, $validated, $storedImage) {
                return LandingAgenda::query()->create([
                    'title' => $validated['title'],
                    'slug' => $this->uniqueSlug($validated['title']),
                    'description' => $validated['description'],
                    'event_date' => $validated['event_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'location' => $validated['location'],
                    'image' => $storedImage,
                    'event_status' => $validated['event_status'],
                    'status' => $validated['status'],
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($storedImage !== null) {
                Storage::disk('public')->delete($storedImage);
            }

            throw $exception;
        }

        AktivitasSistem::catat(
            'landing_agenda.created',
            $request->user(),
            $agenda,
            ['status' => $agenda->status, 'event_status' => $agenda->event_status],
            'Agenda landing page "'.$agenda->title.'" ditambahkan.'
        );

        return redirect()
            ->route('landing.agendas.index')
            ->with('success', 'Agenda kegiatan berhasil ditambahkan.');
    }

    public function edit(LandingAgenda $agenda): View
    {
        $this->authorize('manage-landing-page');

        return view('landing.agendas.edit', [
            'agenda' => $agenda,
            'statusOptions' => LandingAgenda::STATUSES,
            'eventStatusOptions' => LandingAgenda::EVENT_STATUSES,
        ]);
    }

    public function update(LandingAgendaRequest $request, LandingAgenda $agenda): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $validated = $request->validated();
        $previousImage = $agenda->image;
        $storedImage = $this->storeImage($request);

        try {
            DB::transaction(function () use ($agenda, $validated, $storedImage) {
                $attributes = [
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'event_date' => $validated['event_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'location' => $validated['location'],
                    'event_status' => $validated['event_status'],
                    'status' => $validated['status'],
                ];

                if ($storedImage !== null) {
                    $attributes['image'] = $storedImage;
                }

                if ($agenda->title !== $validated['title']) {
                    $attributes['slug'] = $this->uniqueSlug($validated['title'], $agenda->id);
                }

                $agenda->update($attributes);
            });
        } catch (\Throwable $exception) {
            if ($storedImage !== null) {
                Storage::disk('public')->delete($storedImage);
            }

            throw $exception;
        }

        if ($storedImage !== null && $previousImage !== null && $previousImage !== $storedImage) {
            Storage::disk('public')->delete($previousImage);
        }

        AktivitasSistem::catat(
            'landing_agenda.updated',
            $request->user(),
            $agenda,
            ['status' => $agenda->status, 'event_status' => $agenda->event_status],
            'Agenda landing page "'.$agenda->title.'" diperbarui.'
        );

        return redirect()
            ->route('landing.agendas.index')
            ->with('success', 'Agenda kegiatan berhasil diperbarui.');
    }

    public function destroy(Request $request, LandingAgenda $agenda): RedirectResponse
    {
        $this->authorize('manage-landing-page');

        $image = $agenda->image;

        DB::transaction(static fn () => $agenda->delete());

        if ($image !== null) {
            Storage::disk('public')->delete($image);
        }

        AktivitasSistem::catat(
            'landing_agenda.deleted',
            $request->user(),
            $agenda,
            ['status' => $agenda->status],
            'Agenda landing page "'.$agenda->title.'" dihapus.'
        );

        return redirect()
            ->route('landing.agendas.index')
            ->with('success', 'Agenda kegiatan berhasil dihapus.');
    }

    private function storeImage(LandingAgendaRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('landing/agendas', 'public');

        if ($path === false) {
            abort(500, 'Gagal menyimpan gambar agenda.');
        }

        return $path;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'agenda';
        $slug = $base;
        $counter = 2;

        while (LandingAgenda::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedValue(mixed $value, array $allowed): ?string
    {
        $value = (string) $value;

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }
}
