<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminRoomController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $rooms = Room::query()
            ->with('facilities:id,name')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'floor' => $room->floor,
                    'capacity' => $room->capacity,
                    'calendar_url' => $room->calendar_url,
                    'facilities' => $room->facilities->pluck('name'),
                ];
            });

        return Inertia::render('Admin/Room', [
            'rooms' => $rooms,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
