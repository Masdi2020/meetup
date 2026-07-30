<?php

namespace App\Services;

use App\Models\Booking;

// use ILluminate\Http\UploadedFile;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class BookingService
{
    public function history(int $userId)
    {
        return Booking::with([
            'room:id,name',
            'status:id,label',
        ])->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'room' => $booking->room->name,
                    'date' => $booking->date->format('d F Y'),
                    'time' => $booking->start_time->format('H:i').' - '.$booking->end_time->format('H:i'),
                    'title' => $booking->title,
                    'status' => $booking->status->label,
                ];
            });
    }
}
