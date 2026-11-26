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

        Route::post('/bookings/export-data', [AdminBookingController::class, 'exportData'])->name('bookings.export-data');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->middleware('booking.action:cancel')->name('bookings.cancel');
        Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->middleware('booking.action:approve')->name('bookings.approve');
        Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->middleware('booking.action:reject')->name('bookings.reject');
        Route::patch('/bookings/{booking}/finish', [AdminBookingController::class, 'finish'])->middleware('booking.action:finish')->name('bookings.finish');
        Route::post('/bookings/{booking}/results/documentation', [BookingResultController::class, 'storeDocumentation'])->name('bookings.results.documentation');
        Route::post('/bookings/{booking}/results/meeting-minutes', [BookingResultController::class, 'storeMeetingMinutes'])->name('bookings.results.meeting-minutes');
        Route::resource('bookings.attachments', BookingResultController::class)
            ->only(['show', 'destroy'])
            ->names([
                'show' => 'bookings.results.show',
                'destroy' => 'bookings.results.destroy',
            ]);

        Route::resource('bookings', AdminBookingController::class)
            ->only(['index', 'update', 'destroy'])
            ->middlewareFor('update', 'booking.action:update')
            ->middlewareFor('destroy', 'role:super_admin');

        Route::resource('facilities', AdminFacilityController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('/rooms/{room}/toggle-availability', [AdminRoomController::class, 'toggleAvailability'])->name('rooms.toggle-availability');
        Route::resource('rooms', AdminRoomController::class)
            ->only(['index', 'store', 'update']);

        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', AdminUserController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor(['store', 'update', 'destroy'], 'role:super_admin');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');
            Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings');
            Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        });
    });
