<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFacilityRequest;
use App\Models\Facility;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminFacilityController extends Controller
{
    public function __construct(private AuditService $audits) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $facilities = Facility::query()
            ->with('rooms:id,name')
            ->withCount('rooms')
            ->when(
                $search !== '',
                fn ($query) => $query->where('name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Facility', [
            'facilities' => $facilities,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(SaveFacilityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $facility = Facility::create($validated);

            $this->audits->record(
                'Facility',
                $facility->id,
                'created',
                null,
                $facility->only(['name']),
                $request->user()->id,
                'fasilitas dibuat oleh admin',
            );
        });

        return redirect()->back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(SaveFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($facility, $validated, $request) {
            $oldValues = $facility->only(['name']);
            $facility->update($validated);

            $this->audits->record(
                'Facility',
                $facility->id,
                'updated',
                $oldValues,
                $facility->only(['name']),
                $request->user()->id,
                'fasilitas diperbarui oleh admin',
            );
        });

        return redirect()->back()->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Request $request, Facility $facility): RedirectResponse
    {
        DB::transaction(function () use ($facility, $request) {
            $oldValues = $facility->only(['name']);
            $facility->delete();

            $this->audits->record(
                'Facility',
                $facility->id,
                'deleted',
                $oldValues,
                null,
                $request->user()->id,
                'fasilitas dihapus oleh admin',
            );
        });

        return back()->with('success', 'Fasilitas berhasil dihapus');
    }
}
