<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatus;
use Illuminate\Support\Collection;

// use ILluminate\Http\UploadedFile;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class BookingService
{
    /**
     * @return Collection<int, array{start_time: non-falsy-string, end_time: non-falsy-string}>
     */
    public function activeIntervals(int $roomId, string $date, ?int $ignoreBookingId = null): Collection
    {
        return Booking::query()
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->whereHas('status', fn ($query) => $query->whereIn('code', ['PENDING', 'APPROVED']))
            ->orderBy('start_time')
            ->get(['start_time', 'end_time'])
            ->map(fn (Booking $booking) => [
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
            ]);
    }

    /**
     * Summary of history
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function history(int $userId): Collection
    {
        return Booking::with([
            'room:id,name',
            'status:id,code,label',
            'documentations:id,booking_id,original_filename,path,mime_type,size,type',
            'meetingMinutes:id,booking_id,original_filename,path,mime_type,size,type',
        ])->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (Booking $booking): array => $this->formatHistoryBooking($booking));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatHistoryBooking(Booking $booking): array
    {
        return [
            'id' => (int) $booking->id,
            'room' => (string) $booking->room->name,
            'date' => (string) $booking->date->format('d/m/Y'),
            'time' => (string) $booking->start_time->format('H:i').' - '.$booking->end_time->format('H:i'),
            'title' => (string) $booking->title,
            'status' => ucfirst(strtolower((string) $booking->status->code)),
            'results_available' => $booking->status->code === 'FINISHED',
            'documentations' => $booking->documentations->map(fn ($attachment) => [
                'id' => (int) $attachment->id,
                'original_filename' => (string) $attachment->original_filename,
                'path' => (string) $attachment->path,
                'mime_type' => (string) $attachment->mime_type,
            ])->values()->all(),
            'meeting_minutes' => $booking->meetingMinutes ? [
                'id' => (int) $booking->meetingMinutes->id,
                'original_filename' => (string) $booking->meetingMinutes->original_filename,
                'path' => (string) $booking->meetingMinutes->path,
                'mime_type' => (string) $booking->meetingMinutes->mime_type,
            ] : null,
        ];
    }

    /** @return Collection<int, array{value: string, label: string}> */
    public function historyStatuses(): Collection
    {
        return BookingStatus::query()
            ->select(['code', 'label'])
            ->orderBy('id')
            ->get()
            ->map(fn (BookingStatus $status) => [
                'value' => ucfirst(strtolower($status->code)),
                'label' => ucfirst(strtolower($status->label)),
            ]);
    }
}
