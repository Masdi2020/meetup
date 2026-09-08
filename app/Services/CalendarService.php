<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonInterface;

class CalendarService
{
    /**
     * @param  int|array<int, int>|null  $roomId
     * @return array<int, array{
     *      id: int,
     *      title: string,
     *      status: string,
     *      room: string,
     *      date: string,
     *      start_time: string,
     *      end_time: string,
     *      borrower: string,
     *      participants_count: int,
     *      notes: string|null
     * }>
     */
    public function events(
        CarbonInterface $start,
        CarbonInterface $end,
        int|array|null $roomId = null
    ): array {
        return Booking::query()
            ->with(['room', 'status', 'user'])
            ->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->when($roomId !== null, function ($query) use ($roomId) {
                $query->whereIn('room_id', (array) $roomId);
            })
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['PENDING', 'APPROVED', 'FINISHED']);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'status' => $booking->status->code,
                    'room' => $booking->room->name,
                    'date' => $booking->date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                    'borrower' => $booking->user->name,
                    'participants_count' => $booking->participants_count,
                    'notes' => $booking->notes,
                ];
            })
            ->all();
    }
}
