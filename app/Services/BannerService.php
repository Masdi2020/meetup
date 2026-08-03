<?php

namespace App\Services;

use App\Models\Booking;

class BannerService
{
    public function current(): array
    {
        $now = now();

        $booking = Booking::query()
            ->with('attachments')
            ->whereDate('date', $now->toDateString())
            ->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>', $now->toTimeString())
            ->whereHas('status', fn ($q) => $q->where('code', 'APPROVED')
            )->first();

        $nextBooking = Booking::query()
            ->whereDate('date', $now->toDateString())
            ->whereTime('start_time', '>', $now->toTimeString())
            ->whereHas('status', fn ($q) => $q->where('code', 'APPROVED'))
            ->orderBy('start_time', 'asc')
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
        ];
    }
}
