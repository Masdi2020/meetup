<?php

namespace App\Services;

use App\Models\Booking;

class BannerService
{
    /**
     * @return array{
     *      booking: Booking | null,
     *      next_change: string | null,
     *      now: string
     * }
     */
    public function current(): array
    {
        $now = now();

        $baseQuery = Booking::query()
            ->where('room_id', 1)
            ->whereDate('date', $now->toDateString())
            ->whereHas('status', fn ($q) => $q->where('code', 'APPROVED'));

        $booking = (clone $baseQuery)
            ->with('attachments')
            ->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>', $now->toTimeString())
            ->first();

        $nextBooking = (clone $baseQuery)
            ->whereTime('start_time', '>', $now->toTimeString())
            ->orderBy('start_time')
            ->first();

        $nextChange = null;

        if ($booking) {
            $nextChange = $now->copy()
                ->setDateFrom($booking->date)
                ->setTimeFromTimeString($booking->end_time->format('H:i:s'))
                ->toIso8601String();
        } elseif ($nextBooking) {
            $nextChange = $now->copy()
                ->setDateFrom($nextBooking->date)
                ->setTimeFromTimeString($nextBooking->start_time->format('H:i:s'))
                ->toIso8601String();
        }

        return [
            'booking' => $booking,
            'next_change' => $nextChange,
            'now' => $now->toIso8601String(),
        ];
    }
}
