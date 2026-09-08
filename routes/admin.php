<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Controllers\AdminDashboardController::class)->name('dashboard');

        Route::get('/bookings', [Controllers\AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/export-data', [Controllers\AdminBookingController::class, 'exportData'])->name('bookings.export-data');
        Route::post('/bookings', [Controllers\BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [Controllers\AdminBookingController::class, 'update'])->middleware('booking.action:update')->name('bookings.update');
        Route::put('/bookings/{booking}/cancel', [Controllers\AdminBookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('bookings.cancel');
        Route::patch('/bookings/{booking}/approve', [Controllers\AdminBookingController::class, 'approve'])->middleware('booking.action:approve')->name('bookings.approve');
        Route::patch('/bookings/{booking}/reject', [Controllers\AdminBookingController::class, 'reject'])->middleware('booking.action:reject')->name('bookings.reject');
        Route::patch('/bookings/{booking}/finish', [Controllers\AdminBookingController::class, 'finish'])->middleware('booking.action:finish')->name('bookings.finish');
        Route::delete('/bookings/{booking}', [Controllers\AdminBookingController::class, 'destroy'])->middleware('booking.action:destroy')->name('bookings.destroy');
        Route::post('/bookings/{booking}/results/documentation', [Controllers\BookingResultController::class, 'storeDocumentation'])->name('bookings.results.documentation');
        Route::post('/bookings/{booking}/results/meeting-minutes', [Controllers\BookingResultController::class, 'storeMeetingMinutes'])->name('bookings.results.meeting-minutes');
        Route::delete('/bookings/{booking}/attachments/{attachment}', [Controllers\BookingResultController::class, 'destroy'])->name('bookings.results.destroy');

        Route::get('/facilities', [Controllers\AdminFacilityController::class, 'index'])->name('facilities.index');
        Route::post('/facilities', [Controllers\AdminFacilityController::class, 'store'])->name('facilities.store');
        Route::put('/facilities/{facility}', [Controllers\AdminFacilityController::class, 'update'])->name('facilities.update');
        Route::delete('/facilities/{facility}', [Controllers\AdminFacilityController::class, 'destroy'])->name('facilities.destroy');

        Route::get('/rooms', [Controllers\AdminRoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [Controllers\AdminRoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [Controllers\AdminRoomController::class, 'update'])->name('rooms.update');
        Route::patch('/rooms/{room}/toggle-availability', [Controllers\AdminRoomController::class, 'toggleAvailability'])->name('rooms.toggle-availability');

        Route::get('/users', [Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [Controllers\AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [Controllers\AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/audits', [Controllers\AdminAuditController::class, 'index'])->name('audits.index');

        Route::get('/settings', [Controllers\AdminSettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [Controllers\AdminSettingController::class, 'update'])->name('settings.update');
    });
