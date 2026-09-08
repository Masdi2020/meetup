<?php

namespace App\Services;

use App\Enums\BookingAttachmentType;
use App\Events\BannerUpdated;
use App\Events\BookingsUpdated;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingStatus;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookingWorkflowService
{
    public function __construct(private AuditService $audits) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, int $actorUserId, bool $isAdmin): Booking
    {
        if (! Room::query()->whereKey($data['room_id'])->where('is_available', true)->exists()) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan tidak tersedia untuk dipinjam']);
        }

        $statusCode = $isAdmin ? (string) $data['status'] : 'PENDING';
        $borrowerId = $isAdmin ? (int) $data['user_id'] : $actorUserId;
        $activeStatus = in_array($statusCode, ['PENDING', 'APPROVED'], true);
        $overlaps = $activeStatus && Booking::query()
            ->where('room_id', $data['room_id'])
            ->whereDate('date', $data['date'])
            ->whereHas('status', fn ($query) => $query->whereIn('code', ['PENDING', 'APPROVED']))
            ->whereTime('start_time', '<', $data['end_time'])
            ->whereTime('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan sudah dibooking pada waktu tersebut.']);
        }

        $booking = DB::transaction(function () use ($data, $actorUserId, $borrowerId, $statusCode) {
            $statusId = BookingStatus::where('code', $statusCode)->firstOrFail()->id;
            $processed = $statusCode !== 'PENDING';
            $booking = Booking::create([
                'room_id' => $data['room_id'], 'user_id' => $borrowerId, 'date' => $data['date'],
                'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'title' => $data['title'],
                'participants_count' => $data['participants'], 'notes' => $data['request'] ?? null,
                'status_id' => $statusId, 'processed_by' => $processed ? $actorUserId : null,
                'processed_at' => $processed ? now() : null,
                'processed_notes' => $processed ? "Status {$statusCode} ditetapkan oleh admin saat dibuat" : null,
            ]);
            $this->audits->record(
                'Booking',
                $booking->id,
                'created',
                null,
                [
                    'room_id' => (int) $data['room_id'],
                    'user_id' => $borrowerId,
                    'date' => (string) $data['date'],
                    'start_time' => (string) $data['start_time'],
                    'end_time' => (string) $data['end_time'],
                    'title' => (string) $data['title'],
                    'participants_count' => (int) $data['participants'],
                    'notes' => $data['request'] ?? null,
                    'status_id' => $statusId,
                ],
                $actorUserId,
                'booking dibuat oleh '.($actorUserId === $borrowerId ? 'peminjam' : 'admin'),
            );

            if (($data['banner'] ?? null) instanceof UploadedFile) {
                $file = $data['banner'];

                if (! $file->isValid()) {
                    throw ValidationException::withMessages([
                        'banner' => 'File banner tidak valid.',
                    ]);
                }

                $sourcePath = $file->getPathname();

                if ($sourcePath === '' || ! is_file($sourcePath)) {
                    throw ValidationException::withMessages([
                        'banner' => 'File sementara banner tidak ditemukan.',
                    ]);
                }

                $filename = $file->hashName();
                $path = "banners/{$filename}";

                $stream = fopen($sourcePath, 'rb');

                if ($stream === false) {
                    throw new RuntimeException('Gagal membuka file banner.');
                }

                try {
                    if (! Storage::disk('public')->writeStream($path, $stream)) {
                        throw new RuntimeException('Gagal menyimpan file banner');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                $attachment = BookingAttachment::create([
                    'booking_id' => $booking->id,
                    'original_filename' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'type' => BookingAttachmentType::Banner,
                    'uploaded_by' => $actorUserId,
                ]);

                $this->audits->record(
                    'BookingAttachment',
                    $attachment->id,
                    'created',
                    null,
                    [
                        'booking_id' => $booking->id,
                        'original_filename' => $attachment->original_filename,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                    ],
                    $actorUserId,
                    'banner booking diunggah',
                );
            }

            return $booking;
        });

        BookingsUpdated::dispatch();

        if ($statusCode === 'APPROVED') {
            broadcast(new BannerUpdated);
        }

        return $booking;
    }

    /** @param array{title: string, date: string, start_time: string, end_time: string} $data */
    public function update(
        Booking $booking,
        array $data,
        int $userId,
        string $auditComment = 'booking diperbarui oleh peminjam',
    ): void {
        $overlaps = Booking::query()
            ->whereKeyNot($booking->id)
            ->where('room_id', $booking->room_id)
            ->whereDate('date', $data['date'])
            ->whereHas('status', fn ($query) => $query->whereIn('code', ['PENDING', 'APPROVED']))
            ->whereTime('start_time', '<', $data['end_time'])
            ->whereTime('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'date' => 'Ruangan sudah dibooking pada waktu tersebut.',
            ]);
        }

        $oldValues = [
            'title' => $booking->title,
            'date' => $booking->date->format('Y-m-d'),
            'start_time' => $booking->start_time->format('H:i'),
            'end_time' => $booking->end_time->format('H:i'),
        ];

        DB::transaction(function () use ($booking, $data, $oldValues, $userId, $auditComment) {
            $booking->update($data);
            $this->audits->record(
                'Booking',
                $booking->id,
                'updated',
                $oldValues,
                $data,
                $userId,
                $auditComment,
            );
        });

        BookingsUpdated::dispatch();

        if ($booking->status->code === 'APPROVED') {
            broadcast(new BannerUpdated);
        }
    }

    public function changeStatus(Booking $booking, string $statusCode, int $userId, string $notes): void
    {
        $booking->loadMissing('status');
        $previousStatusCode = $booking->status->code;
        $allowedTransitions = [
            'PENDING' => ['APPROVED', 'REJECTED', 'CANCELLED'],
            'APPROVED' => ['FINISHED', 'CANCELLED'],
        ];

        if (! in_array($statusCode, $allowedTransitions[$booking->status->code] ?? [], true)) {
            throw ValidationException::withMessages(['booking' => 'Perubahan status booking tidak valid.']);
        }

        DB::transaction(function () use ($booking, $statusCode, $previousStatusCode, $userId, $notes) {
            $oldStatusId = $booking->status_id;
            $status = BookingStatus::where('code', $statusCode)->firstOrFail();
            $booking->forceFill([
                'status_id' => $status->id, 'processed_by' => $userId,
                'processed_at' => now(), 'processed_notes' => $notes,
            ])->save();
            $this->audits->record(
                'Booking',
                $booking->id,
                'status_changed',
                ['status_id' => $oldStatusId, 'status' => $previousStatusCode],
                [
                    'status_id' => $status->id,
                    'status' => $statusCode,
                    'processed_notes' => $notes,
                ],
                $userId,
                $notes,
            );
        });

        BookingsUpdated::dispatch();

        if (
            in_array($statusCode, ['APPROVED', 'FINISHED'], true)
            || ($statusCode === 'CANCELLED' && $previousStatusCode === 'APPROVED')
        ) {
            broadcast(new BannerUpdated);
        }
    }

    public function finishExpired(?Carbon $now = null): int
    {
        $now ??= now();
        $approvedStatus = BookingStatus::where('code', 'APPROVED')->firstOrFail();
        $finishedStatus = BookingStatus::where('code', 'FINISHED')->firstOrFail();
        $finishedCount = 0;

        Booking::query()
            ->where('status_id', $approvedStatus->id)
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now) {
                        $query->whereDate('date', $now->toDateString())
                            ->whereTime('end_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->select('id')
            ->chunkById(100, function ($bookings) use ($approvedStatus, $finishedStatus, $now, &$finishedCount) {
                foreach ($bookings as $expiredBooking) {
                    DB::transaction(function () use ($expiredBooking, $approvedStatus, $finishedStatus, $now, &$finishedCount) {
                        $booking = Booking::query()->lockForUpdate()->find($expiredBooking->id);

                        if (! $booking || $booking->status_id !== $approvedStatus->id) {
                            return;
                        }

                        $booking->forceFill([
                            'status_id' => $finishedStatus->id,
                            'processed_by' => null,
                            'processed_at' => $now,
                            'processed_notes' => 'Otomatis selesai karena waktu booking telah berakhir',
                        ])->save();

                        $this->audits->record(
                            'Booking',
                            $booking->id,
                            'status_changed',
                            ['status_id' => $approvedStatus->id],
                            ['status_id' => $finishedStatus->id],
                            null,
                            'booking otomatis diakhiri oleh sistem',
                        );

                        $finishedCount++;
                    });
                }
            });

        if ($finishedCount > 0) {
            BookingsUpdated::dispatch();
            broadcast(new BannerUpdated);
        }

        return $finishedCount;
    }

    public function delete(Booking $booking, int $actorId): void
    {
        DB::transaction(function () use ($booking, $actorId) {
            $booking->loadMissing('status:id,code');
            $oldValues = [
                'room_id' => $booking->room_id,
                'user_id' => $booking->user_id,
                'date' => $booking->date->format('Y-m-d'),
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
                'title' => $booking->title,
                'participants_count' => $booking->participants_count,
                'status' => $booking->status->code,
            ];

            $booking->delete();

            $this->audits->record(
                'Booking',
                $booking->id,
                'deleted',
                $oldValues,
                null,
                $actorId,
                'booking dihapus oleh admin',
            );
        });

        BookingsUpdated::dispatch();
        broadcast(new BannerUpdated);
    }
}
