<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Display\BannerService;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(Room $room, BannerService $service): Response
    {
        abort_unless($room->has_display, 404, 'Ruangan tidak memiliki display');

        return Inertia::render(
            'Display/Banner',
            $service->current($room)
        );
    }
}
