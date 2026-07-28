<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke() {
        return Inertia::render("Admin/Dashboard", [
            'stats' => [
                'rooms' => Room::count(),
                'users'=> User::count(),
                'bookings' => Booking::count(),
                'pending' => Booking::where('status_id', 1)->count(),
            ],
        ]);
    }
}
