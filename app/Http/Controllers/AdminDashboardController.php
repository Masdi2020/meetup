<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $pendingStatus = BookingStatus::where('code', 'PENDING')->value('id');

        $today = Carbon::today();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'rooms' => Room::count(),
                'users' => User::count(),
                'bookings' => Booking::count(),
                'pending' => Booking::where('status_id', $pendingStatus)->count(),
                'today_bookings' => Booking::whereDate('date', $today)->count(),
                'today_audits' => Audit::whereDate('created_at', $today)->count(),
            ],

            'todayBookings' => Booking::query()
                ->with('room:id,name')
                ->whereDate('date', $today)
                ->orderBy('start_time')
                ->get([
                    'id', 'room_id', 'title', 'start_time', 'end_time',
                ]),

            'activities' => Audit::query()
                ->with(['user:id,name,role'])
                ->latest()
                ->take(5)
                ->get([
                    'id', 'entity_type',
                    'entity_id', 'action',
                    'old_values', 'new_values',
                    'changed_by', 'ip_address',
                    'comment', 'created_at',
                ]),
        ]);
    }
}
