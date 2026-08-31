<?php

namespace App\Services;

use App\Events\BannerUpdated;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingStatus;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingWorkflowService
{
    public function __construct(private AuditService $audits) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, int $userId, bool $isAdmin): Booking
    {
        if (! Room::query()->whereKey($data['room_id'])->where('is_available', true)->exists()) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan tidak tersedia untuk dipinjam']);
        }

        $overlaps = Booking::query()
            ->where('room_id', $data['room_id'])->where('date', $data['date'])
            ->whereHas('status', fn ($query) => $query->whereIn('code', ['PENDING', 'APPROVED']))
            ->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan sudah dibooking pada waktu tersebut.']);
        }

        $statusCode = $isAdmin && in_array($data['status'] ?? null, ['pending', 'approved'], true)
            ? strtoupper($data['status']) : 'PENDING';

        $booking = DB::transaction(function () use ($data, $userId, $statusCode) {
            $statusId = BookingStatus::where('code', $statusCode)->firstOrFail()->id;
            $approved = $statusCode === 'APPROVED';
            $booking = Booking::create([
                'room_id' => $data['room_id'], 'user_id' => $userId, 'date' => $data['date'],
                'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'title' => $data['title'],
                'participants_count' => $data['participants'], 'notes' => $data['request'] ?? null,
                'status_id' => $statusId, 'processed_by' => $approved ? $userId : null,
                'processed_at' => $approved ? now() : null,
                'processed_notes' => $approved ? 'Disetujui oleh admin saat dibuat' : null,
            ]);
            $this->audits->record('Booking', $booking->id, 'created', null, ['status_id' => $statusId], $userId, $approved ? 'booking dibuat dan disetujui' : 'booking dibuat');

            if (($data['banner'] ?? null) instanceof UploadedFile) {
                $file = $data['banner'];
                $path = $file->store('banners', 'public');
                BookingAttachment::create([
                    'booking_id' => $booking->id, 'original_filename' => $file->getClientOriginalName(),
                    'filename' => $file->hashName(), 'path' => $path, 'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(), 'uploaded_by' => $userId,
                ]);
            }

            return $booking;
        });

        if ($statusCode === 'APPROVED') {
            broadcast(new BannerUpdated);
        }

        return $booking;
    }

    public function changeStatus(Booking $booking, string $statusCode, int $userId, string $notes): void
    {
        $booking->loadMissing('status');
        $allowedTransitions = [
            'PENDING' => ['APPROVED', 'REJECTED', 'CANCELLED'],
            'APPROVED' => ['FINISHED'],
        ];

        if (! in_array($statusCode, $allowedTransitions[$booking->status->code] ?? [], true)) {
            throw ValidationException::withMessages(['booking' => 'Perubahan status booking tidak valid.']);
        }

        DB::transaction(function () use ($booking, $statusCode, $userId, $notes) {
            $oldStatusId = $booking->status_id;
            $status = BookingStatus::where('code', $statusCode)->firstOrFail();
            $booking->forceFill([
                'status_id' => $status->id, 'processed_by' => $userId,
                'processed_at' => now(), 'processed_notes' => $notes,
            ])->save();
            $comments = ['APPROVED' => 'booking disetujui', 'REJECTED' => 'booking ditolak', 'CANCELLED' => 'Dibatalkan oleh peminjam', 'FINISHED' => 'booking diakhiri'];
            $this->audits->record('Booking', $booking->id, 'status_changed', ['status_id' => $oldStatusId], ['status_id' => $status->id], $userId, $comments[$statusCode]);
        });

        if (in_array($statusCode, ['APPROVED', 'FINISHED'], true)) {
            broadcast(new BannerUpdated);
        }
    }
}
