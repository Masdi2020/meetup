<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadBookingDocumentationRequest;
use App\Http\Requests\UploadMeetingMinutesRequest;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Services\BookingResultService;
use Illuminate\Http\RedirectResponse;

class BookingResultController extends Controller
{
    public function __construct(private BookingResultService $results) {}

    public function storeDocumentation(
        UploadBookingDocumentationRequest $request,
        Booking $booking,
    ): RedirectResponse {
        $this->results->uploadDocumentation(
            $booking,
            $request->user(),
            $request->validated('documentation'),
        );

        return back()->with('success', 'Dokumentasi rapat berhasil diunggah.');
    }

    public function storeMeetingMinutes(
        UploadMeetingMinutesRequest $request,
        Booking $booking,
    ): RedirectResponse {
        $this->results->replaceMeetingMinutes(
            $booking,
            $request->user(),
            $request->validated('meeting_minutes'),
        );

        return back()->with('success', 'Notulensi rapat berhasil disimpan.');
    }

    public function destroy(
        Booking $booking,
        BookingAttachment $attachment,
    ): RedirectResponse {
        $this->results->delete($booking, $attachment, request()->user());

        return back()->with('success', 'Hasil rapat berhasil dihapus.');
    }
}
