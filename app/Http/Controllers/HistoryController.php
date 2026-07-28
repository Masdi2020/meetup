<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index() {
        return Inertia::render('History', [
            'histories' => $this->bookingService->history(auth()->id()),
        ]);
    }
}
