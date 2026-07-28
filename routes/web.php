<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\BannerController;

Route::get('/meeting/banner', [BannerController::class, 'index'])->name('meeting.banner');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::inertia('/', 'Dashboard')->name('home');
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
