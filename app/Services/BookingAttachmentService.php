<?php

namespace App\Services;

use App\Enums\BookingAttachmentType;
use App\Models\Booking;
use App\Models\BookingAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookingAttachmentService
{
    public function store(
        Booking $booking,
        int $actorId,
        UploadedFile $file,
        BookingAttachmentType $type,
    ): BookingAttachment {
        $field = $type->value;
        $label = $type === BookingAttachmentType::Banner ? 'banner' : 'unggahan';

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $field => "File {$label} tidak valid.",
            ]);
        }

        $sourcePath = $file->getPathname();

        if ($sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw ValidationException::withMessages([
                $field => "File sementara {$label} tidak ditemukan.",
            ]);
        }

        $filename = $file->hashName();
        $path = $type === BookingAttachmentType::Banner
            ? "banners/{$filename}"
            : "booking-results/{$booking->id}/{$type->value}/{$filename}";
        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Gagal membuka file hasil rapat.');
        }

        try {
            if (! Storage::disk('public')->writeStream($path, $stream)) {
                throw new RuntimeException('Gagal menyimpan file hasil rapat.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return BookingAttachment::create([
            'booking_id' => $booking->id,
            'original_filename' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
            'uploaded_by' => $actorId,
        ]);
    }
}
