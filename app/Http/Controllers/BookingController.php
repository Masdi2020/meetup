<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingAudit;
use App\Services\RoomService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(): Response {
        return Inertia::render('Booking', [
            'rooms' => $this->roomService->list(),
        ]);
    }

    public function store(StoreBookingRequest $request) {
        $exists = Booking::where('room_id', $request->room_id)
            ->where('date', $request->date)
            ->whereIn('status_id', [1, 2])
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('start_time', '<=', $request->start_time)
                              ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'room_id' => 'Ruangan sudah dibooking pada waktu tersebut.',
            ]);
        }

        DB::transaction(function () use ($request) {
            $booking = Booking::create([
                'room_id'      => $request->room_id,
                'user_id'      => auth()->id(),
                'date'         => $request->date,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'title'        => $request->title,
                'participants' => $request->participants,
                'status_id'    => 1,
            ]);

            if ($request->filled('request')) {
                BookingAudit::create([
                    'booking_id'    => $booking->id,
                    'old_status_id' => null,
                    'new_status_id' => 1,
                    'changed_by'    => auth()->id(),
                    'comment'       => $request->request,
                ]);
            }

            if ($request->hasFile('banner')) {
                $file = $request->file('banner');

                $path = $file->store('banners', 'public');

                BookingAttachment::create([
                    'booking_id' => $booking->id,
                    'filename'   => $file->getClientOriginalName(),
                    'path'       => $path,
                    'mime_type'  => $file->getClientMimeType(),
                    'size'       => $file->getSize(),
                    'uploaded_by'=> auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Booking berhasil dibuat.');
    }
}
