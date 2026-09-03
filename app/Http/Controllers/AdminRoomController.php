<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRoomRequest;
use App\Models\Facility;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoomController extends Controller
{
    public function __construct(private RoomService $rooms) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/Room', [
            'rooms' => $this->rooms->adminList($search),
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

        $this->rooms->create($validated, $request->user()->id);

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(SaveRoomRequest $request, Room $room): RedirectResponse
    {
        $validated = $request->validated();

        $this->rooms->update($room, $validated, $request->user()->id);

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function toggleAvailability(Request $request, Room $room): RedirectResponse
    {
        $this->rooms->toggleAvailability($room, $request->user()->id);

        return redirect()->back()->with('success', $room->is_available ? 'Ruangan diaktifkan.' : 'Ruangan dinonaktifkan.');
    }
}
