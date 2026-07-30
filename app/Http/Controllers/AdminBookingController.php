<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::query()
            ->with([
                'room:id,name',
                'user:id,name',
                'status:id,code,label',
            ]);

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('room', function ($room) use ($search) {
                        $room->where('name', 'like', "%{$search}%");
                    });

            });

        }

        if ($request->filled('status')) {

            $query->whereHas('status', function ($q) use ($request) {
                $q->where('code', strtoupper($request->status));
            });

        }

        if ($request->filled('room')) {

            $query->where('room_id', $request->room);

        }

        $bookings = $query
            ->latest()
            ->paginate(10)
            ->through(function (Booking $booking) {

                return [

                    'id' => $booking->id,

                    'code' => $booking->id,

                    'room' => $booking->room->name,

                    'borrower' => $booking->user->name,

                    'activity' => $booking->title,

                    'date' => $booking->date->format('d-m-Y'),

                    'start' => $booking->start_time->format('H:i'),

                    'end' => $booking->end_time->format('H:i'),

                    'status' => strtolower($booking->status->code),

                ];

            });

        return Inertia::render('Admin/Booking', [

            'bookings' => $bookings,

            'rooms' => Room::select('id', 'name')->orderBy('name')->get(),

            'stats' => [

                'total' => Booking::count(),

                'pending' => Booking::whereHas('status', fn ($q) => $q->where('code', 'PENDING'))->count(),

                'approved' => Booking::whereHas('status', fn ($q) => $q->where('code', 'APPROVED'))->count(),

                'finished' => Booking::whereHas('status', fn ($q) => $q->where('code', 'FINISHED'))->count(),

            ],

            'filters' => $request->only([
                'search',
                'status',
                'room',
            ]),

        ]);
    }
}
