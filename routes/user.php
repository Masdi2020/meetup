<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingResultController;
use App\Http\Controllers\User\AvailabilityController;
use App\Http\Controllers\User\HistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');

    Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');

    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::put('/booking/{booking}', [BookingController::class, 'update'])->middleware('booking.action:update')->name('booking.update');
    Route::put('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('booking.cancel');
    Route::patch('/booking/{booking}/finish', [BookingController::class, 'finish'])->middleware('booking.action:finish')->name('booking.finish');
    Route::post('/booking/{booking}/results/documentation', [BookingResultController::class, 'storeDocumentation'])->name('booking.results.documentation');
    Route::post('/booking/{booking}/results/meeting-minutes', [BookingResultController::class, 'storeMeetingMinutes'])->name('booking.results.meeting-minutes');
    Route::delete('/booking/{booking}/attachments/{attachment}', [BookingResultController::class, 'destroy'])->name('booking.results.destroy');
});
