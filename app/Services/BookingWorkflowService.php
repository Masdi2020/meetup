<?php

namespace App\Services;

use App\Enums\BookingAttachmentType;
use App\Events\BannerUpdated;
use App\Events\BookingsUpdated;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingWorkflowService
{
    public function __construct(
        private AuditService $audits,
        private BookingAttachmentService $attachments,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, int $actorUserId, bool $isAdmin): Booking
    {
        $statusCode = $isAdmin ? (string) $data['status'] : 'PENDING';
        $borrowerId = $isAdmin ? (int) $data['user_id'] : $actorUserId;

        $booking = DB::transaction(function () use ($data, $actorUserId, $borrowerId, $statusCode) {
            $this->lockRoom((int) $data['room_id']);

            if (! Room::query()->whereKey($data['room_id'])->where('is_available', true)->exists()) {
                throw ValidationException::withMessages(['room_id' => 'Ruangan tidak tersedia untuk dipinjam']);
            }

            if (in_array($statusCode, Booking::ACTIVE_STATUSES, true) && $this->hasOverlap(
                (int) $data['room_id'], $data['date'], $data['start_time'], $data['end_time'],
            )) {
                throw ValidationException::withMessages(['room_id' => 'Ruangan sudah dibooking pada waktu tersebut.']);
            }

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
                $attachment = $this->attachments->store(
                    $booking, $actorUserId, $data['banner'], BookingAttachmentType::Banner,
                );

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
        DB::transaction(function () use ($booking, $data, $userId, $auditComment) {
            $this->lockRoom($booking->room_id);
            $booking->refresh();

            if ($booking->trashed() || ! in_array($booking->status->code, Booking::ACTIVE_STATUSES, true)) {
                throw ValidationException::withMessages(['booking' => 'Booking tidak dapat diperbarui pada status ini.']);
            }

            if ($this->hasOverlap(
                $booking->room_id, $data['date'], $data['start_time'], $data['end_time'], $booking->id,
            )) {
                throw ValidationException::withMessages(['date' => 'Ruangan sudah dibooking pada waktu tersebut.']);
            }

            $oldValues = [
                'title' => $booking->title,
                'date' => $booking->date->format('Y-m-d'),
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
            ];

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
        $previousStatusCode = DB::transaction(function () use ($booking, $statusCode, $userId, $notes): string {
            $this->lockRoom($booking->room_id);
            $booking->refresh();
            $previousStatusCode = $booking->status->code;
            $allowedTransitions = [
                'PENDING' => ['APPROVED', 'REJECTED', 'CANCELLED'],
                'APPROVED' => ['FINISHED', 'CANCELLED'],
            ];

            if ($booking->trashed() || ! in_array($statusCode, $allowedTransitions[$previousStatusCode] ?? [], true)) {
                throw ValidationException::withMessages(['booking' => 'Perubahan status booking tidak valid.']);
            }

            if ($statusCode === 'APPROVED' && $this->hasOverlap(
                $booking->room_id, $booking->date->format('Y-m-d'),
                $booking->start_time->format('H:i'), $booking->end_time->format('H:i'), $booking->id,
            )) {
                throw ValidationException::withMessages(['booking' => 'Ruangan sudah dibooking pada waktu tersebut.']);
            }

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

            return $previousStatusCode;
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
            ->select(['id', 'room_id'])
            ->chunkById(100, function ($bookings) use ($approvedStatus, $finishedStatus, $now, &$finishedCount) {
                foreach ($bookings as $expiredBooking) {
                    DB::transaction(function () use ($expiredBooking, $approvedStatus, $finishedStatus, $now, &$finishedCount) {
                        $this->lockRoom($expiredBooking->room_id);
                        $booking = Booking::query()->lockForUpdate()->find($expiredBooking->id);

                        if (! $booking || $booking->status_id !== $approvedStatus->id
                            || $booking->date->format('Y-m-d').' '.$booking->end_time->format('H:i:s') > $now->format('Y-m-d H:i:s')) {
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

    private function lockRoom(int $roomId): void
    {
        // A write locks the room before any reads, including on SQLite where FOR UPDATE is ignored.
        DB::table('rooms')->where('id', $roomId)->update(['updated_at' => DB::raw('updated_at')]);
    }

    private function hasOverlap(int $roomId, string $date, string $startTime, string $endTime, ?int $ignoreBookingId = null): bool
    {
        return Booking::query()
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->active()
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->whereTime('start_time', '<', Carbon::parse($endTime))
            ->whereTime('end_time', '>', Carbon::parse($startTime))
            ->exists();
    }
}
