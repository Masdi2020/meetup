<?php

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/shutdown-check', function () {
    $now = now();

    // Jumat 16:30, Senin-Kamis 16:00
    $closingTime = $now->copy()->setTime(
        16,
        $now->isFriday() ? 30 : 0,
        0
    );

    // Kalau endpoint dipanggil sebelum jam tutup
    if ($now->lt($closingTime)) {
        return response()->json([
            'action' => 'wait',
            'reason' => 'before_closing_time',
            'check_at' => $closingTime->format('Y-m-d H:i:s'),
        ]);
    }

    $latestBooking = Booking::query()
        ->whereDate('date', $now->toDateString())
        ->whereHas('status', function ($query) {
            $query->where('code', 'APPROVED');
        })
        ->whereTime('end_time', '>', $now->format('H:i:s'))
        ->orderByDesc('end_time')
        ->first();

    // Ada booking yang masih berlangsung setelah jam tutup
    if ($latestBooking) {
        $bookingEnd = Carbon::parse(
            $now->toDateString().' '.$latestBooking->end_time
        );

        return response()->json([
            'action' => 'wait',
            'reason' => 'active_booking',
            'check_at' => $bookingEnd->format('Y-m-d H:i:s'),
        ]);
    }

    // Sudah melewati jam tutup dan tidak ada booking aktif
    return response()->json([
        'action' => 'shutdown',
    ]);
});

Route::get('/csrf-token', function (Request $request) {
    $request->session()->regenerateToken();

    return response()->json([
        'token' => csrf_token(),
    ])->header('Cache-Control', 'no-store, private');
})->middleware(['web', 'guest', 'throttle:10,1']);
