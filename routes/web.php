<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return match (auth()->user()?->role) {
        'user' => to_route('availability.index'),
        'admin' => to_route('admin.dashboard'),
        'display' => to_route('display.index'),
        default => to_route('login'),
    };
})->name('root');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/booking/availability', [BookingController::class, 'availability'])
        ->middleware('role:user,admin')
        ->name('booking.availability');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/forced-password', [ProfileController::class, 'updateForcedPassword'])->name('profile.forced-password');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

require __DIR__.'/admin.php';
require __DIR__.'/api.php';
require __DIR__.'/display.php';
require __DIR__.'/user.php';
