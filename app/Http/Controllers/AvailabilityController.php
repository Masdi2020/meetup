<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalendarService;
use Carbon\Carbon;
use App\Models\Room;
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

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        return Inertia::render('Availability', [
            'rooms' => Room::all(),
            'events' => $calendar->events($start, $end),
            'month' => $month,
            'year' => $year,
        ]);
    }
}
