<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFacilityController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingResultController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/export-data', [AdminBookingController::class, 'exportData'])->name('bookings.export-data');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->middleware('booking.action:update')->name('bookings.update');
        Route::put('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('bookings.cancel');
        Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->middleware('booking.action:approve')->name('bookings.approve');
        Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->middleware('booking.action:reject')->name('bookings.reject');
        Route::patch('/bookings/{booking}/finish', [AdminBookingController::class, 'finish'])->middleware('booking.action:finish')->name('bookings.finish');
        Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->middleware('booking.action:destroy')->name('bookings.destroy');
        Route::post('/bookings/{booking}/results/documentation', [BookingResultController::class, 'storeDocumentation'])->name('bookings.results.documentation');
        Route::post('/bookings/{booking}/results/meeting-minutes', [BookingResultController::class, 'storeMeetingMinutes'])->name('bookings.results.meeting-minutes');
        Route::delete('/bookings/{booking}/attachments/{attachment}', [BookingResultController::class, 'destroy'])->name('bookings.results.destroy');

        Route::get('/facilities', [AdminFacilityController::class, 'index'])->name('facilities.index');
        Route::post('/facilities', [AdminFacilityController::class, 'store'])->name('facilities.store');
        Route::put('/facilities/{facility}', [AdminFacilityController::class, 'update'])->name('facilities.update');
        Route::delete('/facilities/{facility}', [AdminFacilityController::class, 'destroy'])->name('facilities.destroy');

        Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [AdminRoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
        Route::patch('/rooms/{room}/toggle-availability', [AdminRoomController::class, 'toggleAvailability'])->name('rooms.toggle-availability');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');

        Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    });
