<?php

namespace App\Services;

use App\Enums\BookingAttachmentType;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class BookingResultService
{
    public function __construct(private BookingAttachmentService $attachments) {}

    /** @param array<int, UploadedFile> $files */
    public function uploadDocumentation(Booking $booking, User $actor, array $files): void
    {
        $stored = [];

        try {
            DB::transaction(function () use ($booking, $actor, $files, &$stored) {
                DB::table('bookings')->where('id', $booking->id)->update(['updated_at' => DB::raw('updated_at')]);
                $booking = Booking::findOrFail($booking->id);
                $this->assertCanManage($booking, $actor);

                foreach ($files as $file) {
                    $stored[] = $this->attachments->store($booking, $actor->id, $file, BookingAttachmentType::Documentation);
                }
            });
        } catch (Throwable $exception) {
            foreach ($stored as $attachment) {
                $this->attachments->deleteFile($attachment->path, $attachment->type);
            }

            throw $exception;
        }
    }

    public function replaceMeetingMinutes(
        Booking $booking,
        User $actor,
        UploadedFile $file,
    ): void {
        $replacement = null;

        try {
            DB::transaction(function () use ($booking, $actor, $file, &$replacement) {
                DB::table('bookings')->where('id', $booking->id)->update(['updated_at' => DB::raw('updated_at')]);
                $booking = Booking::findOrFail($booking->id);
                $this->assertCanManage($booking, $actor);
                $previousMinutes = $booking->meetingMinutes;
                $replacement = $this->attachments->store($booking, $actor->id, $file, BookingAttachmentType::MeetingMinutes);

                if ($previousMinutes) {
                    $this->remove($previousMinutes);
                }
            });
        } catch (Throwable $exception) {
            if ($replacement) {
                $this->attachments->deleteFile($replacement->path, $replacement->type);
            }

            throw $exception;
        }
    }

    public function response(Booking $booking, BookingAttachment $attachment, User $actor): StreamedResponse
    {
        $this->assertCanManage($booking, $actor);
        $this->assertResultAttachment($booking, $attachment);
        $disk = Storage::disk('local');

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->response($attachment->path, $attachment->original_filename, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(Booking $booking, BookingAttachment $attachment, User $actor): void
    {
        $this->assertCanManage($booking, $actor);

        $this->assertResultAttachment($booking, $attachment);
        DB::transaction(fn () => $this->remove($attachment));
    }

    private function assertResultAttachment(Booking $booking, BookingAttachment $attachment): void
    {
        abort_unless(
            $attachment->booking_id === $booking->id
                && in_array($attachment->getRawOriginal('type'), [
                    BookingAttachmentType::Documentation->value,
                    BookingAttachmentType::MeetingMinutes->value,
                ], true),
            404,
            'Lampiran tidak ditemukan.',
        );
    }

    private function assertCanManage(Booking $booking, User $actor): void
    {
        if (! $actor->isAdmin() && ($actor->role !== 'user' || $booking->user_id !== $actor->id)) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke hasil rapat ini.');
        }

        $booking->loadMissing('status');

        abort_unless(
            $booking->status->code === 'FINISHED',
            403,
            'Hasil rapat hanya dapat ditambahkan setelah booking berstatus selesai.',
        );
    }

    private function remove(BookingAttachment $attachment): void
    {
        $attachment->delete();
        DB::afterCommit(fn () => $this->attachments->deleteFile($attachment->path, $attachment->type));
    }
}
