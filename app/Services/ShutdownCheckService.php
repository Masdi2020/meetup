<?php

namespace App\Services;

use App\Models\Booking;

class ShutdownCheckService
{
    /** @return array{action: string, reason?: string, check_at?: string} */
    public function decision(): array
    {
        $now = now();

        // Jumat 16:30, Senin-Kamis 16:00
        $closingTime = $now->copy()->setTime(
            16,
            $now->isFriday() ? 30 : 0,
            0
        );

        if ($now->lt($closingTime)) {
            return [
                'action' => 'wait',
                'reason' => 'before_closing_time',
                'check_at' => $closingTime->format('Y-m-d H:i:s'),
            ];
        }

        $latestBooking = Booking::query()
            ->whereDate('date', $now->toDateString())
            ->whereHas('status', function ($query) {
                $query->where('code', 'APPROVED');
            })
            ->whereTime('end_time', '>', $now->format('H:i:s'))
            ->orderByDesc('end_time')
            ->first();

        if ($latestBooking) {
            return [
                'action' => 'wait',
                'reason' => 'active_booking',
                'check_at' => $now->toDateString().' '.$latestBooking->end_time->format('H:i:s'),
            ];
        }

        return [
            'action' => 'shutdown',
        ];
    }
}
