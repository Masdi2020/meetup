<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Booking::query()
            ->with([
                'room:id,name',
                'user:id,name',
                'status:id,code,label',
            ]);

        if ($request->filled('search')) {

            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {

                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('room', function ($room) use ($search) {
                        $room->where('name', 'like', "%{$search}%");
                    });

            });

        }

        if ($request->filled('status')) {

            $query->whereHas('status', function ($q) use ($request) {
                $q->where('code', strtoupper($request->status));
            });

        }

        if ($request->filled('room')) {

            $query->where('room_id', $request->room);

        }

        $bookings = $query
            ->latest()
            ->paginate(10)
            ->through(function (Booking $booking) {

                return [

                    'id' => $booking->id,

                    'code' => $booking->id,

                    'room' => $booking->room->name,

                    'borrower' => $booking->user->name,

                    'activity' => $booking->title,

                    'date' => $booking->date->format('d-m-Y'),

                    'start' => $booking->start_time->format('H:i'),

                    'end' => $booking->end_time->format('H:i'),

                    'status' => strtolower($booking->status->code),

                    'request' => $booking->request,

                    'processed_notes' => $booking->processed_notes,

                ];

            });

        return Inertia::render('Admin/Booking', [

            'bookings' => $bookings,

            'rooms' => Room::select('id', 'name')->orderBy('name')->get(),

            'stats' => [

                'total' => Booking::count(),

                'pending' => Booking::whereHas('status', fn ($q) => $q->where('code', 'PENDING'))->count(),

                'approved' => Booking::whereHas('status', fn ($q) => $q->where('code', 'APPROVED'))->count(),

                'finished' => Booking::whereHas('status', fn ($q) => $q->where('code', 'FINISHED'))->count(),

            ],

            'filters' => $request->only([
                'search',
                'status',
                'room',
            ]),

        ]);
    }

    public function approve(Booking $booking)
    {
        if ($booking->status->code !== 'PENDING') {
            return back()->with('error', 'Booking is not pending.');
        }

        DB::transaction(function () use ($booking) {
            $oldStatus = $booking->status_id;
            $approved = BookingStatus::where('code', 'APPROVED')->firstOrFail();

            $booking->forceFill([
                'status_id' => $approved->id,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
                'processed_notes' => 'Disetujui oleh admin',
            ])->save();

            Audit::create([
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'action' => 'status_changed',
                'old_values' => ['status_id' => $oldStatus],
                'new_values' => ['status_id' => $approved->id],
                'changed_by' => Auth::id(),
                'ip_address' => $this->resolveIpAddress(),
                'comment' => 'booking disetujui',
            ]);
        });

        return back()->with('success', 'Booking approved successfully.');
    }

    public function reject(Request $request, Booking $booking)
    {
        if ($booking->status->code !== 'PENDING') {
            return back()->with('error', 'Booking is not pending.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($booking, $request) {
            $oldStatus = $booking->status_id;
            $rejected = BookingStatus::where('code', 'REJECTED')->firstOrFail();

            $booking->forceFill([
                'status_id' => $rejected->id,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
                'processed_notes' => $request->reason,
            ])->save();

            Audit::create([
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'action' => 'status_changed',
                'old_values' => ['status_id' => $oldStatus],
                'new_values' => ['status_id' => $rejected->id],
                'changed_by' => Auth::id(),
                'ip_address' => $this->resolveIpAddress(),
                'comment' => 'booking ditolak',
            ]);
        });

        return back()->with('success', 'Booking rejected successfully.');
    }

    private function resolveIpAddress(): string
    {
        $ip = request()->header('X-Forwarded-For') ?: request()->header('Client-IP');

        if ($ip) {
            $ip = trim(explode(',', $ip)[0]);
        } else {
            $ip = request()->ip();
        }

        return $ip === '::1' ? '127.0.0.1' : $ip;
    }
}
