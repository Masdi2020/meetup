<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class CalendarService
{
    /**
     * Create a new class instance.
     */
    public function events(
        Carbon $start,
        Carbon $end,
        ?int $roomId = null
    ): array {
        return Booking::query()
            ->with(['room', 'status'])
            ->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->when($roomId, function ($query) use ($roomId) {
                $query->where('room_id', $roomId);
            })
            ->whereHas('status', function ($query) use ($roomId) {
                $query->where('code', 'APPROVED');
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'room' => $booking->room->name,
                    'date' => $booking->date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                ];
            })
            ->all();
    }
}
