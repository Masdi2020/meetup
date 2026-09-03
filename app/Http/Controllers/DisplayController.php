<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function index(RoomService $rooms): Response
    {
        return Inertia::render('Display', [
            'rooms' => $rooms->displayList(),
        ]);
    }
}
