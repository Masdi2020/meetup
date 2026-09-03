<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportBookingRequest;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Services\AdminBookingQueryService;
use App\Services\BookingWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    public function __construct(
        private BookingWorkflowService $bookingWorkflow,
        private AdminBookingQueryService $bookingQueries,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->lower()->toString();
        $room = $request->integer('room') ?: null;

        return Inertia::render(
            'Admin/Booking',
            $this->bookingQueries->indexData($search, strtoupper($status), $room),
        );
    }

    public function exportData(ExportBookingRequest $request): JsonResponse
    {
        return response()->json($this->bookingQueries->exportData(
            $request->exportOptions(),
            $request->searchTerm(),
            $request->statusFilter(),
            $request->roomId(),
        ));
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'APPROVED', $request->user()->id, 'Disetujui oleh admin');

        return back()->with('success', 'Booking approved successfully.');
    }

    public function reject(RejectBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'REJECTED', $request->user()->id, $request->validated('reason'));

        return back()->with('success', 'Booking rejected successfully.');
    }

    public function finish(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'FINISHED', $request->user()->id, 'Diakhiri oleh admin');

        return back()->with('success', 'Booking berhasil diakhiri.');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->update(
            $booking,
            $request->bookingData(),
            $request->user()->id,
            'booking diperbarui oleh admin',
        );

        return back()->with('success', 'Booking berhasil diperbarui.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus(
            $booking,
            'CANCELLED',
            $request->user()->id,
            'Dibatalkan oleh admin',
        );

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->delete($booking, $request->user()->id);

        return back()->with('success', 'Booking berhasil dihapus.');
    }
}
