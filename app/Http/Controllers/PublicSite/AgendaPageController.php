<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\LandingAgenda;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaPageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $agendas = LandingAgenda::query()
            ->published()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->paginate(9)
            ->withQueryString();

        return view('public.agenda.index', [
            'agendas' => $agendas,
            'search' => mb_substr($search, 0, 100),
        ]);
    }

    public function archive(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // Agenda yang telah lewat tetap tersimpan dan dapat diakses publik
        // melalui arsip; tidak ada penghapusan otomatis.
        $agendas = LandingAgenda::query()
            ->published()
            ->whereDate('event_date', '<', now()->toDateString())
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('event_date')
            ->orderByDesc('start_time')
            ->paginate(12)
            ->withQueryString();

        return view('public.agenda.arsip', [
            'agendas' => $agendas,
            'search' => mb_substr($search, 0, 100),
        ]);
    }

    public function show(string $slug): View
    {
        $agenda = LandingAgenda::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $otherAgendas = LandingAgenda::query()
            ->published()
            ->whereKeyNot($agenda->id)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(3)
            ->get();

        return view('public.agenda.show', [
            'agenda' => $agenda,
            'otherAgendas' => $otherAgendas,
        ]);
    }
}
