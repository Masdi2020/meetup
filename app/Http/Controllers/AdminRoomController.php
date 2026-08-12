<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $rooms = Room::query()
            ->with('facilities:id,name')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'location' => $room->location,
                    'capacity' => $room->capacity,
                    'is_available' => (bool) $room->is_available,
                    'facilities' => $room->facilities->pluck('name')->values()->all(),
                    'facility_ids' => $room->facilities->pluck('id')->values()->all(),
                ];
            });

        return Inertia::render('Admin/Room', [
            'rooms' => $rooms,
            'facilities' => Facility::query()
                ->select(['id', 'name'])
                ->get()
                ->sortBy('name')
                ->values()
                ->all(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
            'facilities' => ['sometimes', 'array'],
            'facilities.*' => ['integer', 'exists:facilities,id'],
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'is_available' => $validated['is_available'] ?? true,
        ]);

        if (isset($validated['facilities'])) {
            $room->facilities()->sync($validated['facilities']);
        }

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
            'facilities' => ['sometimes', 'array'],
            'facilities.*' => ['integer', 'exists:facilities,id'],
        ]);

        $room->update([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'is_available' => $validated['is_available'] ?? $room->is_available,
        ]);

        if (isset($validated['facilities'])) {
            $room->facilities()->sync($validated['facilities']);
        }

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function toggleAvailability(Room $room): RedirectResponse
    {
        $room->update([
            'is_available' => ! $room->is_available,
        ]);

        return redirect()->back()->with('success', $room->is_available ? 'Ruangan diaktifkan.' : 'Ruangan dinonaktifkan.');
    }
}
