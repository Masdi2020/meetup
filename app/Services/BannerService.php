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
            ->whereTime('start_time', '<=', $now->format('H:i:s'))
            ->whereTime('end_time', '>', $now->format('H:i:s'))
            ->whereHas('status', fn ($q) => $q->where('code', 'APPROVED')
            )->first();

        $nextStart = Booking::query()
            ->whereDate('date', $now->toDateString())
            ->whereTime('start_time', '>', $now->format('H:i:s'))
            ->orderBy('start_time', 'asc')
            ->value('start_time');

        $nextChange = $booking ? $booking->end_time : $nextStart;

        return [
            'booking' => $booking,
            'next_change' => $nextChange,
        ];
    }
}
