<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Booking', [
            'rooms' => $this->roomService->list(),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $room = Room::query()
            ->whereKey($request->room_id)
            ->where('is_available', true)
            ->first();

        if (!$room) {
            return back()->withErrors([
                'room_id'=> 'Ruangan tidak tersedia untuk dipinjam',
            ]);
        }

        $exists = Booking::query()
            ->where('room_id', '=', $request->room_id)
            ->where('date', '=', $request->date)
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
            $status = 'pending';

            if (
                Auth::user()->role === 'admin' &&
                in_array($request->status, ['pending', 'approved'], true)
            ) {
                $status = $request->status;
            }

            $statusId = BookingStatus::where('code', strtoupper($status))->firstOrFail()->id;
            $processedBy = null;
            $processedAt = null;
            $processedNotes = null;

            if ($status === 'approved') {
                $processedBy = Auth::id();
                $processedAt = now();
                $processedNotes = 'Disetujui oleh admin saat dibuat';
            }

            $booking = Booking::create([
                'room_id' => $request->room_id,
                'user_id' => Auth::id(),
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'title' => $request->title,
                'participants_count' => $request->participants,
                'notes' => $request->input('request'),
                'status_id' => $statusId,
                'processed_by' => $processedBy,
                'processed_at' => $processedAt,
                'processed_notes' => $processedNotes,
            ]);

            Audit::create([
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => ['status_id' => $statusId],
                'changed_by' => Auth::id(),
                'ip_address' => $this->resolveIpAddress(),
                'comment' => $status === 'approved' ? 'booking dibuat dan disetujui' : 'booking dibuat',
            ]);

            if ($request->hasFile('banner')) {
                $file = $request->file('banner');

                $path = $file->store('banners', 'public');

                BookingAttachment::create([
                    'booking_id' => $booking->id,
                    'original_filename' => $file->getClientOriginalName(),
                    'filename' => $file->hashName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        });

        return back()->with('success', 'Booking berhasil dibuat.');
    }

    public function update(
        UpdateBookingRequest $request,
        Booking $booking
    ): RedirectResponse {
        if ($booking->status->code !== 'PENDING') {
            abort(403);
        }

        $booking->update(
            $request->validated()
        );

        return back()->with(
            'success',
            'Peminjaman berhasil diperbarui.'
        );
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

    public function cancel(Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($booking) {
            $booking->load('status');

            if ($booking->status->code !== 'PENDING') {
                abort(403, 'Booking tidak dapat dibatalkan.');
            }

            $oldStatusId = $booking->status_id;

            $cancelled = BookingStatus::where('code', '=', 'CANCELLED')->firstOrFail();

            $booking->update([
                'status_id' => $cancelled->id,
            ]);

            Audit::create([
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'action' => 'status_changed',
                'old_values' => ['status_id' => $oldStatusId],
                'new_values' => ['status_id' => $cancelled->id],
                'changed_by' => Auth::id(),
                'ip_address' => $this->resolveIpAddress(),
                'comment' => 'Dibatalkan oleh peminjam',
            ]);
        });

        return back()->with('success', 'Booking berhasil dibatalkan');
    }
}
