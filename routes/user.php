<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingResultController;
use App\Http\Controllers\User\AvailabilityController;
use App\Http\Controllers\User\HistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');

    Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');

    Route::put('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('booking.cancel');
    Route::patch('/booking/{booking}/finish', [BookingController::class, 'finish'])->middleware('booking.action:finish')->name('booking.finish');
    Route::post('/booking/{booking}/results/documentation', [BookingResultController::class, 'storeDocumentation'])->name('booking.results.documentation');
    Route::post('/booking/{booking}/results/meeting-minutes', [BookingResultController::class, 'storeMeetingMinutes'])->name('booking.results.meeting-minutes');
    Route::resource('booking.attachments', BookingResultController::class)
        ->only(['show', 'destroy'])
        ->names([
            'show' => 'booking.results.show',
            'destroy' => 'booking.results.destroy',
        ]);

    Route::resource('booking', BookingController::class)
        ->only(['index', 'store', 'update'])
        ->middlewareFor('update', 'booking.action:update');
});
