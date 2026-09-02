<?php

namespace App\Http\Controllers;

use App\Models\BookingStatus;
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
            'statuses' => BookingStatus::query()
                ->select(['code', 'label'])
                ->orderBy('id')
                ->get()
                ->map(fn (BookingStatus $status) => [
                    'value' => ucfirst(strtolower($status->code)),
                    'label' => ucfirst(strtolower($status->label)),
                ]),
        ]);
    }
}
