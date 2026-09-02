<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFacilityRequest;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFacilityController extends Controller
{
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

        Facility::create($validated);

        return redirect()->back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(SaveFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validated();

        $facility->update($validated);

        return redirect()->back()->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete();

        return back()->with('success', 'Fasilitas berhasil dihapus');
    }
}
