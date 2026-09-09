<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Services\RoomService;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function index(RoomService $rooms): Response
    {
        return Inertia::render('Display/Display', [
            'rooms' => $rooms->displayList(),
        ]);
    }
}
