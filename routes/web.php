<?php

use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminFacilityController;
use App\Http\Controllers\AdminRoomController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AvailabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/meeting/banner', [BannerController::class, 'index'])->name('meeting.banner');

Route::get('/', function () {
    return redirect()->route('meeting.banner');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware(['auth'])->group(function () {
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('dashboard');
            // Route::inertia('/bookings', 'Admin/Booking')->name('booking');
            Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
            Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
            Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');
            Route::get('/facilities', [AdminFacilityController::class, 'index'])->name('facilities.index');
            Route::post('/facilities', [AdminFacilityController::class, 'store'])->name('facilities.store');
            Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
            Route::inertia('/audits', 'Admin/Audit')->name('audit');
            Route::inertia('/settings', 'Admin/Setting')->name('setting');
        });

    Route::middleware('role:user')
        ->group(function () {
            Route::inertia('/dashboard', 'Dashboard')->name('home');
            Route::get('/pinjam', [BookingController::class, 'index'])->name('booking.index');
            Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
            // Route::get('/kalender', [RoomController::class, 'index'])->name('calendar.index');
            Route::get('/kalender', [AvailabilityController::class, 'index'])->name('calendar.events');
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
