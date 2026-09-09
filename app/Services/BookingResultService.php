<?php

namespace App\Services;

use App\Enums\BookingAttachmentType;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BookingResultService
{
    /** @param array<int, UploadedFile> $files */
    public function uploadDocumentation(Booking $booking, User $actor, array $files): void
    {
        $this->assertCanManage($booking, $actor);

        foreach ($files as $file) {
            $this->store($booking, $actor, $file, BookingAttachmentType::Documentation);
        }
    }

    public function replaceMeetingMinutes(
        Booking $booking,
        User $actor,
        UploadedFile $file,
    ): void {
        $this->assertCanManage($booking, $actor);

        if ($booking->meetingMinutes) {
            $this->remove($booking->meetingMinutes);
        }

        $this->store($booking, $actor, $file, BookingAttachmentType::MeetingMinutes);
    }

    public function delete(Booking $booking, BookingAttachment $attachment, User $actor): void
    {
        $this->assertCanManage($booking, $actor);

        abort_unless(
            $attachment->booking_id === $booking->id
                && in_array($attachment->getRawOriginal('type'), [
                    BookingAttachmentType::Documentation->value,
                    BookingAttachmentType::MeetingMinutes->value,
                ], true),
            404,
            'Lampiran tidak ditemukan.',
        );

        $this->remove($attachment);
    }

    private function assertCanManage(Booking $booking, User $actor): void
    {
        if ($actor->role === 'user' && $booking->user_id !== $actor->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke hasil rapat ini.');
        }

        $booking->loadMissing('status');

        abort_unless(
            $booking->status->code === 'FINISHED',
            403,
            'Hasil rapat hanya dapat ditambahkan setelah booking berstatus selesai.',
        );
    }

    private function store(
        Booking $booking,
        User $actor,
        UploadedFile $file,
        BookingAttachmentType $type,
    ): void {
        $path = $file->store(
            "booking-results/{$booking->id}/{$type->value}",
            'public',
        );

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan file hasil rapat.');
        }

        $filename = basename($path);

        BookingAttachment::create([
            'booking_id' => $booking->id,
            'original_filename' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
            'uploaded_by' => $actor->id,
        ]);
    }

    private function remove(BookingAttachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();
    }
}
