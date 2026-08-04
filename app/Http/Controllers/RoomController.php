<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Calendar', [
            'rooms' => $this->roomService->calendar(),
        ]);
    }
}
