<?php

namespace App\Http\Controllers;

use App\Events\BannerUpdated;
use App\Http\Requests\RejectBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    public function __construct(private BookingWorkflowService $bookingWorkflow) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->upper()->toString();
        $room = $request->integer('room') ?: null;
        $bookings = Booking::query()->with(['room:id,name', 'user:id,name', 'status:id,code,label'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('room', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($status, fn ($query) => $query->whereHas('status', fn ($query) => $query->where('code', $status)))
            ->when($room, fn ($query) => $query->where('room_id', $room))->latest()->paginate(10)
            ->through(fn ($booking) => [
                'id' => $booking->id, 'code' => $booking->id, 'room' => $booking->room->name,
                'borrower' => $booking->user->name ?? 'Pengguna dihapus', 'activity' => $booking->title,
                'date' => $booking->date->format('d-m-Y'), 'start' => $booking->start_time->format('H:i'),
                'end' => $booking->end_time->format('H:i'), 'status' => strtolower($booking->status->code),
                'request' => $booking->notes, 'processed_notes' => $booking->processed_notes,
            ]);

        $count = fn (string $code) => Booking::whereHas('status', fn ($query) => $query->where('code', $code))->count();

        return Inertia::render('Admin/Booking', [
            'bookings' => $bookings, 'rooms' => Room::select('id', 'name')->orderBy('name')->get(),
            'stats' => ['total' => Booking::count(), 'pending' => $count('PENDING'), 'approved' => $count('APPROVED'), 'finished' => $count('FINISHED')],
            'filters' => $request->only(['search', 'status', 'room']),
        ]);
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

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        broadcast(new BannerUpdated);

        return back()->with('success', 'Booking berhasil dihapus.');
    }
}
