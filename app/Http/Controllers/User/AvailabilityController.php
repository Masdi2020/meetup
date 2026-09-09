<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(
        Request $request,
        AvailabilityService $availability,
    ): Response {
        return Inertia::render('User/Availability', $availability->data(
            $request->integer('month', now()->month),
            $request->integer('year', now()->year),
            $request->string('view')->toString(),
            $request->filled('date') ? $request->string('date')->toString() : null,
            $request->input('room'),
        ));
    }
}
