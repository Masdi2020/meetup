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

        $rooms = Room::with('facilities')->get();
        $roomIds = $roomValue === null || $roomValue === '' || $roomValue === '0' || $roomValue === 0
            ? $rooms->pluck('id')->all()
            : array_values(array_intersect(
                $rooms->pluck('id')->all(),
                array_map('intval', (array) $roomValue),
            ));

        // Include complete edge weeks so cross-month week views stay complete.
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return [
            'rooms' => $rooms,
            'events' => $this->calendar->events($start, $end, $roomIds),
            'selectedRoomIds' => $roomIds,
            'month' => $date->month,
            'year' => $date->year,
            'date' => $date->toDateString(),
            'view' => $view,
        ];
    }
}
