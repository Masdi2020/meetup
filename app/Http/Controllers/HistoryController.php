<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('History', [
            'histories' => $this->bookingService->history($request->user()->id),
            'statuses' => $this->bookingService->historyStatuses(),
        ]);
    }
}
