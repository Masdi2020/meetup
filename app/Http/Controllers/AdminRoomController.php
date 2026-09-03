<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRoomRequest;
use App\Models\Facility;
use App\Models\Room;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoomController extends Controller
{
    public function __construct(private AuditService $audits) {}

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

    public function store(SaveRoomRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $room = Room::create([
                'name' => $validated['name'],
                'capacity' => $validated['capacity'],
                'location' => $validated['location'],
                'is_available' => $validated['is_available'] ?? true,
            ]);

            if (isset($validated['facilities'])) {
                $room->facilities()->sync($validated['facilities']);
            }

            $this->audits->record(
                'Room',
                $room->id,
                'created',
                null,
                $this->auditValues($room),
                $request->user()->id,
                'ruangan dibuat oleh admin',
            );
        });

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(SaveRoomRequest $request, Room $room): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $room) {
            $oldValues = $this->auditValues($room);

            $room->update([
                'name' => $validated['name'],
                'capacity' => $validated['capacity'],
                'location' => $validated['location'],
                'is_available' => $validated['is_available'] ?? $room->is_available,
            ]);

            if (isset($validated['facilities'])) {
                $room->facilities()->sync($validated['facilities']);
            }

            $this->audits->record(
                'Room',
                $room->id,
                'updated',
                $oldValues,
                $this->auditValues($room),
                $request->user()->id,
                'ruangan diperbarui oleh admin',
            );
        });

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function toggleAvailability(Request $request, Room $room): RedirectResponse
    {
        DB::transaction(function () use ($request, $room) {
            $oldAvailability = (bool) $room->is_available;

            $room->update([
                'is_available' => ! $oldAvailability,
            ]);

            $this->audits->record(
                'Room',
                $room->id,
                'availability_changed',
                ['is_available' => $oldAvailability],
                ['is_available' => (bool) $room->is_available],
                $request->user()->id,
                $room->is_available ? 'ruangan diaktifkan oleh admin' : 'ruangan dinonaktifkan oleh admin',
            );
        });

        return redirect()->back()->with('success', $room->is_available ? 'Ruangan diaktifkan.' : 'Ruangan dinonaktifkan.');
    }

    /** @return array<string, mixed> */
    private function auditValues(Room $room): array
    {
        $room->load('facilities:id');

        return [
            'name' => $room->name,
            'capacity' => $room->capacity,
            'location' => $room->location,
            'is_available' => (bool) $room->is_available,
            'facility_ids' => $room->facilities->pluck('id')->sort()->values()->all(),
        ];
    }
}
