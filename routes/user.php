<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/availability', [Controllers\AvailabilityController::class, 'index'])->name('availability.index');

    Route::get('/riwayat', [Controllers\HistoryController::class, 'index'])->name('history.index');

    Route::get('/booking', [Controllers\BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [Controllers\BookingController::class, 'store'])->name('booking.store');
    Route::put('/booking/{booking}', [Controllers\BookingController::class, 'update'])->middleware('booking.action:update')->name('booking.update');
    Route::put('/booking/{booking}/cancel', [Controllers\BookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('booking.cancel');
    Route::patch('/booking/{booking}/finish', [Controllers\BookingController::class, 'finish'])->middleware('booking.action:finish')->name('booking.finish');
    Route::post('/booking/{booking}/results/documentation', [Controllers\BookingResultController::class, 'storeDocumentation'])->name('booking.results.documentation');
    Route::post('/booking/{booking}/results/meeting-minutes', [Controllers\BookingResultController::class, 'storeMeetingMinutes'])->name('booking.results.meeting-minutes');
    Route::delete('/booking/{booking}/attachments/{attachment}', [Controllers\BookingResultController::class, 'destroy'])->name('booking.results.destroy');
});
