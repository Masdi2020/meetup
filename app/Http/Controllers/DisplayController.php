<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function index(): Response
    {
        $rooms = Room::query()
            ->select([
                'id',
                'name',
                'location',
                'capacity',
                'is_available',
                'has_display',
            ])
            ->get();

        return Inertia::render('Display', [
            'rooms' => $rooms,
        ]);
    }
}
