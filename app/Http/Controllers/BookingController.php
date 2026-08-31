<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Services\BookingWorkflowService;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected RoomService $roomService,
        protected BookingWorkflowService $bookingWorkflow,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Booking', ['rooms' => $this->roomService->list()]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->bookingWorkflow->create($request->validated(), $user->id, $user->role === 'admin');

        return back()->with('success', 'Booking berhasil dibuat.');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        if ($booking->status->code !== 'PENDING') {
            abort(403);
        }

        $booking->update($request->validated());

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
