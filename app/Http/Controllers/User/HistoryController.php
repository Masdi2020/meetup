<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoryRequest;
use App\Services\BookingService;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(HistoryRequest $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('User/History', [
            'histories' => $this->bookingService->history($request->user()->id, $status),
            'filters' => ['status' => ucfirst(strtolower($status))],
            'statuses' => $this->bookingService->historyStatuses(),
        ]);
    }
}
