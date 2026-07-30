<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use Inertia\Inertia;

class AdminFacilityController extends Controller
{
    public function index() {
        $facilities = Facility::query()
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Facility', [
            'facilities' => $facilities,
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
        ]);

        Facility::create($validated);

        return redirect()->back();
    }
}
