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
            ->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->whereHas('status', fn ($q) =>
                $q->where('code', 'APPROVED')
            )->with('room')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($booking) => [
                'id'=> $booking->id,
                'title'=> $booking->title,
                'room'=> $booking->room->name,
                'date'=> $booking->date,
                'start_time'=> $booking->start_time,
                'end_time'=> $booking->end_time,
            ])
            ->all();

    }
}
