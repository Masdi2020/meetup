<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminFacilityController extends Controller
{
    public function index(): Response
    {
        $facilities = Facility::query()
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Facility', [
            'facilities' => $facilities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('facilities', 'name'),
            ],
        ]);

        Facility::create($validated);

        return redirect()->back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('facilities', 'name')->ignore($facility->id),
            ],
        ]);

        $facility->update($validated);

        return redirect()->back()->with('success', 'Fasilitas berhasil diperbarui.');
    }
}
