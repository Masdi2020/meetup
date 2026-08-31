<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;

// use ILluminate\Http\UploadedFile;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class BookingService
{
    /**
     * Summary of history
     *
     * @return Collection<int, array{
     *      id: int,
     *      room: string,
     *      date: non-falsy-string,
     *      time: non-falsy-string,
     *      title: string,
     *      status: string
     * }>
     */
    public function history(int $userId): Collection
    {
        return Booking::with([
            'room:id,name',
            'status:id,code,label',
        ])->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Booking $booking): array {
                return [
                    'id' => (int) $booking->id,
                    'room' => (string) $booking->room->name,
                    'date' => (string) $booking->date->format('d F Y'),
                    'time' => (string) $booking->start_time->format('H:i').' - '.$booking->end_time->format('H:i'),
                    'title' => (string) $booking->title,
                    'status' => ucfirst(strtolower((string) $booking->status->code)),
                ];
            });
    }
}
