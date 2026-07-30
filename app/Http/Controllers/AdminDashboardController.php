<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingAudit;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $pendingStatus = BookingStatus::where('code', 'PENDING')->value('id');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'rooms' => Room::count(),
                'users' => User::count(),
                'bookings' => Booking::count(),
                'pending' => Booking::where('status_id', $pendingStatus)->count(),
            ],

            'todayBookings' => Booking::query()
                ->with('room:id,name')
                ->whereDate('date', Carbon::today())
                ->orderBy('start_time')
                ->get([
                    'id', 'room_id', 'title', 'start_time',
                ]),

            'activities' => BookingAudit::query()
                ->with([
                    'booking:id,title',
                    'changedBy:id,name',
                    'oldStatus:id,label',
                    'newStatus:id,label',
                ])->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
