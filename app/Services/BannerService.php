<?php

namespace App\Services;

use App\Models\Booking;

class BannerService
{
    public function current(): array
    {
        $now = now();

        $baseQuery = Booking::query()
            ->where("room_id",1)
            ->whereDate('date', $now->toDateString())
            ->whereHas('status', fn ($q) => $q->where('code', 'APPROVED'));

        $booking = (clone $baseQuery)
            ->with('attachments')
            ->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>', $now->toTimeString())
            ->first();

        $nextBooking = (clone $booking)
            ->whereTime('start_time', '>', $now->toTimeString())
            ->orderBy('start_time')
            ->first();

        $nextChange = null;

        if ($booking) {
            $nextChange = $booking->date->format('Y-m-d') . ' ' . $booking->end_time;
        } elseif ($nextBooking) {
            $nextChange = $nextBooking->date->format('Y-m-d') . ' ' . $nextBooking->start_time;
        }

        return [
            'booking' => $booking,
            'next_change' => $nextChange,
            'now' => $now->toIso8601String(),
        ];
    }
}
