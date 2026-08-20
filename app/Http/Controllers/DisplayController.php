<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function index(): Response
    {
        $rooms = Room::query()
            ->with('facilities:id,name')
            ->select([
                'id',
                'name',
                'location',
                'capacity',
                'is_available',
            ])
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'location' => $room->location,
                'capacity' => $room->capacity,
                'is_available' => $room->is_available,
                'facilities' => $room->facilities
                    ->pluck('name')
                    ->values()
                    ->all(),
            ]);

        return Inertia::render('Display', [
            'rooms' => $rooms,
        ]);
    }
}
