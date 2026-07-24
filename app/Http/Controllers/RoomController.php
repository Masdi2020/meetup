<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;
use App\Services\RoomService;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index() {
        $rooms = Room::select([
            'id', 'name', 'capacity', 'floor',
            'calendar_url'
        ])->get();

        return Inertia::render('Calendar', [
            'rooms' => $this->roomService->list(),
        ]);
    }
}
