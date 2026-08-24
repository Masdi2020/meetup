<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(
        Request $request,
        CalendarService $calendar
    ): Response {
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $rooms = Room::with('facilities')->get();

        $roomValue = $request->input('room');
        $roomId = null;
        $selectedRoomId = 0;

        if ($roomValue !== null && $roomValue !== '' && (string) $roomValue !== '0') {
            $roomId = (int) $roomValue;
            $selectedRoomId = $roomId;
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
            'selectedRoomId' => $selectedRoomId,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
