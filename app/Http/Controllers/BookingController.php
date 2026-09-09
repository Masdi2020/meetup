<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\BookingWorkflowService;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected RoomService $roomService,
        protected BookingWorkflowService $bookingWorkflow,
        protected BookingService $bookingService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('User/Booking', ['rooms' => $this->roomService->list()]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'ignore_booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ]);

        $ignoreBookingId = $request->user()->role === 'admin'
            ? ($validated['ignore_booking_id'] ?? null)
            : null;

        return response()->json([
            'booked_intervals' => $this->bookingService->activeIntervals(
                (int) $validated['room_id'],
                $validated['date'],
                $ignoreBookingId ? (int) $ignoreBookingId : null,
            ),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->bookingWorkflow->create($request->validated(), $user->id, $user->role === 'admin');

        return back()->with('success', 'Booking berhasil dibuat.');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->update($booking, $request->bookingData(), $request->user()->id);

        return back()->with('success', 'Peminjaman berhasil diperbarui.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus(
            $booking, 'CANCELLED', request()->user()->id, 'Dibatalkan oleh peminjam'
        );

        return back()->with('success', 'Booking berhasil dibatalkan');
    }

    public function finish(Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus(
            $booking, 'FINISHED', request()->user()->id, 'Diakhiri oleh peminjam'
        );

        return back()->with('success', 'Peminjaman berhasil diakhiri.');
    }
}
