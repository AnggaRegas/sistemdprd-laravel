<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Models\AktivitasSistem;
use App\Models\DprdCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-rdp-archives');

        $commissions = DprdCommission::query()
            ->withCount([
                'rdpMeetings as rdp_count',
                'documents as document_count',
            ])
            ->orderBy('id')
            ->get();

        return view('commissions.index', [
            'commissions' => $commissions,
            'totalRdp' => $commissions->sum('rdp_count'),
            'totalDocuments' => $commissions->sum('document_count'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-rdp-archives');

        return view('commissions.create');
    }

    public function store(CommissionRequest $request): RedirectResponse
    {
        $this->authorize('manage-rdp-archives');

        $validated = $request->validated();
        $keywords = $this->parsedKeywords($validated['keywords_text'] ?? '');

        $commission = DB::transaction(function () use ($validated, $keywords) {
            return DprdCommission::query()->create([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'field' => $validated['field'] ?: null,
                'description' => $validated['description'] ?: null,
                'keywords' => $keywords,
                'is_active' => (bool) $validated['is_active'],
            ]);
        });

        AktivitasSistem::catat(
            'komisi.created',
            $request->user(),
            $commission,
            ['is_active' => $commission->is_active],
            'Komisi '.$commission->name.' ditambahkan.'
        );

        return redirect()
            ->route('komisi.index')
            ->with('success', 'Komisi berhasil ditambahkan.');
    }

    public function edit(DprdCommission $commission): View
    {
        $this->authorize('manage-rdp-archives');

        $commission->loadCount([
            'rdpMeetings as rdp_count',
            'documents as document_count',
        ]);

        return view('commissions.edit', [
            'commission' => $commission,
        ]);
    }

    public function update(CommissionRequest $request, DprdCommission $commission): RedirectResponse
    {
        $this->authorize('manage-rdp-archives');

        $validated = $request->validated();
        $nameChanged = $commission->name !== $validated['name'];
        $keywords = $this->parsedKeywords($validated['keywords_text'] ?? '');

        DB::transaction(function () use ($commission, $validated, $nameChanged, $keywords) {
            $attributes = [
                'name' => $validated['name'],
                'field' => $validated['field'] ?: null,
                'description' => $validated['description'] ?: null,
                'keywords' => $keywords,
                'is_active' => (bool) $validated['is_active'],
            ];

            if ($nameChanged) {
                $attributes['slug'] = $this->uniqueSlug($validated['name'], $commission->id);
            }

            $commission->update($attributes);
        });

        AktivitasSistem::catat(
            'komisi.updated',
            $request->user(),
            $commission,
            ['is_active' => $commission->is_active],
            'Komisi '.$commission->name.' diperbarui.'
        );

        return redirect()
            ->route('komisi.index')
            ->with('success', 'Komisi berhasil diperbarui.');
    }

    public function destroy(Request $request, DprdCommission $commission): RedirectResponse
    {
        $this->authorize('manage-rdp-archives');

        if ($commission->rdpMeetings()->exists()) {
            return back()->with(
                'error',
                'Komisi tidak dapat dihapus karena masih memiliki RDP. Nonaktifkan komisi atau pindahkan RDP terlebih dahulu.'
            );
        }

        DB::transaction(function () use ($commission) {
            $commission->delete();
        });

        AktivitasSistem::catat(
            'komisi.deleted',
            $request->user(),
            $commission,
            [],
            'Komisi '.$commission->name.' dihapus (soft delete).'
        );

        return redirect()
            ->route('komisi.index')
            ->with('success', 'Komisi berhasil dihapus.');
    }

    /**
     * @return array<int, array{term: string, weight: int}>
     */
    private function parsedKeywords(string $text): array
    {
        $parsed = DprdCommission::parseKeywordsText($text);

        if ($parsed['errors'] !== []) {
            throw ValidationException::withMessages([
                'keywords_text' => $parsed['errors'],
            ]);
        }

        return $parsed['keywords'];
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'komisi';
        $slug = $base;
        $suffix = 2;

        while (
            DprdCommission::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
