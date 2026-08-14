<?php

use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminFacilityController;
use App\Http\Controllers\AdminRoomController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/meeting/banner', [BannerController::class, 'index'])->name('meeting.banner');

Route::get('/', function () {
    return redirect()->route('meeting.banner');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/forced-password', [ProfileController::class, 'updateForcedPassword'])->name('profile.forced-password');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('dashboard');
            Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
            Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
            Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
            Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');
            Route::get('/facilities', [AdminFacilityController::class, 'index'])->name('facilities.index');
            Route::post('/facilities', [AdminFacilityController::class, 'store'])->name('facilities.store');
            Route::put('/facilities/{facility}', [AdminFacilityController::class, 'update'])->name('facilities.update');
            Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
            Route::post('/rooms', [AdminRoomController::class, 'store'])->name('rooms.store');
            Route::put('/rooms/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
            Route::patch('/rooms/{room}/toggle-availability', [AdminRoomController::class, 'toggleAvailability'])->name('rooms.toggle-availability');
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
            Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');

            Route::get(
                '/settings',
                [AdminSettingController::class, 'edit']
            )->name('settings');

            Route::put(
                '/settings',
                [AdminSettingController::class, 'update']
            )->name('settings.update');
        });

    Route::middleware('role:user')
        ->group(function () {
            Route::inertia('/dashboard', 'Dashboard')->name('home');
            Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
            Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
            Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');
            Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');

            Route::put('/booking/{booking}', [
                BookingController::class,
                'update',
            ])->name('booking.update');

            Route::put('/booking/{booking}/cancel', [
                BookingController::class,
                'cancel',
            ])->name('booking.cancel');
        });
});
