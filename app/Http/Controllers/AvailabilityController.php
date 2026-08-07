<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalendarService;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Booking;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index (
        Request $request,
        CalendarService $calendar
    ): Response {
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $rooms = Room::with('facilities')->get();

        $roomValue = $request->input('room');
        $roomId = $roomValue !== null && $roomValue !== '' ? (int) $roomValue : null;

        if ($roomId === null && $rooms->isNotEmpty()) {
            $roomId = $rooms->first()->id;
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        return Inertia::render('Availability', [
            'rooms' => $rooms,
            'events' => $calendar->events(
                start: $start,
                end: $end,
                roomId: $roomId,
            ),
            'selectedRoomId' => $roomId,
            'month' => $month,
            'year'=> $year,
        ]);
    }
}
