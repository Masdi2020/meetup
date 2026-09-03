<?php

namespace App\Services;

use App\Models\Room;
use Carbon\Carbon;

class AvailabilityService
{
    public function __construct(private CalendarService $calendar) {}

    /** @return array<string, mixed> */
    public function data(
        int $month,
        int $year,
        string $requestedView,
        ?string $requestedDate,
        mixed $roomValue,
    ): array {
        $view = in_array($requestedView, ['month', 'week', 'day'], true)
            ? $requestedView
            : 'month';

        try {
            $date = $requestedDate
                ? Carbon::createFromFormat('Y-m-d', $requestedDate)->startOfDay()
                : Carbon::create($year, $month, 1)->startOfDay();
        } catch (\Throwable) {
            $date = now()->startOfDay();
        }

        $roomId = $roomValue !== null
            && $roomValue !== ''
            && (string) $roomValue !== '0'
                ? (int) $roomValue
                : null;

        // Include complete edge weeks so cross-month week views stay complete.
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return [
            'rooms' => Room::with('facilities')->get(),
            'events' => $this->calendar->events($start, $end, $roomId),
            'selectedRoomId' => $roomId ?? 0,
            'month' => $date->month,
            'year' => $date->year,
            'date' => $date->toDateString(),
            'view' => $view,
        ];
    }
}
