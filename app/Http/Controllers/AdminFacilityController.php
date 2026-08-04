<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'name' => ['required', 'max:255'],
        ]);

        Facility::create($validated);

        return redirect()->back();
    }
}
