<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Dashboard')->name('home');
Route::inertia('/login', 'Login')->name('login');
Route::inertia('/register', 'Register')->name('register');
Route::inertia('/kalender', 'Calendar')->name('kalender');
Route::inertia('/pinjam', 'Booking')->name('pinjam');
Route::inertia('/riwayat', 'History')->name('riwayat');
