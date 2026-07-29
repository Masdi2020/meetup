<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminBookingController;

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
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
            // Route::inertia('/bookings', 'Admin/Booking')->name('admin.booking');
            Route::get('/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
            Route::inertia('/rooms', 'Admin/Room')->name('admin.room');
            Route::inertia('/facilities', 'Admin/Facility')->name('admin.facility');
            Route::inertia('/users', 'Admin/User')->name('admin.user');
            Route::inertia('/audits', 'Admin/Audit')->name('admin.audit');
            Route::inertia('/settings', 'Admin/Setting')->name('admin.setting');
        });

    Route::middleware('role:user')
        ->group(function () {
            Route::inertia('/dashboard', 'Dashboard')->name('home');
            Route::get('/pinjam', [BookingController::class, 'index'])->name('booking.index');
            Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
            Route::get('/kalender', [RoomController::class, 'index'])->name('calendar.index');
            Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');


            Route::put('/booking/{booking}', [
                BookingController::class,
                'update'
            ])->name('booking.update');

            Route::put('/booking/{booking}/cancel', [
                BookingController::class,
                'cancel'
            ])->name('booking.cancel');
        });
});
