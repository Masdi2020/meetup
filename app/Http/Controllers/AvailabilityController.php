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
        $requestedView = $request->string('view')->toString();
        $view = in_array($requestedView, ['month', 'week', 'day'], true)
            ? $requestedView
            : 'month';

        try {
            $date = $request->filled('date')
                ? Carbon::createFromFormat('Y-m-d', $request->string('date')->toString())->startOfDay()
                : Carbon::create($year, $month, 1)->startOfDay();
        } catch (\Throwable) {
            $date = now()->startOfDay();
        }

        $month = $date->month;
        $year = $date->year;

        $rooms = Room::with('facilities')->get();

        $roomValue = $request->input('room');
        $roomId = null;
        $selectedRoomId = 0;

        if ($roomValue !== null && $roomValue !== '' && (string) $roomValue !== '0') {
            $roomId = (int) $roomValue;
            $selectedRoomId = $roomId;
        }

        // Include complete edge weeks so cross-month week views stay complete.
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

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
            'date' => $date->toDateString(),
            'view' => $view,
        ]);
    }
}
