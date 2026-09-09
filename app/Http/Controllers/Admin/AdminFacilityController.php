<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveFacilityRequest;
use App\Models\Facility;
use App\Services\Admin\FacilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFacilityController extends Controller
{
    public function __construct(private FacilityService $facilities) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Admin/Facility', [
            'facilities' => $this->facilities->search($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(SaveFacilityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->facilities->create($validated, $request->user()->id);

        return redirect()->back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(SaveFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validated();

        $this->facilities->update($facility, $validated, $request->user()->id);

        return redirect()->back()->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Request $request, Facility $facility): RedirectResponse
    {
        $this->facilities->delete($facility, $request->user()->id);

        return back()->with('success', 'Fasilitas berhasil dihapus');
    }
}
